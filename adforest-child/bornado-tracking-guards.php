<?php

if (!function_exists('adforest_load_search_countries')) {
    /**
     * Child-theme override for AdForest Google Places bootstrap.
     *
     * Keeps manual address input usable without noisy console warnings or long
     * polling loops when Google Places is unavailable on the current page.
     *
     * @param string $action_on_complete Whether to sync map lat/lng on selection.
     * @return void
     */
    function adforest_load_search_countries($action_on_complete = '')
    {
        global $adforest_theme;

        if (
            !is_array($adforest_theme)
            || empty($adforest_theme['map-setings-map-type'])
            || $adforest_theme['map-setings-map-type'] !== 'google_map'
        ) {
            return;
        }

        $options = array();

        if (
            isset($adforest_theme['sb_location_type'])
            && (string) $adforest_theme['sb_location_type'] !== ''
            && (string) $adforest_theme['sb_location_type'] !== 'regions'
        ) {
            $options['types'] = array('(cities)');
        } elseif (!isset($adforest_theme['sb_location_type']) || (string) $adforest_theme['sb_location_type'] === '') {
            $options['types'] = array('(cities)');
        }

        if (
            isset($adforest_theme['sb_location_allowed'])
            && !$adforest_theme['sb_location_allowed']
            && isset($adforest_theme['sb_list_allowed_country'])
            && $adforest_theme['sb_list_allowed_country'] !== ''
        ) {
            $options['componentRestrictions'] = array(
                'country' => $adforest_theme['sb_list_allowed_country'],
            );
        }

        $js_options = wp_json_encode($options);
        if (!is_string($js_options) || $js_options === '') {
            $js_options = '{}';
        }

        $should_sync_map = !empty($action_on_complete) ? 'true' : 'false';

        ?>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var options = <?php echo $js_options; ?> || {};
                var syncMapOnComplete = <?php echo $should_sync_map; ?>;
                var initialized = false;

                function resolveInput() {
                    return document.getElementById('sb_user_address');
                }

                function hasMapsScript() {
                    return !!document.querySelector('script[src*="maps.googleapis.com/maps/api/js"]');
                }

                function placesAvailable() {
                    return !!(
                        window.google &&
                        google.maps &&
                        google.maps.places &&
                        typeof google.maps.places.Autocomplete === 'function'
                    );
                }

                function bindAutocomplete() {
                    var input = resolveInput();
                    var autocomplete;

                    if (!input || initialized || !placesAvailable()) {
                        return false;
                    }

                    autocomplete = new google.maps.places.Autocomplete(input, options);
                    initialized = true;

                    if (syncMapOnComplete && google.maps.event && typeof google.maps.event.addListener === 'function') {
                        google.maps.event.addListener(autocomplete, 'place_changed', function () {
                            var place = autocomplete.getPlace();
                            var latField = document.getElementById('ad_map_lat');
                            var lngField = document.getElementById('ad_map_long');

                            if (!place || !place.geometry || !place.geometry.location) {
                                return;
                            }

                            if (latField) {
                                latField.value = place.geometry.location.lat();
                            }

                            if (lngField) {
                                lngField.value = place.geometry.location.lng();
                            }

                            if (typeof window.my_g_map === 'function') {
                                window.my_g_map([
                                    {
                                        title: '',
                                        lat: place.geometry.location.lat(),
                                        lng: place.geometry.location.lng()
                                    }
                                ]);
                            }
                        });
                    }

                    return true;
                }

                window.adforest_location = function () {
                    return bindAutocomplete();
                };

                window._adfGPlacesAvailable = placesAvailable;
                window._adfGPlacesWarnOnce = function () {
                    return false;
                };

                if (!resolveInput() || !hasMapsScript()) {
                    return;
                }

                if (bindAutocomplete()) {
                    return;
                }

                var tries = 0;
                (function waitForPlaces() {
                    if (bindAutocomplete()) {
                        return;
                    }

                    if (++tries >= 40) {
                        return;
                    }

                    window.setTimeout(waitForPlaces, 100);
                })();
            });
        </script>
        <?php
    }
}

if (!function_exists('bornado_print_meta_pixel_dedupe_guard')) {
    /**
     * Drop duplicate Meta Pixel init calls with a low-risk runtime wrapper.
     *
     * @return void
     */
    function bornado_print_meta_pixel_dedupe_guard()
    {
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('REST_REQUEST') && REST_REQUEST) || wp_is_json_request()) {
            return;
        }
        ?>
        <script id="bornado-meta-pixel-dedupe">
            (function (window) {
                "use strict";

                var initializedPixelIds = Object.create(null);
                var patched = false;
                var tries = 0;

                function copyProperties(source, target) {
                    Object.keys(source || {}).forEach(function (key) {
                        try {
                            target[key] = source[key];
                        } catch (error) {
                            return;
                        }
                    });
                }

                function wrapFbq(candidate) {
                    if (typeof candidate !== "function") {
                        return null;
                    }

                    if (candidate.__bornadoPixelGuardWrapped) {
                        return candidate;
                    }

                    function guardedFbq() {
                        var command = arguments.length > 0 ? String(arguments[0] || "") : "";
                        var pixelId = arguments.length > 1 ? String(arguments[1] || "").trim() : "";

                        if (command === "init" && pixelId !== "") {
                            if (initializedPixelIds[pixelId]) {
                                return;
                            }

                            initializedPixelIds[pixelId] = true;
                        }

                        return candidate.apply(this, arguments);
                    }

                    copyProperties(candidate, guardedFbq);
                    guardedFbq.__bornadoPixelGuardWrapped = true;

                    if (candidate.queue) {
                        guardedFbq.queue = candidate.queue;
                    }

                    if (typeof candidate.push === "function") {
                        guardedFbq.push = function () {
                            return guardedFbq.apply(guardedFbq, arguments);
                        };
                    }

                    return guardedFbq;
                }

                function patchFbq() {
                    var current = typeof window.fbq === "function"
                        ? window.fbq
                        : (typeof window._fbq === "function" ? window._fbq : null);
                    var wrapped;

                    if (!current) {
                        return false;
                    }

                    wrapped = wrapFbq(current);
                    if (!wrapped || wrapped === current) {
                        patched = true;
                        return true;
                    }

                    if (window.fbq === current) {
                        window.fbq = wrapped;
                    }

                    if (window._fbq === current || typeof window._fbq === "undefined") {
                        window._fbq = wrapped;
                    }

                    patched = true;
                    return true;
                }

                (function waitForFbq() {
                    if (patchFbq()) {
                        return;
                    }

                    if (++tries >= 200 || patched) {
                        return;
                    }

                    window.setTimeout(waitForFbq, 50);
                })();
            })(window);
        </script>
        <?php
    }
}
add_action('wp_head', 'bornado_print_meta_pixel_dedupe_guard', 0);
