<?php
class AdforestMapHandler {
    private $theme_options;
    private $is_update;
    private $ad_map_lat;
    private $ad_map_long;

    public function __construct($theme_options, $is_update = false, $ad_map_lat = '', $ad_map_long = '') {
        $this->theme_options = $theme_options;
        $this->is_update = $is_update;
        $this->ad_map_lat = $ad_map_lat;
        $this->ad_map_long = $ad_map_long;
    }

    public function render() {
        if (!$this->isLatLonAllowed()) {
            $this->renderAddressAutocomplete();
        }

        if ($this->isLatLonAllowed() && $this->hasDefaultCoordinates()) {
            $this->renderMapWithCoordinates();
        }
    }

    private function isLatLonAllowed() {
        return isset($this->theme_options['allow_lat_lon']) && $this->theme_options['allow_lat_lon'] == '1';
    }

    private function hasDefaultCoordinates() {
        return isset($this->theme_options['sb_default_lat']) &&
            $this->theme_options['sb_default_lat'] != '' &&
            isset($this->theme_options['sb_default_long']) &&
            $this->theme_options['sb_default_long'] != '';
    }

    private function getMapType() {
        return $this->theme_options['map-setings-map-type'] ?? 'google_map';
    }

    private function renderAddressAutocomplete() {
        $map_type = $this->getMapType();

        if ($map_type == "leafletjs_map") {
            $this->renderLeafletAutocomplete();
        } else {
            $this->renderGoogleAutocomplete();
        }
    }

    private function renderLeafletAutocomplete() {
        ?>
        <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function () {
                const MapAutocomplete = {
                    init() {
                        new Autocomplete("ad_address", {
                            selectFirst: true,
                            howManyCharacters: 2,
                            onSearch: this.handleSearch,
                            onResults: this.handleResults,
                            onSubmit: this.handleSubmit,
                            noResults: this.handleNoResults
                        });
                    },

                    handleSearch: ({currentValue}) => {
                        const _ccParam = '<?php echo $this->getNominatimCountryCodesParam(); ?>';
                        const _ftParam = '<?php echo $this->getNominatimFeatureTypeParam(); ?>';
                        const _defLat = <?php echo json_encode(floatval($this->theme_options['sb_default_lat'] ?? 0)); ?>;
                        const _defLng = <?php echo json_encode(floatval($this->theme_options['sb_default_long'] ?? 0)); ?>;
                        const _vb = (_defLat || _defLng) ? `&viewbox=${_defLng-1},${_defLat-1},${_defLng+1},${_defLat+1}&bounded=0` : '';
                        const api = `https://nominatim.openstreetmap.org/search?format=geojson&limit=10&q=${encodeURI(currentValue)}${_ccParam}${_ftParam}${_vb}`;
                        return fetch(api)
                            .then(response => response.json())
                            .then(data => {
                                var features = data.features;
                                <?php if (($this->theme_options['sb_location_type'] ?? 'cities') !== 'regions') : ?>
                                var _cityTypes = ['city', 'town', 'village', 'municipality', 'administrative'];
                                features = features.filter(function(f) { return _cityTypes.indexOf(f.properties.type) !== -1; });
                                <?php endif; ?>
                                return features;
                            })
                            .catch(error => {
                                console.error('Geocoding error:', error);
                                return [];
                            });
                    },

                    handleResults: ({currentValue, matches, template}) => {
                        if (matches === 0) return template;

                        const regex = new RegExp(currentValue, "gi");
                        return matches.map(element => `
                            <li class="loupe">
                                <p>${element.properties.display_name.replace(regex, str => `<b>${str}</b>`)}</p>
                            </li>
                        `).join("");
                    },

                    handleSubmit: ({object}) => {
                        const {geometry} = object;
                        const [lng, lat] = geometry.coordinates;

                        MapHandler.updateMapLocation(lat, lng);
                    },

                    handleNoResults: ({currentValue, template}) =>
                        template(`<li><?php echo esc_html__("No results found", "adforest-elementor"); ?>: "${currentValue}"</li>`)
                };

                const MapHandler = {
                    updateMapLocation(lat, lng) {
                        if (typeof my_map !== 'undefined') {
                            my_map.setView([lat, lng], 13);

                            if (typeof markers !== 'undefined' && markers) {
                                my_map.removeLayer(markers);
                            }

                            markers = L.marker([lat, lng], {draggable: true}).addTo(my_map);
                            this.updateFormFields(lat, lng);
                            this.addMarkerEvents(markers);
                        }
                    },

                    updateFormFields(lat, lng) {
                        const latField = document.getElementById("ad_map_lat");
                        const lngField = document.getElementById("ad_map_long");

                        if (latField) latField.value = lat;
                        if (lngField) lngField.value = lng;
                    },

                    addMarkerEvents(marker) {
                        marker.on("dragend", async (e) => {
                            const {lat, lng} = marker.getLatLng();
                            this.updateFormFields(lat, lng);

                            try {
                                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                                const data = await response.json();

                                const addressField = document.getElementById("ad_address");
                                if (addressField && data.display_name) {
                                    addressField.value = data.display_name;
                                }
                            } catch (error) {
                                console.error('Error fetching address:', error);
                            }
                        });
                    }
                };

                MapAutocomplete.init();
            });
        </script>
        <?php
    }

    private function renderGoogleAutocomplete() {
        $autocomplete_options = $this->getAutocompleteOptions();
        ?>
        <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function () {
                const GoogleAutocomplete = {
                    init() {
                        const adAddressInput = document.getElementById("ad_address");
                        if (!adAddressInput) return;

                        const options = <?php echo json_encode($autocomplete_options); ?>;
                        const autocomplete = new google.maps.places.Autocomplete(adAddressInput, options);

                        if (typeof my_map !== 'undefined') {
                            autocomplete.bindTo("bounds", my_map);
                        }
                    }
                };

                GoogleAutocomplete.init();
            });
        </script>
        <?php
    }

    private function getAutocompleteOptions() {
        $options = [];

        $location_type = $this->theme_options['sb_location_type'] ?? 'cities';
        if ($location_type !== 'regions') {
            $options['types'] = ['(cities)'];
        }

        if (!$this->isLocationAllowed() && isset($this->theme_options['sb_list_allowed_country'])) {
            $options['componentRestrictions'] = [
                'country' => $this->theme_options['sb_list_allowed_country']
            ];
        }

        return $options;
    }

    private function isLocationAllowed() {
        return isset($this->theme_options['sb_location_allowed']) && $this->theme_options['sb_location_allowed'];
    }

    private function getNominatimCountryCodesParam() {
        if (!$this->isLocationAllowed() && isset($this->theme_options['sb_list_allowed_country']) && is_array($this->theme_options['sb_list_allowed_country'])) {
            return '&countrycodes=' . implode(',', $this->theme_options['sb_list_allowed_country']);
        }
        return '';
    }

    private function getNominatimViewboxParam() {
        $lat = floatval($this->theme_options['sb_default_lat'] ?? 0);
        $lng = floatval($this->theme_options['sb_default_long'] ?? 0);
        if ($lat || $lng) {
            return '&viewbox=' . ($lng - 1) . ',' . ($lat - 1) . ',' . ($lng + 1) . ',' . ($lat + 1) . '&bounded=0';
        }
        return '';
    }

    private function getNominatimFeatureTypeParam() {
        $location_type = $this->theme_options['sb_location_type'] ?? 'cities';
        if ($location_type !== 'regions') {
            return '&featuretype=city';
        }
        return '';
    }

    private function renderMapWithCoordinates() {
        $map_type = $this->getMapType();
        $coordinates = $this->getCoordinates();

        if ($map_type == "leafletjs_map") {
            $this->renderLeafletMap($coordinates);
        } else {
            $this->renderGoogleMap($coordinates);
        }

        $this->renderMapHTML();
    }

    private function getCoordinates() {
        $default_lat = $this->theme_options['sb_default_lat'];
        $default_long = $this->theme_options['sb_default_long'];

        return [
            'lat' => $this->is_update ? $this->ad_map_lat : $default_lat,
            'lng' => $this->is_update ? $this->ad_map_long : $default_long
        ];
    }

    private function renderLeafletMap($coordinates) {
        ?>
        <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function () {
                const LeafletMapHandler = {
                    map: null,
                    marker: null,

                    init() {
                        this.initMap(<?php echo json_encode($coordinates); ?>);
                        this.addSearchControl();
                        this.addAutocomplete();
                    },

                    initMap(coords) {
                        this.map = L.map('dvMap', {zoomSnap: 0.25, zoomDelta: 0.5, wheelDebounceTime: 80}).setView([coords.lat, coords.lng], 13);

                        L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/voyager/{z}/{x}/{y}{r}.png', {
                            maxZoom: 19,
                            detectRetina: true,
                            updateWhenZooming: false,
                            attribution: ''
                        }).addTo(this.map);

                        this.map.createPane('labels');
                        this.map.getPane('labels').style.zIndex = 450;
                        this.map.getPane('labels').style.pointerEvents = 'none';
                        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                            maxZoom: 19, updateWhenZooming: false, pane: 'labels', minZoom: 5
                        }).addTo(this.map);

                        L.control.fullscreen().addTo(this.map);

                        var modernIcon = L.divIcon({className: 'leaflet-modern-marker', iconSize: [32, 42], iconAnchor: [16, 42], popupAnchor: [0, -42]});
                        this.marker = L.marker([coords.lat, coords.lng], {icon: modernIcon, draggable: true}).addTo(this.map);
                        this.addMarkerEvents();
                    },

                    addSearchControl() {
                        const searchControl = new L.Control.Search({
                            url: '//nominatim.openstreetmap.org/search?format=json&q={s}<?php echo $this->getNominatimCountryCodesParam() . $this->getNominatimFeatureTypeParam() . $this->getNominatimViewboxParam(); ?>',
                            jsonpParam: 'json_callback',
                            propertyName: 'display_name',
                            propertyLoc: ['lat', 'lon'],
                            marker: this.marker,
                            autoCollapse: true,
                            autoType: true,
                            minLength: 2,
                        });

                        searchControl.on('search:locationfound', (obj) => {
                            const coords = this.parseLatLng(obj.latlng.toString());
                            this.updateFormFields(coords.lat, coords.lng);
                            
                            const addressField = document.getElementById("ad_address");
                            if (addressField && obj.text) {
                                addressField.value = obj.text;
                            }
                        });

                        this.map.addControl(searchControl);
                    },

                    parseLatLng(latlngString) {
                        const match = latlngString.match(/LatLng\(([^,]+),\s*([^)]+)\)/);
                        return match ? {lat: parseFloat(match[1]), lng: parseFloat(match[2])} : null;
                    },

                    addMarkerEvents() {
                        this.marker.on('dragend', async () => {
                            const {lat, lng} = this.marker.getLatLng();
                            this.showLoading(true);

                            try {
                                const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`);
                                const data = await response.json();

                                this.updateFormFields(lat, lng);

                                const addressField = document.getElementById("ad_address");
                                if (addressField && data.display_name) {
                                    addressField.value = data.display_name;
                                }
                            } catch (error) {
                                console.error('Error fetching address:', error);
                            } finally {
                                this.showLoading(false);
                            }
                        });
                    },

                    updateFormFields(lat, lng) {
                        const latField = document.getElementById('ad_map_lat');
                        const lngField = document.getElementById('ad_map_long');

                        if (latField) latField.value = lat;
                        if (lngField) lngField.value = lng;
                    },

                    showLoading(show) {
                        const loader = document.getElementById("sb_loading");
                        if (loader) {
                            loader.style.display = show ? "block" : "none";
                        }
                    },

                    addAutocomplete() {
                        new Autocomplete("ad_address", {
                            selectFirst: true,
                            howManyCharacters: 2,
                            onSearch: this.handleSearch.bind(this),
                            onResults: this.handleResults.bind(this),
                            onSubmit: (payload) => {
                                const { geometry } = payload.object;
                                const [lng, lat] = geometry.coordinates;
                                this.map.setView([lat, lng], 13);

                                if (this.marker) {
                                    this.map.removeLayer(this.marker);
                                }
                                this.marker = L.marker([lat, lng], { draggable: true }).addTo(this.map);
                                this.updateFormFields(lat, lng);
                                this.addMarkerEvents();
                            },

                            noResults: ({ currentValue, template }) =>
                                template(`<li><?php echo esc_html__("No results found", "adforest-elementor"); ?>: "${currentValue}"</li>`)
                        });
                    
                        const addressField = document.getElementById("ad_address");
                        if (addressField) {
                            let searchTimeout;
                            addressField.addEventListener('input', (e) => {
                                clearTimeout(searchTimeout);
                                const query = e.target.value.trim();
                                
                                if (query.length >= 3) {
                                    searchTimeout = setTimeout(() => {
                                        this.performAddressFieldSearch(query);
                                    }, 500);
                                }
                            });
                        }
                    },

                    handleSearch: async ({currentValue}) => {
                        const _ccParam = '<?php echo $this->getNominatimCountryCodesParam(); ?>';
                        const _ftParam = '<?php echo $this->getNominatimFeatureTypeParam(); ?>';
                        let _vb = '';
                        if (this.map) {
                            const b = this.map.getBounds();
                            _vb = `&viewbox=${b.getWest()},${b.getSouth()},${b.getEast()},${b.getNorth()}&bounded=0`;
                        }
                        const api = `https://nominatim.openstreetmap.org/search?format=geojson&limit=10&q=${encodeURI(currentValue)}${_ftParam}${_ccParam}${_vb}`;
                        try {
                            const response = await fetch(api);
                            const data = await response.json();
                            var features = data.features;
                            <?php if (($this->theme_options['sb_location_type'] ?? 'cities') !== 'regions') : ?>
                            var _cityTypes = ['city', 'town', 'village', 'municipality', 'administrative'];
                            features = features.filter(function(f) { return _cityTypes.indexOf(f.properties.type) !== -1; });
                            <?php endif; ?>
                            return features;
                        } catch (error) {
                            console.error('Geocoding error:', error);
                            return [];
                        }
                    },

                    handleResults: ({currentValue, matches, template}) => {
                        if (matches === 0) return template;

                        const regex = new RegExp(currentValue, "gi");
                        return matches.map(element => `
                            <li class="loupe">
                                <p>${element.properties.display_name.replace(regex, str => `<b>${str}</b>`)}</p>
                            </li>
                        `).join("");
                    },

                    handleSubmit: ({object}) => {
                        const {geometry} = object;
                        const [lng, lat] = geometry.coordinates;

                        this.map.setView([lat, lng], 13);

                        if (this.marker) {
                            this.map.removeLayer(this.marker);
                        }

                        this.marker = L.marker([lat, lng], {draggable: true}).addTo(this.map);
                        this.updateFormFields(lat, lng);
                        this.addMarkerEvents();
                    }
                };

                LeafletMapHandler.init();
                const contactTab = document.querySelector('#v-pills-contact-tab');
                if (contactTab) {
                    contactTab.addEventListener('shown.bs.tab', function () {
                        if (LeafletMapHandler.map) {
                            setTimeout(() => {
                                LeafletMapHandler.map.invalidateSize();
                            }, 100);
                        }
                    });
                }
            });
        </script>
        <?php
    }

    private function renderGoogleMap($coordinates) {
        $map_type = $this->theme_options['adforest_google_map_type'] ?? 'roadmap';
        $autocomplete_options = $this->getAutocompleteOptions();
        ?>
        <script type="text/javascript">
            let my_map;
            let marker;

            const GoogleMapHandler = {
                map: null,
                marker: null,
                geocoder: null,

                async init() {
                    const coords = <?php echo json_encode($coordinates); ?>;
                    await this.initMap(coords);
                    this.addAutocomplete();
                    this.addCurrentLocationHandler();
                },

                async initMap(coords) {
                    const mapOptions = {
                        center: new google.maps.LatLng(coords.lat, coords.lng),
                        zoom: 12,
                        mapTypeId: "<?php echo esc_js($map_type); ?>",
                        styles: [
                            {
                                featureType: 'poi',
                                elementType: 'all',
                                stylers: [{visibility: 'off'}]
                            },
                            {
                                featureType: 'transit.station',
                                elementType: 'all',
                                stylers: [{visibility: 'off'}]
                            }
                        ]
                    };

                    this.map = new google.maps.Map(document.getElementById("dvMap"), mapOptions);
                    my_map = this.map;

                    this.geocoder = new google.maps.Geocoder();

                    this.marker = new google.maps.Marker({
                        position: new google.maps.LatLng(coords.lat, coords.lng),
                        map: this.map,
                        draggable: true,
                        animation: google.maps.Animation.DROP
                    });

                    this.addMarkerEvents();
                },

                addAutocomplete() {
                    const adAddressInput = document.getElementById("ad_address");
                    if (!adAddressInput) return;

                    const options = <?php echo json_encode($autocomplete_options); ?>;
                    const autocomplete = new google.maps.places.Autocomplete(adAddressInput, options);
                    autocomplete.bindTo("bounds", this.map);

                    autocomplete.addListener('place_changed', () => {
                        const place = autocomplete.getPlace();
                        if (!place.geometry?.location) {
                            console.log("Autocomplete's returned place contains no geometry");
                            return;
                        }

                        this.map.setCenter(place.geometry.location);
                        this.map.setZoom(12);

                        this.marker.setPosition(place.geometry.location);
                        this.marker.setVisible(true);

                        const lat = place.geometry.location.lat();
                        const lng = place.geometry.location.lng();
                        this.updateFormFields(lat, lng);
                    });
                },

                addMarkerEvents() {
                    this.marker.addListener("dragend", () => {
                        this.showLoading(true);

                        this.geocoder.geocode(
                            {"latLng": this.marker.getPosition()},
                            (results, status) => {
                                if (status === google.maps.GeocoderStatus.OK) {
                                    const lat = this.marker.getPosition().lat();
                                    const lng = this.marker.getPosition().lng();
                                    const address = results[0].formatted_address;

                                    this.updateFormFields(lat, lng);

                                    const addressField = document.getElementById("ad_address");
                                    if (addressField) {
                                        addressField.value = address;
                                    }
                                }
                                this.showLoading(false);
                            }
                        );
                    });
                },

                updateFormFields(lat, lng) {
                    const latField = document.getElementById("ad_map_lat");
                    const lngField = document.getElementById("ad_map_long");

                    if (latField) latField.value = lat;
                    if (lngField) lngField.value = lng;
                },

                showLoading(show) {
                    const loader = document.getElementById("sb_loading");
                    if (loader) {
                        loader.style.display = show ? "block" : "none";
                    }
                },

                addCurrentLocationHandler() {
                    jQuery(document).ready(($) => {
                        $("#your_current_location").click(() => {
                            this.getCurrentLocation();
                        });
                    });
                },

                getCurrentLocation() {
                    if (!navigator.geolocation) {
                        console.error("Geolocation is not supported by this browser.");
                        return;
                    }

                    this.showLoading(true);

                    navigator.geolocation.getCurrentPosition(
                        async (position) => {
                            const {latitude, longitude} = position.coords;
                            const pos = new google.maps.LatLng(latitude, longitude);

                            this.map.setCenter(pos);
                            this.map.setZoom(12);
                            this.marker.setPosition(pos);

                            this.updateFormFields(latitude, longitude);

                            try {
                                const response = await new Promise((resolve, reject) => {
                                    this.geocoder.geocode(
                                        {location: {lat: latitude, lng: longitude}},
                                        (results, status) => {
                                            if (status === google.maps.GeocoderStatus.OK) {
                                                resolve(results);
                                            } else {
                                                reject(new Error('Geocoding failed'));
                                            }
                                        }
                                    );
                                });

                                const addressField = document.getElementById("ad_address");
                                if (addressField && response[0]) {
                                    addressField.value = response[0].formatted_address;
                                }
                            } catch (error) {
                                console.error('Error getting address:', error);
                            } finally {
                                this.showLoading(false);
                            }
                        },
                        (error) => {
                            this.showLoading(false);
                            if (typeof toastr !== 'undefined') {
                                toastr.error(error.message, "", {
                                    timeOut: 4000,
                                    closeButton: true,
                                    positionClass: "toast-top-right",
                                });
                            }
                            console.error('Geolocation error:', error);
                        }
                    );
                }
            };

            window.onload = function() {
                GoogleMapHandler.init();
            };
        </script>
        <?php
    }

    private function renderMapHTML() {
        ?>
        <div class="col-lg-12">
            <div class="field-box">
                <label for="dvMap" class="form-label">
                    <?php echo esc_html__("Map", "adforest-elementor"); ?>
                </label>
                <div id="dvMap" style="height: 400px;"></div>
                <em>
                    <small><?php echo esc_html__('Drag pin for your pin-point location.', 'adforest-elementor'); ?></small>
                </em>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="field-box">
                <label for="ad_map_lat" class="form-label">
                    <?php echo esc_html__("Latitude", "adforest-elementor"); ?>
                </label>
                <input type="text"
                       class="form-control"
                       name="ad_map_lat"
                       id="ad_map_lat"
                       value="<?php echo esc_attr($this->ad_map_lat); ?>"
                       placeholder="<?php echo esc_attr__("Latitude", "adforest-elementor"); ?>">
            </div>
        </div>

        <div class="col-lg-6">
            <div class="field-box">
                <label for="ad_map_long" class="form-label">
                    <?php echo esc_html__("Longitude", "adforest-elementor"); ?>
                </label>
                <input type="text"
                       class="form-control"
                       name="ad_map_long"
                       id="ad_map_long"
                       value="<?php echo esc_attr($this->ad_map_long); ?>"
                       placeholder="<?php echo esc_attr__("Longitude", "adforest-elementor"); ?>">
            </div>
        </div>
        <?php
    }
}

?>