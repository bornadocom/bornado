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

    function inferCountryFromPhone(phone) {
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

    function buildCountrySelect(selectedCountry) {
        var select = document.createElement("select");

        select.name = "bornado_phone_dial_code";
        select.className = "form-control bornado-phone-country-select";

        phoneCountries.forEach(function (country) {
            var option = document.createElement("option");
            option.value = String(country.dialCode || "");
            option.textContent = String(country.name || "") + " (" + String(country.dialCode || "") + ")";

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

        countrySelect = buildCountrySelect(inferCountryFromPhone(phoneInput.value) || defaultPhoneCountry);

        if (wrapper) {
            wrapper.insertBefore(countryLabel, phoneInput);
            wrapper.insertBefore(countrySelect, phoneInput);
        }

        function syncHelp() {
            var normalized = normalizePhone(phoneInput.value, countrySelect.value);

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
            var normalized = normalizePhone(phoneInput.value, countrySelect.value);
            if (normalized) {
                phoneInput.value = normalized;
            }
            syncHelp();
        }

        countrySelect.addEventListener("change", syncHelp);
        phoneInput.addEventListener("input", syncHelp);
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
