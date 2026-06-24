(function (window, document) {
    "use strict";

    var config = window.BornadoGlobalAdLocation || {};
    if (!config.ajaxUrl || !config.actions) {
        return;
    }

    var requestCache = {};
    var preloadedCountries = Array.isArray(config.countries) ? config.countries.slice() : [];

    function text(key) {
        return config.i18n && config.i18n[key] ? String(config.i18n[key]) : key;
    }

    function debounce(fn, wait) {
        var timeout = 0;
        return function () {
            var args = arguments;
            window.clearTimeout(timeout);
            timeout = window.setTimeout(function () {
                fn.apply(null, args);
            }, wait);
        };
    }

    function request(action, payload) {
        var cacheKey = action + "::" + JSON.stringify(payload || {});
        if (requestCache[cacheKey]) {
            return requestCache[cacheKey];
        }

        var body = new window.URLSearchParams();
        body.append("action", action);
        body.append("nonce", String(config.nonce || ""));

        Object.keys(payload || {}).forEach(function (key) {
            body.append(key, payload[key] == null ? "" : String(payload[key]));
        });

        requestCache[cacheKey] = window.fetch(config.ajaxUrl, {
            method: "POST",
            credentials: "same-origin",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded; charset=UTF-8"
            },
            body: body.toString()
        }).then(function (response) {
            return response.json();
        }).catch(function (error) {
            delete requestCache[cacheKey];
            throw error;
        });

        return requestCache[cacheKey];
    }

    function ensureOption(select, value, label) {
        var existing;
        if (!select) {
            return;
        }

        existing = Array.prototype.find.call(select.options, function (option) {
            return String(option.value) === String(value);
        });

        if (!existing) {
            existing = document.createElement("option");
            existing.value = String(value);
            existing.textContent = String(label || value);
            select.appendChild(existing);
        }
    }

    function hideLegacyRows(rootSelect) {
        ["ad_country", "ad_country_states", "ad_country_cities", "ad_country_towns"].forEach(function (id) {
            var field = document.getElementById(id);
            var row = field ? field.closest(".row") : null;
            var column = field ? field.closest(".col-md-6, .col-lg-6, .col-sm-6, .col-xs-12") : null;

            if (column) {
                column.classList.add("bornado-geo-legacy-hidden");
            }
            if (row) {
                row.classList.add("bornado-geo-legacy-row");
            }
        });

        if (rootSelect) {
            rootSelect.classList.add("bornado-geo-legacy-hidden-control");
        }
    }

    function createHiddenInput(form, name) {
        var input = form.querySelector('input[name="' + name + '"]');
        if (!input) {
            input = document.createElement("input");
            input.type = "hidden";
            input.name = name;
            form.appendChild(input);
        }
        return input;
    }

    function setHidden(form, name, value) {
        createHiddenInput(form, name).value = value == null ? "" : String(value);
    }

    function buildMarkup() {
        var wrapper = document.createElement("div");
        wrapper.className = "bornado-global-location";
        wrapper.innerHTML = '' +
            '<div class="bornado-global-location__card">' +
                '<div class="bornado-global-location__grid">' +
                    '<div class="bornado-global-location__field" data-field="country">' +
                        '<label for="bornado-geo-country-search">' + text("countryLabel") + '</label>' +
                        '<div class="bornado-global-location__input-wrap">' +
                            '<input id="bornado-geo-country-search" class="bornado-global-location__input" type="text" name="bornado_geo_country_search" autocomplete="off" placeholder="' + text("countryPlaceholder") + '">' +
                            '<div class="bornado-global-location__results" data-results="country"></div>' +
                        '</div>' +
                        '<p class="bornado-global-location__summary" data-summary="country"></p>' +
                    '</div>' +
                    '<div class="bornado-global-location__field" data-field="city">' +
                        '<label for="bornado-geo-city-search">' + text("cityLabel") + ' <span class="bornado-global-location__optional">(' + text("optionalCity") + ')</span></label>' +
                        '<div class="bornado-global-location__input-wrap">' +
                            '<input id="bornado-geo-city-search" class="bornado-global-location__input" type="text" name="bornado_geo_city_search" autocomplete="off" placeholder="' + text("cityDisabled") + '" disabled>' +
                            '<div class="bornado-global-location__results" data-results="city"></div>' +
                        '</div>' +
                        '<p class="bornado-global-location__summary" data-summary="city"></p>' +
                    '</div>' +
                '</div>' +
            '</div>';
        return wrapper;
    }

    function renderResults(container, items, onChoose, formatLabel) {
        container.innerHTML = "";

        if (!items.length) {
            container.innerHTML = '<div class="bornado-global-location__empty">' + text("noResults") + '</div>';
            container.classList.add("is-open");
            return;
        }

        items.forEach(function (item) {
            var button = document.createElement("button");
            button.type = "button";
            button.className = "bornado-global-location__result";
            button.textContent = formatLabel(item);
            button.addEventListener("click", function () {
                onChoose(item);
                container.classList.remove("is-open");
            });
            container.appendChild(button);
        });

        container.classList.add("is-open");
    }

    function filterCountries(query) {
        var normalized = String(query || "").trim().toLowerCase();
        var items = preloadedCountries.slice();

        if (normalized === "") {
            return items;
        }

        return items.filter(function (country) {
            var fa = String(country.nameFa || "").toLowerCase();
            var en = String(country.nameEn || "").toLowerCase();
            var iso = String(country.iso2 || "").toLowerCase();
            return fa.indexOf(normalized) !== -1 || en.indexOf(normalized) !== -1 || iso.indexOf(normalized) !== -1;
        });
    }

    function syncLegacyCountry(rootSelect, hiddenCountryId, country) {
        if (!rootSelect || !country || !country.legacyTermId) {
            return;
        }

        ensureOption(rootSelect, country.legacyTermId, country.nameFa || country.nameEn || country.iso2);
        rootSelect.value = String(country.legacyTermId);
        rootSelect.dispatchEvent(new window.Event("change", { bubbles: true }));

        if (hiddenCountryId) {
            hiddenCountryId.value = String(country.legacyTermId);
        }

        ["ad_country_states", "ad_country_cities", "ad_country_towns"].forEach(function (id) {
            var select = document.getElementById(id);
            if (select) {
                select.value = "";
                select.dispatchEvent(new window.Event("change", { bubbles: true }));
            }
        });
    }

    function applyCountrySelection(form, rootSelect, hiddenCountryId, summaryNode, cityInput, state, country) {
        state.country = country;
        state.city = null;

        summaryNode.textContent = country ? text("selectedCountry") + ": " + (country.nameFa || country.nameEn || country.iso2) : "";
        cityInput.disabled = !country;
        cityInput.placeholder = country ? text("cityPlaceholder") : text("cityDisabled");
        cityInput.value = "";

        setHidden(form, "bornado_geo_country_iso2", country ? country.iso2 : "");
        setHidden(form, "bornado_geo_country_geoname_id", country ? country.geonameId : "");
        setHidden(form, "bornado_geo_country_name_fa", country ? country.nameFa : "");
        setHidden(form, "bornado_geo_country_name_en", country ? country.nameEn : "");
        setHidden(form, "bornado_geo_country_slug_candidate", country ? country.slugCandidate : "");
        setHidden(form, "bornado_geo_country_phone_dial_code", country ? country.phoneDialCode : "");
        setHidden(form, "bornado_geo_country_currency_code", country ? country.currencyCode : "");
        setHidden(form, "bornado_geo_root_term_id", country ? country.legacyTermId : "");

        setHidden(form, "bornado_geo_city_geoname_id", "");
        setHidden(form, "bornado_geo_city_name_fa", "");
        setHidden(form, "bornado_geo_city_name_en", "");
        setHidden(form, "bornado_geo_city_slug_candidate", "");
        setHidden(form, "bornado_geo_city_latitude", "");
        setHidden(form, "bornado_geo_city_longitude", "");

        syncLegacyCountry(rootSelect, hiddenCountryId, country);

        if (country && country.iso2) {
            request(config.actions.citySearch, {
                country_iso2: country.iso2,
                query: ""
            }).catch(function () {});
        }
    }

    function applyCitySelection(form, summaryNode, state, city) {
        state.city = city;
        summaryNode.textContent = city ? text("selectedCity") + ": " + (city.nameFa || city.nameEn) : "";

        setHidden(form, "bornado_geo_city_geoname_id", city ? city.geonameId : "");
        setHidden(form, "bornado_geo_city_name_fa", city ? city.nameFa : "");
        setHidden(form, "bornado_geo_city_name_en", city ? city.nameEn : "");
        setHidden(form, "bornado_geo_city_slug_candidate", city ? city.slugCandidate : "");
        setHidden(form, "bornado_geo_city_latitude", city ? city.latitude : "");
        setHidden(form, "bornado_geo_city_longitude", city ? city.longitude : "");
    }

    function init() {
        var form = document.getElementById("adforest-ad-post-form");
        var rootSelect = document.getElementById("ad_country");
        var hiddenCountryId = document.getElementById("ad_country_id");
        var insertionPoint;
        var ui;
        var countryInput;
        var cityInput;
        var countryResults;
        var cityResults;
        var countrySummary;
        var citySummary;
        var state = { country: null, city: null };

        if (!form || !rootSelect) {
            return;
        }

        hideLegacyRows(rootSelect);

        insertionPoint = rootSelect.closest(".row");
        ui = buildMarkup();
        if (insertionPoint && insertionPoint.parentNode) {
            insertionPoint.parentNode.insertBefore(ui, insertionPoint);
        } else {
            form.insertBefore(ui, form.firstChild);
        }

        countryInput = ui.querySelector("#bornado-geo-country-search");
        cityInput = ui.querySelector("#bornado-geo-city-search");
        countryResults = ui.querySelector('[data-results="country"]');
        cityResults = ui.querySelector('[data-results="city"]');
        countrySummary = ui.querySelector('[data-summary="country"]');
        citySummary = ui.querySelector('[data-summary="city"]');
        countryInput.required = true;

        function syncCountryValidity() {
            if (state.country && state.country.iso2) {
                countryInput.setCustomValidity("");
                return;
            }

            countryInput.setCustomValidity(text("countryLabel"));
        }

        function searchCountries(query) {
            countryResults.innerHTML = '<div class="bornado-global-location__empty">' + text("loading") + '</div>';
            countryResults.classList.add("is-open");
            renderResults(countryResults, filterCountries(query), function (country) {
                countryInput.value = country.nameFa || country.nameEn || country.iso2;
                applyCountrySelection(form, rootSelect, hiddenCountryId, countrySummary, cityInput, state, country);
                applyCitySelection(form, citySummary, state, null);
                syncCountryValidity();
            }, function (country) {
                return country.nameFa + (country.nameEn ? " (" + country.nameEn + ")" : "");
            });
        }

        function searchCities(query) {
            if (!state.country || !state.country.iso2) {
                return;
            }

            cityResults.innerHTML = '<div class="bornado-global-location__empty">' + text("loading") + '</div>';
            cityResults.classList.add("is-open");

            request(config.actions.citySearch, {
                country_iso2: state.country.iso2,
                query: query || ""
            }).then(function (response) {
                var items = response && response.success && response.data && Array.isArray(response.data.items)
                    ? response.data.items
                    : [];

                renderResults(cityResults, items, function (city) {
                    cityInput.value = city.nameFa || city.nameEn;
                    applyCitySelection(form, citySummary, state, city);
                }, function (city) {
                    return city.nameFa + (city.nameEn ? " (" + city.nameEn + ")" : "");
                });
            }).catch(function () {
                renderResults(cityResults, [], function () {}, function () { return ""; });
            });
        }

        countryInput.addEventListener("focus", function () {
            searchCountries(countryInput.value);
        });
        countryInput.addEventListener("input", function () {
            state.country = null;
            countrySummary.textContent = "";
            applyCitySelection(form, citySummary, state, null);
            state.city = null;
            cityInput.value = "";
            cityInput.disabled = true;
            cityInput.placeholder = text("cityDisabled");
            setHidden(form, "bornado_geo_country_iso2", "");
            setHidden(form, "bornado_geo_country_geoname_id", "");
            setHidden(form, "bornado_geo_country_name_fa", "");
            setHidden(form, "bornado_geo_country_name_en", "");
            setHidden(form, "bornado_geo_country_slug_candidate", "");
            setHidden(form, "bornado_geo_country_phone_dial_code", "");
            setHidden(form, "bornado_geo_country_currency_code", "");
            setHidden(form, "bornado_geo_root_term_id", "");
            if (rootSelect) {
                rootSelect.value = "";
            }
            if (hiddenCountryId) {
                hiddenCountryId.value = "";
            }
            syncCountryValidity();
        });
        countryInput.addEventListener("input", debounce(function () {
            searchCountries(countryInput.value);
        }, 180));

        cityInput.addEventListener("focus", function () {
            if (!cityInput.disabled) {
                searchCities(cityInput.value);
            }
        });
        cityInput.addEventListener("input", debounce(function () {
            if (!cityInput.disabled) {
                searchCities(cityInput.value);
            }
        }, 180));

        document.addEventListener("click", function (event) {
            if (!ui.contains(event.target)) {
                countryResults.classList.remove("is-open");
                cityResults.classList.remove("is-open");
            }
        });

        form.addEventListener("submit", function () {
            syncCountryValidity();
            if (state.country) {
                syncLegacyCountry(rootSelect, hiddenCountryId, state.country);
            }
        }, true);

        if (config.selection && config.selection.country_iso2) {
            applyCountrySelection(form, rootSelect, hiddenCountryId, countrySummary, cityInput, state, {
                iso2: config.selection.country_iso2,
                geonameId: config.selection.country_geoname_id || "",
                nameFa: config.selection.country_name_fa || "",
                nameEn: config.selection.country_name_en || "",
                slugCandidate: config.selection.country_slug_candidate || "",
                phoneDialCode: config.selection.country_phone_dial_code || "",
                currencyCode: config.selection.country_currency_code || "",
                legacyTermId: config.selection.root_term_id || ""
            });
            countryInput.value = config.selection.country_name_fa || config.selection.country_name_en || "";

            if (config.selection.city_geoname_id) {
                applyCitySelection(form, citySummary, state, {
                    geonameId: config.selection.city_geoname_id || "",
                    nameFa: config.selection.city_name_fa || "",
                    nameEn: config.selection.city_name_en || "",
                    slugCandidate: config.selection.city_slug_candidate || "",
                    latitude: config.selection.city_latitude || "",
                    longitude: config.selection.city_longitude || ""
                });
                cityInput.value = config.selection.city_name_fa || config.selection.city_name_en || "";
            }
        }

        syncCountryValidity();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})(window, document);
