(function (window, document) {
    "use strict";

    var config = window.bornadoProfilePhoneGuard || {};
    var phoneCountries = Array.isArray(config.phoneCountries) ? config.phoneCountries : [];
    var defaultPhoneCountry = config.defaultPhoneCountry && typeof config.defaultPhoneCountry === "object"
        ? config.defaultPhoneCountry
        : null;

    function getI18n(key) {
        return config.i18n && config.i18n[key] ? String(config.i18n[key]) : key;
    }

    function sanitizeDialCode(value) {
        var cleaned = String(value || "").trim().replace(/[^\d+]/g, "");
        if (!cleaned) {
            return "";
        }

        if (cleaned.indexOf("00") === 0) {
            cleaned = "+" + cleaned.slice(2);
        } else if (cleaned.charAt(0) !== "+") {
            cleaned = "+" + cleaned.replace(/^\++/, "");
        }

        cleaned = "+" + cleaned.replace(/[^\d]/g, "");

        return /^\+\d{1,4}$/.test(cleaned) ? cleaned : "";
    }

    function normalizePhone(value, dialCode) {
        var raw = String(value || "").trim();
        var normalizedDialCode = sanitizeDialCode(dialCode);
        var cleaned;
        var digitsOnly;
        var dialDigits;

        if (!raw) {
            return "";
        }

        cleaned = raw.replace(/[^\d+]/g, "");
        if (!cleaned) {
            return "";
        }

        if (cleaned.indexOf("00") === 0) {
            cleaned = "+" + cleaned.slice(2);
        }

        if (cleaned.charAt(0) === "+") {
            cleaned = "+" + cleaned.replace(/[^\d]/g, "");
            return /^\+\d{8,16}$/.test(cleaned) ? cleaned : "";
        }

        if (!normalizedDialCode) {
            return "";
        }

        digitsOnly = cleaned.replace(/[^\d]/g, "");
        dialDigits = normalizedDialCode.replace(/[^\d]/g, "");

        if (!digitsOnly || !dialDigits) {
            return "";
        }

        if (digitsOnly.indexOf(dialDigits) === 0) {
            cleaned = "+" + digitsOnly;
        } else {
            cleaned = "+" + dialDigits + digitsOnly.replace(/^0+/, "");
        }

        return /^\+\d{8,16}$/.test(cleaned) ? cleaned : "";
    }

    function hasExplicitDialCode(value) {
        var cleaned = String(value || "").trim().replace(/[^\d+]/g, "");
        return cleaned.indexOf("+") === 0 || cleaned.indexOf("00") === 0;
    }

    function getResolvedDefaultPhoneCountry() {
        var runtimeCountry = window.BornadoPhoneCountryPickerResolvedCountry
            && typeof window.BornadoPhoneCountryPickerResolvedCountry === "object"
            ? window.BornadoPhoneCountryPickerResolvedCountry
            : null;

        if (runtimeCountry && runtimeCountry.dialCode) {
            return runtimeCountry;
        }

        runtimeCountry = resolveBrowserSuggestedCountry();
        if (runtimeCountry && runtimeCountry.dialCode) {
            return runtimeCountry;
        }

        return defaultPhoneCountry && defaultPhoneCountry.dialCode ? defaultPhoneCountry : null;
    }

    function getCountryByCountryCode(countryCode) {
        var normalized = String(countryCode || "").trim().toUpperCase();
        var match = null;

        if (!normalized) {
            return null;
        }

        phoneCountries.some(function (country) {
            if (String(country && country.countryCode ? country.countryCode : "").trim().toUpperCase() === normalized) {
                match = country;
                return true;
            }

            return false;
        });

        return match;
    }

    function countryCodeFromLocale(locale) {
        var normalized = String(locale || "").trim().replace(/_/g, "-");
        var parts = normalized.split("-").filter(Boolean);
        var languageMap = {
            fa: "IR",
            ar: "AE",
            en: "GB"
        };

        if (parts.length > 1 && /^[A-Za-z]{2}$/.test(parts[parts.length - 1])) {
            return String(parts[parts.length - 1]).toUpperCase();
        }

        if (parts.length && languageMap[parts[0].toLowerCase()]) {
            return languageMap[parts[0].toLowerCase()];
        }

        return "";
    }

    function countryCodeFromTimezone(timezone) {
        var timezoneMap = {
            "Europe/London": "GB",
            "Asia/Tehran": "IR",
            "Asia/Dubai": "AE",
            "Asia/Baku": "AZ",
            "Europe/Berlin": "DE",
            "Europe/Paris": "FR",
            "Europe/Amsterdam": "NL",
            "Europe/Stockholm": "SE",
            "Europe/Oslo": "NO",
            "Europe/Copenhagen": "DK",
            "Europe/Brussels": "BE",
            "Europe/Vienna": "AT",
            "Europe/Zurich": "CH",
            "America/Toronto": "CA",
            "America/Vancouver": "CA",
            "America/New_York": "US",
            "America/Los_Angeles": "US",
            "Australia/Sydney": "AU"
        };

        return timezoneMap[String(timezone || "").trim()] || "";
    }

    function resolveBrowserSuggestedCountry() {
        var locales = [];
        var timezone = "";
        var idx;
        var country;

        if (document.documentElement && document.documentElement.lang) {
            locales.push(String(document.documentElement.lang));
        }

        if (Array.isArray(navigator.languages)) {
            navigator.languages.forEach(function (locale) {
                if (locale) {
                    locales.push(String(locale));
                }
            });
        }

        if (navigator.language) {
            locales.push(String(navigator.language));
        }

        for (idx = 0; idx < locales.length; idx += 1) {
            country = getCountryByCountryCode(countryCodeFromLocale(locales[idx]));
            if (country) {
                return country;
            }
        }

        try {
            timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || "";
        } catch (_error) {
            timezone = "";
        }

        return getCountryByCountryCode(countryCodeFromTimezone(timezone));
    }

    function digitsOnly(value) {
        return String(value || "").replace(/[^\d]/g, "");
    }

    function inferCountryFromPhone(phone) {
        if (!hasExplicitDialCode(phone)) {
            return null;
        }

        var normalized = normalizePhone(phone, "");
        var match = null;

        if (!normalized) {
            return null;
        }

        phoneCountries.forEach(function (country) {
            var dialCode = sanitizeDialCode(country && country.dialCode ? country.dialCode : "");
            if (!dialCode) {
                return;
            }

            if (normalized.indexOf(dialCode) === 0) {
                if (!match || String(dialCode).length > String(match.dialCode || "").length) {
                    match = country;
                }
            }
        });

        return match;
    }

    function updateDialCodeSelect(select, dialCode) {
        var normalizedDialCode = sanitizeDialCode(dialCode);

        if (!select || !normalizedDialCode) {
            return "";
        }

        if (sanitizeDialCode(select.value) === normalizedDialCode) {
            return normalizedDialCode;
        }

        select.value = normalizedDialCode;
        select.dispatchEvent(new Event("change", { bubbles: true }));

        return normalizedDialCode;
    }

    function getPickerDialCode(select) {
        var root = select && select.closest ? select.closest(".bpcp") : null;
        return root && root.dataset && root.dataset.currentDialCode
            ? sanitizeDialCode(root.dataset.currentDialCode)
            : "";
    }

    function rememberPhoneSyncState(input, mode, dialCode, localDigits) {
        if (!input || !input.dataset) {
            return;
        }

        input.dataset.phoneSyncMode = String(mode || "");
        input.dataset.phoneSyncDialCode = sanitizeDialCode(dialCode || "");
        input.dataset.phoneSyncLocalDigits = String(localDigits || "");
    }

    function resolveLocalDigitsForSync(input, dialCode) {
        var raw = input ? String(input.value || "").trim() : "";
        var normalizedDialCode = sanitizeDialCode(dialCode || "");
        var dialDigits = digitsOnly(normalizedDialCode);
        var cleaned = raw.replace(/[^\d+]/g, "");
        var rawDigits = digitsOnly(cleaned);
        var storedLocalDigits = input && input.dataset ? String(input.dataset.phoneSyncLocalDigits || "") : "";
        var storedDialDigits = input && input.dataset ? digitsOnly(input.dataset.phoneSyncDialCode || "") : "";

        if (storedLocalDigits) {
            return storedLocalDigits;
        }

        if (!rawDigits) {
            return "";
        }

        if (cleaned.charAt(0) === "+" && storedDialDigits && rawDigits.indexOf(storedDialDigits) === 0) {
            return rawDigits.slice(storedDialDigits.length);
        }

        if (cleaned.charAt(0) === "+" && dialDigits && rawDigits.indexOf(dialDigits) === 0) {
            return rawDigits.slice(dialDigits.length);
        }

        return rawDigits.replace(/^0+/, "");
    }

    function rewritePhoneForSelectedDial(input, dialCode) {
        var normalizedDialCode = sanitizeDialCode(dialCode);
        var dialDigits = digitsOnly(normalizedDialCode);
        var localDigits = resolveLocalDigitsForSync(input, normalizedDialCode);

        if (!input || !normalizedDialCode || !dialDigits || !localDigits) {
            return false;
        }

        input.value = "+" + dialDigits + localDigits;
        rememberPhoneSyncState(input, "auto", normalizedDialCode, localDigits);
        return true;
    }

    function syncPhoneCountrySelection(select, phoneValue, applyDefaultFallback) {
        var inferredCountry = inferCountryFromPhone(phoneValue);
        var resolvedDefaultPhoneCountry = getResolvedDefaultPhoneCountry();

        if (select && inferredCountry && inferredCountry.dialCode) {
            return updateDialCodeSelect(select, inferredCountry.dialCode);
        }

        if (select && applyDefaultFallback && !sanitizeDialCode(select.value) && resolvedDefaultPhoneCountry && resolvedDefaultPhoneCountry.dialCode) {
            return updateDialCodeSelect(select, resolvedDefaultPhoneCountry.dialCode);
        }

        return select ? (getPickerDialCode(select) || sanitizeDialCode(select.value)) : "";
    }

    function formatCountryOptionLabel(country) {
        var name = String(
            country && (country.displayNameFa || country.name || country.displayNameEn)
                ? (country.displayNameFa || country.name || country.displayNameEn)
                : ""
        ).trim();
        var dialCode = String(country && country.dialCode ? country.dialCode : "").trim();

        if (!dialCode) {
            return name;
        }

        return name + " (\u2066" + dialCode + "\u2069)";
    }

    function decorateCountryOption(option, country) {
        if (!option || !country) {
            return;
        }

        option.dataset.termId = String(country.termId || "");
        option.dataset.countryCode = String(country.countryCode || "");
        option.dataset.displayNameFa = String(country.displayNameFa || country.name || "");
        option.dataset.displayNameEn = String(country.displayNameEn || "");
        option.dataset.searchTokens = String(country.searchTokens || "");
    }

    function buildCountrySelect(selectedCountry) {
        var select = document.createElement("select");

        select.name = "bornado_phone_dial_code";
        select.className = "form-control bornado-phone-country-select";

        phoneCountries.forEach(function (country) {
            var option = document.createElement("option");
            option.value = String(country.dialCode || "");
            option.textContent = formatCountryOptionLabel(country);
            decorateCountryOption(option, country);

            if (selectedCountry && String(selectedCountry.dialCode || "") === String(country.dialCode || "")) {
                option.selected = true;
            }

            select.appendChild(option);
        });

        return select;
    }

    function init() {
        var form = document.getElementById("sb_update_profile");
        var phoneInput = document.getElementById("sb_user_contact");
        var small;
        var wrapper;
        var countryLabel;
        var countrySelect;

        if (!form || !phoneInput || !phoneCountries.length) {
            return;
        }

        wrapper = phoneInput.parentNode;
        small = wrapper ? wrapper.querySelector("small") : null;
        countryLabel = document.createElement("label");
        countryLabel.textContent = getI18n("countryLabel");

        countrySelect = buildCountrySelect(inferCountryFromPhone(phoneInput.value) || getResolvedDefaultPhoneCountry());

        if (wrapper) {
            wrapper.insertBefore(countryLabel, phoneInput);
            wrapper.insertBefore(countrySelect, phoneInput);
        }

        function syncHelp() {
            var normalized = normalizePhone(phoneInput.value, getPickerDialCode(countrySelect) || countrySelect.value);

            phoneInput.setAttribute("placeholder", "9121234567");

            if (!small) {
                return;
            }

            if (!phoneInput.value.trim()) {
                small.textContent = getI18n("phoneExample") + ": " + String(countrySelect.value || "") + "9121234567";
                return;
            }

            small.textContent = normalized
                ? getI18n("phoneExample") + ": " + normalized
                : getI18n("invalidPhone");
        }

        function applyNormalization() {
            var wasExplicit = hasExplicitDialCode(phoneInput.value);
            syncPhoneCountrySelection(countrySelect, phoneInput.value, true);
            var normalized = normalizePhone(phoneInput.value, getPickerDialCode(countrySelect) || countrySelect.value);
            if (normalized) {
                phoneInput.value = normalized;
                rememberPhoneSyncState(
                    phoneInput,
                    wasExplicit ? "explicit" : "auto",
                    getPickerDialCode(countrySelect) || countrySelect.value,
                    resolveLocalDigitsForSync(phoneInput, getPickerDialCode(countrySelect) || countrySelect.value)
                );
            }
            syncHelp();
        }

        syncPhoneCountrySelection(countrySelect, phoneInput.value, true);
        form.addEventListener("bpcp:change", function (event) {
            var dialCode = event && event.detail && event.detail.dialCode
                ? event.detail.dialCode
                : (getPickerDialCode(countrySelect) || countrySelect.value);

            if (String(phoneInput.value || "").trim() && (!hasExplicitDialCode(phoneInput.value) || String(phoneInput.dataset.phoneSyncMode || "") === "auto")) {
                rewritePhoneForSelectedDial(phoneInput, dialCode);
            }

            syncHelp();
        });
        countrySelect.addEventListener("change", function () {
            if (String(phoneInput.value || "").trim() && (!hasExplicitDialCode(phoneInput.value) || String(phoneInput.dataset.phoneSyncMode || "") === "auto")) {
                rewritePhoneForSelectedDial(phoneInput, getPickerDialCode(countrySelect) || countrySelect.value);
            }

            syncHelp();
        });
        phoneInput.addEventListener("input", function () {
            if (hasExplicitDialCode(phoneInput.value)) {
                rememberPhoneSyncState(phoneInput, "explicit", getPickerDialCode(countrySelect) || countrySelect.value, "");
                syncPhoneCountrySelection(countrySelect, phoneInput.value, false);
                return;
            }

            rememberPhoneSyncState(
                phoneInput,
                "local",
                getPickerDialCode(countrySelect) || countrySelect.value,
                digitsOnly(phoneInput.value).replace(/^0+/, "")
            );
            syncHelp();
        });
        phoneInput.addEventListener("blur", applyNormalization);
        form.addEventListener("submit", applyNormalization, true);

        syncHelp();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})(window, document);
