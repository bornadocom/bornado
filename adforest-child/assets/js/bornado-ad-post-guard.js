(function (window, document) {
    "use strict";

    var config = window.bornadoAdPostGuard || {};
    var storageKey = String(config.storageKey || "bornado:ad-post-draft");
    var rootCategoryNames = [
        "ad_post_category_select",
        "ad_post_child_category_select_1",
        "ad_post_child_category_select_2",
        "ad_post_child_category_select_3",
        "ad_post_child_category_select_4",
        "ad_post_child_category_select_5",
        "ad_post_child_category_select_6"
    ];
    var countryNames = [
        "ad_country",
        "ad_country_states",
        "ad_country_cities",
        "ad_country_towns"
    ];
    var phoneCountries = Array.isArray(config.phoneCountries) ? config.phoneCountries : [];
    var defaultPhoneCountry = config.defaultPhoneCountry && typeof config.defaultPhoneCountry === "object"
        ? config.defaultPhoneCountry
        : null;

    function isFormField(element) {
        return !!(
            element &&
            element.name &&
            /^(INPUT|SELECT|TEXTAREA)$/.test(element.tagName)
        );
    }

    function isIgnoredField(name, type) {
        return name === "security" ||
            name === "_wp_http_referer" ||
            type === "file" ||
            type === "submit" ||
            type === "button";
    }

    function readStoredDraft() {
        try {
            var raw = window.sessionStorage.getItem(storageKey);
            return raw ? JSON.parse(raw) : null;
        } catch (error) {
            return null;
        }
    }

    function writeStoredDraft(data) {
        try {
            window.sessionStorage.setItem(storageKey, JSON.stringify(data));
        } catch (error) {
            // Ignore storage failures.
        }
    }

    function clearStoredDraft() {
        try {
            window.sessionStorage.removeItem(storageKey);
        } catch (error) {
            // Ignore storage failures.
        }
    }

    function buildDraftSnapshot(form) {
        var snapshot = {};
        var formData = new window.FormData(form);

        formData.forEach(function (value, key) {
            var field = form.querySelector('[name="' + key.replace(/\\/g, "\\\\").replace(/"/g, '\\"') + '"]');
            var type = field && field.type ? String(field.type).toLowerCase() : "";

            if (isIgnoredField(key, type) || value instanceof window.File) {
                return;
            }

            if (Object.prototype.hasOwnProperty.call(snapshot, key)) {
                if (!Array.isArray(snapshot[key])) {
                    snapshot[key] = [snapshot[key]];
                }
                snapshot[key].push(String(value));
                return;
            }

            snapshot[key] = String(value);
        });

        return snapshot;
    }

    function saveDraft(form) {
        writeStoredDraft(buildDraftSnapshot(form));
    }

    function normaliseValues(value) {
        if (Array.isArray(value)) {
            return value.map(String);
        }

        if (typeof value === "undefined" || value === null) {
            return [];
        }

        return [String(value)];
    }

    function setControlValues(form, name, rawValue) {
        var selector = '[name="' + name.replace(/\\/g, "\\\\").replace(/"/g, '\\"') + '"]';
        var controls = form.querySelectorAll(selector);
        var values = normaliseValues(rawValue);

        if (!controls.length) {
            return false;
        }

        Array.prototype.forEach.call(controls, function (control) {
            var tagName = control.tagName;
            var type = control.type ? String(control.type).toLowerCase() : "";

            if (type === "checkbox" || type === "radio") {
                control.checked = values.indexOf(String(control.value)) !== -1;
                return;
            }

            if (tagName === "SELECT" && control.multiple) {
                Array.prototype.forEach.call(control.options, function (option) {
                    option.selected = values.indexOf(String(option.value)) !== -1;
                });
                return;
            }

            control.value = values.length ? values[0] : "";
        });

        return true;
    }

    function dispatchChange(control) {
        if (!control) {
            return;
        }

        control.dispatchEvent(new window.Event("change", { bubbles: true }));
        control.dispatchEvent(new window.Event("input", { bubbles: true }));
    }

    function waitForControl(form, name, timeoutMs) {
        return new window.Promise(function (resolve) {
            var startedAt = Date.now();
            var timer = window.setInterval(function () {
                var control = form.querySelector('[name="' + name.replace(/\\/g, "\\\\").replace(/"/g, '\\"') + '"]');
                if (control) {
                    window.clearInterval(timer);
                    resolve(control);
                    return;
                }

                if (Date.now() - startedAt >= timeoutMs) {
                    window.clearInterval(timer);
                    resolve(null);
                }
            }, 120);
        });
    }

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

    function getCountryByTermId(termId) {
        var normalized = String(termId || "");
        var match = null;

        phoneCountries.some(function (country) {
            if (String(country.termId || "") === normalized) {
                match = country;
                return true;
            }
            return false;
        });

        return match;
    }

    function ensurePhoneHint(input) {
        var hint = input.parentNode ? input.parentNode.querySelector(".bornado-phone-helper-text") : null;
        if (!hint && input.parentNode) {
            hint = document.createElement("small");
            hint.className = "bornado-phone-helper-text";
            input.parentNode.appendChild(hint);
        }

        return hint;
    }

    function getLocalPhoneExample() {
        return "9121234567";
    }

    function isolateInlineText(value) {
        return "\u2066" + String(value || "") + "\u2069";
    }

    function enhanceAdPostPhoneField(form) {
        var phoneInput = form.querySelector('input[name="ad_contact_number"]');
        var rootCountry = form.querySelector('select[name="ad_country"]');
        var phoneHint;

        if (!phoneInput) {
            return;
        }

        phoneHint = ensurePhoneHint(phoneInput);
        phoneInput.setAttribute("dir", "ltr");

        function currentCountry() {
            if (!rootCountry) {
                return defaultPhoneCountry;
            }

            return getCountryByTermId(rootCountry.value) || defaultPhoneCountry;
        }

        function syncPhoneHelp() {
            var country = currentCountry();
            var normalized = normalizePhone(phoneInput.value, country && country.dialCode ? country.dialCode : "");

            phoneInput.setAttribute("placeholder", getLocalPhoneExample());

            if (!phoneHint) {
                return;
            }

            if (!phoneInput.value.trim()) {
                phoneHint.textContent = country && country.dialCode
                    ? getI18n("countryApplied") + " " + getI18n("localPhoneExample") + ": " + isolateInlineText(getLocalPhoneExample()) + " | " + getI18n("phoneExample") + ": " + isolateInlineText(String(country.dialCode || "") + getLocalPhoneExample())
                    : getI18n("selectCountry");
                return;
            }

            phoneHint.textContent = normalized ? "" : getI18n("invalidPhone");
        }

        function applyNormalization() {
            var country = currentCountry();
            var normalized = normalizePhone(phoneInput.value, country && country.dialCode ? country.dialCode : "");

            if (normalized) {
                phoneInput.value = normalized;
            }

            syncPhoneHelp();
        }

        if (rootCountry) {
            rootCountry.addEventListener("change", function () {
                window.setTimeout(syncPhoneHelp, 200);
            });
        }

        phoneInput.addEventListener("input", syncPhoneHelp);
        phoneInput.addEventListener("blur", applyNormalization);
        form.addEventListener("submit", applyNormalization, true);

        syncPhoneHelp();
    }

    function restoreImmediateFields(form, draft, excludedNames) {
        Object.keys(draft).forEach(function (name) {
            if (excludedNames.indexOf(name) !== -1) {
                return;
            }

            setControlValues(form, name, draft[name]);
        });
    }

    function restoreSelectChain(form, names, draft) {
        var sequence = window.Promise.resolve();

        names.forEach(function (name, index) {
            var value = draft[name];
            if (!value) {
                return;
            }

            sequence = sequence.then(function () {
                if (index === 0) {
                    var firstControl = form.querySelector('[name="' + name + '"]');
                    if (!firstControl) {
                        return null;
                    }

                    firstControl.value = String(value);
                    dispatchChange(firstControl);
                    return firstControl;
                }

                return waitForControl(form, name, 5000).then(function (control) {
                    if (!control) {
                        return null;
                    }

                    control.value = String(value);
                    dispatchChange(control);
                    return control;
                });
            }).then(function () {
                return new window.Promise(function (resolve) {
                    window.setTimeout(resolve, 220);
                });
            });
        });

        return sequence;
    }

    function maybeValidateWithParsley(form) {
        if (!window.jQuery || typeof window.jQuery !== "function") {
            return form.checkValidity();
        }

        var $ = window.jQuery;
        if (!$.fn || typeof $.fn.parsley !== "function") {
            return form.checkValidity();
        }

        var parsleyForm = $(form).parsley();
        if (parsleyForm.isValid()) {
            return true;
        }

        parsleyForm.validate();
        return false;
    }

    function isSuccessfulAdPostingResponse(responseText) {
        var response = String(responseText || "").trim();
        if (!response) {
            return false;
        }

        return /^https?:\/\//i.test(response) ||
            /^\/(?!\/)/.test(response) ||
            response.indexOf("?p=") !== -1 ||
            response.indexOf("&p=") !== -1;
    }

    function bindAjaxSuccessCleanup() {
        if (!window.jQuery || typeof window.jQuery !== "function") {
            return;
        }

        var $ = window.jQuery;
        $(document).ajaxSuccess(function (_event, xhr, settings) {
            if (!settings) {
                return;
            }

            var isAdPostingRequest = false;
            if (typeof settings.data === "string") {
                isAdPostingRequest = settings.data.indexOf("action=sb_ad_posting") !== -1;
            } else if (settings.data && typeof settings.data === "object") {
                isAdPostingRequest = settings.data.action === "sb_ad_posting";
            }

            if (!isAdPostingRequest) {
                return;
            }

            if (isSuccessfulAdPostingResponse(xhr && xhr.responseText)) {
                clearStoredDraft();
            }
        });
    }

    function bindSubmitGuard(form) {
        form.setAttribute("method", "post");
        form.setAttribute("novalidate", "novalidate");

        form.addEventListener("submit", function (event) {
            saveDraft(form);
            event.preventDefault();

            if (!maybeValidateWithParsley(form)) {
                return;
            }
        }, true);
    }

    function bindDraftPersistence(form) {
        document.addEventListener("input", function (event) {
            if (!form.contains(event.target) || !isFormField(event.target)) {
                return;
            }

            saveDraft(form);
        }, true);

        document.addEventListener("change", function (event) {
            if (!form.contains(event.target) || !isFormField(event.target)) {
                return;
            }

            saveDraft(form);
        }, true);
    }

    function restoreDraft(form) {
        var draft = readStoredDraft();
        var excludedNames = rootCategoryNames.concat(countryNames);

        if (!draft || typeof draft !== "object") {
            return;
        }

        restoreImmediateFields(form, draft, excludedNames);

        restoreSelectChain(form, rootCategoryNames, draft)
            .then(function () {
                return restoreSelectChain(form, countryNames, draft);
            })
            .then(function () {
                window.setTimeout(function () {
                    restoreImmediateFields(form, draft, []);
                }, 400);

                window.setTimeout(function () {
                    restoreImmediateFields(form, draft, []);
                }, 1400);

                window.setTimeout(function () {
                    saveDraft(form);
                }, 1800);
            });
    }

    function init() {
        var form = document.getElementById("adforest-ad-post-form");
        if (!form) {
            return;
        }

        bindSubmitGuard(form);
        bindDraftPersistence(form);
        bindAjaxSuccessCleanup();
        restoreDraft(form);
        enhanceAdPostPhoneField(form);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})(window, document);
