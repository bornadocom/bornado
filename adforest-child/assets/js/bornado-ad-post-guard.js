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
    var contactMethodsConfig = config.contactMethods && typeof config.contactMethods === "object"
        ? config.contactMethods
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

    function escapeHtml(value) {
        return String(value || "")
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function hasCustomContactMethods() {
        return !!(contactMethodsConfig && contactMethodsConfig.enabled);
    }

    function getManagedFieldNames() {
        return hasCustomContactMethods() ? ["ad_contact_number", "sb_user_name"] : [];
    }

    function getContactMethodsFieldNames() {
        return hasCustomContactMethods() ? ["bornado_contact_methods[]", "bornado_contact_methods_version"] : [];
    }

    function getMethodIconClass(methodKey) {
        if (methodKey === "phone") {
            return "fas fa-phone-alt";
        }

        if (methodKey === "whatsapp") {
            return "fab fa-whatsapp";
        }

        if (methodKey === "email") {
            return "far fa-envelope";
        }

        return "far fa-comment-alt";
    }

    function syncManagedProfileFields(form) {
        var phoneInput;
        var nameInput;

        if (!hasCustomContactMethods()) {
            return;
        }

        phoneInput = form.querySelector('input[name="ad_contact_number"]');
        nameInput = form.querySelector('input[name="sb_user_name"]');

        if (phoneInput) {
            phoneInput.value = contactMethodsConfig.profilePhone ? String(contactMethodsConfig.profilePhone) : "";
        }

        if (nameInput) {
            nameInput.value = contactMethodsConfig.profileName ? String(contactMethodsConfig.profileName) : "";
        }
    }

    function hideManagedContactFields(form) {
        ["ad_contact_number", "sb_user_name"].forEach(function (fieldName) {
            var input = form.querySelector('[name="' + fieldName + '"]');
            var fieldBox;
            var column;

            if (!input) {
                return;
            }

            fieldBox = input.closest(".field-box");
            column = input.closest(".col-lg-6, .col-md-6, .col-sm-12, .col-12");

            if (fieldBox) {
                fieldBox.classList.add("bornado-contact-methods__managed-field");
            }

            if (column) {
                column.classList.add("bornado-contact-methods__managed-field");
            }
        });
    }

    function buildMethodCard(method) {
        var isEnabled = !!method.enabled;
        var checked = Array.isArray(contactMethodsConfig.selectedMethods) && contactMethodsConfig.selectedMethods.indexOf(method.key) !== -1;
        var statusClass = isEnabled ? "" : " is-disabled";
        var inputId = "bornado-contact-method-" + String(method.key || "").replace(/[^a-z0-9_-]/gi, "-").toLowerCase();
        var safeValue = method.value ? '<div class="bornado-contact-method-card__value">' + escapeHtml(method.value) + "</div>" : "";
        var safeStatusLabel = method.status_label ? escapeHtml(method.status_label) : "";
        var fallbackBadge = !isEnabled
            ? '<span class="bornado-contact-method-card__badge' + statusClass + '">' + escapeHtml((contactMethodsConfig.strings && contactMethodsConfig.strings.needVerification) || "نیاز به تایید") + "</span>"
            : "";
        var helpButton = "";

        if (!isEnabled && method.help_html) {
            helpButton = '' +
                '<div class="bornado-contact-method-help" data-help-wrap>' +
                    '<button type="button" class="bornado-contact-method-help__trigger" aria-label="' + escapeHtml((contactMethodsConfig.strings && contactMethodsConfig.strings.helpLabel) || "راهنمای فعال‌سازی") + '">' +
                        "?" +
                    "</button>" +
                    '<div class="bornado-contact-method-tooltip">' + method.help_html + "</div>" +
                "</div>";
        }

        return '' +
            '<div class="bornado-contact-method-card' + (!isEnabled ? " is-disabled" : "") + '" data-method-key="' + escapeHtml(method.key) + '">' +
                helpButton +
                '<input id="' + escapeHtml(inputId) + '" type="checkbox" name="bornado_contact_methods[]" value="' + escapeHtml(method.key) + '"' + (checked ? " checked" : "") + (isEnabled ? "" : " disabled") + ">" +
                '<label class="bornado-contact-method-card__content" for="' + escapeHtml(inputId) + '">' +
                    '<span class="bornado-contact-method-card__icon"><i class="' + escapeHtml(getMethodIconClass(method.key)) + '" aria-hidden="true"></i></span>' +
                    '<span class="bornado-contact-method-card__text">' +
                        '<span class="bornado-contact-method-card__label">' + escapeHtml(method.label) + "</span>" +
                        safeValue +
                        '<span class="bornado-contact-method-card__status">' +
                            (safeStatusLabel ? '<span class="bornado-contact-method-card__badge' + statusClass + '">' + safeStatusLabel + "</span>" : "") +
                            (!safeStatusLabel ? fallbackBadge : "") +
                        "</span>" +
                    "</span>" +
                "</label>" +
            "</div>";
    }

    function bindMethodHelpToggles(container) {
        var wraps = container.querySelectorAll("[data-help-wrap]");

        Array.prototype.forEach.call(wraps, function (wrap) {
            var trigger = wrap.querySelector(".bornado-contact-method-help__trigger");

            if (!trigger) {
                return;
            }

            trigger.addEventListener("click", function (event) {
                event.preventDefault();
                event.stopPropagation();
                Array.prototype.forEach.call(wraps, function (otherWrap) {
                    if (otherWrap !== wrap) {
                        otherWrap.classList.remove("is-open");
                        if (otherWrap.parentNode) {
                            otherWrap.parentNode.classList.remove("is-help-open");
                        }
                    }
                });
                wrap.classList.toggle("is-open");
                if (wrap.parentNode) {
                    wrap.parentNode.classList.toggle("is-help-open", wrap.classList.contains("is-open"));
                }
            });
        });

        document.addEventListener("click", function (event) {
            Array.prototype.forEach.call(wraps, function (wrap) {
                if (!wrap.contains(event.target)) {
                    wrap.classList.remove("is-open");
                    if (wrap.parentNode) {
                        wrap.parentNode.classList.remove("is-help-open");
                    }
                }
            });
        });
    }

    function renderContactMethodsUi(form) {
        var contactPane;
        var contactRow;
        var methods;
        var wrapper;
        var hiddenVersion;

        if (!hasCustomContactMethods()) {
            return false;
        }

        contactPane = document.getElementById("v-pills-contact");
        contactRow = contactPane ? contactPane.querySelector(".row") : null;
        methods = contactMethodsConfig.statusMap ? Object.keys(contactMethodsConfig.statusMap).map(function (key) {
            return contactMethodsConfig.statusMap[key];
        }) : [];

        if (!contactPane || !contactRow || !methods.length) {
            return false;
        }

        wrapper = document.createElement("div");
        wrapper.className = "col-12 bornado-contact-methods-col";
        wrapper.innerHTML = '' +
            '<div class="bornado-contact-methods">' +
                '<div class="bornado-contact-methods__header">' +
                    '<h3>' + escapeHtml(contactMethodsConfig.strings && contactMethodsConfig.strings.sectionTitle ? contactMethodsConfig.strings.sectionTitle : "روش های ارتباطی برای این آگهی") + "</h3>" +
                    '<p>' + escapeHtml(contactMethodsConfig.strings && contactMethodsConfig.strings.sectionHint ? contactMethodsConfig.strings.sectionHint : "") + "</p>" +
                "</div>" +
                '<div class="bornado-contact-methods__grid">' + methods.map(buildMethodCard).join("") + "</div>" +
            "</div>";

        contactRow.insertBefore(wrapper, contactRow.firstChild);

        hiddenVersion = document.createElement("input");
        hiddenVersion.type = "hidden";
        hiddenVersion.name = "bornado_contact_methods_version";
        hiddenVersion.value = "1";
        form.appendChild(hiddenVersion);

        bindMethodHelpToggles(wrapper);
        hideManagedContactFields(form);
        syncManagedProfileFields(form);
        return true;
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
        var excludedNames = rootCategoryNames
            .concat(countryNames)
            .concat(getManagedFieldNames())
            .concat(getContactMethodsFieldNames());

        if (!draft || typeof draft !== "object") {
            syncManagedProfileFields(form);
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

                syncManagedProfileFields(form);
            });
    }

    function bindTermsAgreementCheckbox(form) {
        var checkbox = form.querySelector(".skin-minimal.check-detail .pretty input#minimal-checkbox-1");
        var pretty;
        var label;

        if (!checkbox) {
            return;
        }

        pretty = checkbox.closest(".pretty");
        label = pretty ? pretty.querySelector(".state label") : null;

        if (label && !label.getAttribute("for")) {
            label.setAttribute("for", "minimal-checkbox-1");
        }
    }

    function isMobileViewport() {
        if (window.matchMedia && typeof window.matchMedia === "function") {
            return window.matchMedia("(max-width: 767px)").matches;
        }

        return window.innerWidth <= 767;
    }

    function isIOSWebKit() {
        var ua = window.navigator.userAgent || "";
        var platform = window.navigator.platform || "";
        var isIOSDevice = /iP(ad|hone|od)/i.test(ua) || (platform === "MacIntel" && window.navigator.maxTouchPoints > 1);

        return isIOSDevice && /WebKit/i.test(ua);
    }

    function syncIOSViewportState() {
        var root = document.documentElement;
        var viewport = window.visualViewport;
        var viewportWidth = viewport && viewport.width ? viewport.width : window.innerWidth;
        var viewportOffsetLeft = viewport && viewport.offsetLeft ? viewport.offsetLeft : 0;

        if (!isIOSWebKit()) {
            root.classList.remove("bornado-ios-ad-post");
            root.style.removeProperty("--bornado-ios-vw");
            root.style.removeProperty("--bornado-ios-offset-left");
            return;
        }

        root.classList.add("bornado-ios-ad-post");
        root.style.setProperty("--bornado-ios-vw", Math.max(0, Math.round(viewportWidth)) + "px");
        root.style.setProperty("--bornado-ios-offset-left", Math.max(0, Math.round(viewportOffsetLeft)) + "px");
    }

    function clampHorizontalScroll() {
        var scrollTop = window.pageYOffset || document.documentElement.scrollTop || document.body.scrollTop || 0;

        document.documentElement.scrollLeft = 0;
        document.body.scrollLeft = 0;

        if ((window.pageXOffset || 0) !== 0) {
            window.scrollTo(0, scrollTop);
        }
    }

    function bindMobileOverflowGuard(form) {
        var clampTimer = 0;
        var viewportTimer = 0;

        function runOnNextFrame(callback) {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(callback);
            });
        }

        function scheduleClamp(target) {
            window.clearTimeout(clampTimer);

            runOnNextFrame(function () {
                if (!isMobileViewport()) {
                    return;
                }

                clampHorizontalScroll();
            });

            clampTimer = window.setTimeout(function () {
                if (!isMobileViewport()) {
                    return;
                }

                clampHorizontalScroll();
            }, 180);
        }

        function scheduleViewportSync() {
            window.clearTimeout(viewportTimer);

            runOnNextFrame(function () {
                syncIOSViewportState();
                if (isMobileViewport()) {
                    clampHorizontalScroll();
                }
            });

            viewportTimer = window.setTimeout(function () {
                syncIOSViewportState();
                if (isMobileViewport()) {
                    clampHorizontalScroll();
                }
            }, 180);
        }

        function bindHorizontalPanLock() {
            var startX = 0;
            var startY = 0;
            var tracking = false;

            document.addEventListener("touchstart", function (event) {
                var touch;

                if (!isIOSWebKit() || !isMobileViewport() || !form.contains(event.target)) {
                    tracking = false;
                    return;
                }

                touch = event.touches && event.touches[0];
                if (!touch) {
                    tracking = false;
                    return;
                }

                startX = touch.clientX;
                startY = touch.clientY;
                tracking = true;
            }, { passive: true, capture: true });

            document.addEventListener("touchmove", function (event) {
                var touch;
                var deltaX;
                var deltaY;

                if (!tracking || !isIOSWebKit() || !isMobileViewport() || !form.contains(event.target)) {
                    return;
                }

                touch = event.touches && event.touches[0];
                if (!touch) {
                    return;
                }

                deltaX = touch.clientX - startX;
                deltaY = touch.clientY - startY;

                if (Math.abs(deltaX) > 8 && Math.abs(deltaX) > Math.abs(deltaY) + 4) {
                    event.preventDefault();
                    clampHorizontalScroll();
                }
            }, { passive: false, capture: true });

            document.addEventListener("touchend", function () {
                tracking = false;
            }, { passive: true, capture: true });

            document.addEventListener("touchcancel", function () {
                tracking = false;
            }, { passive: true, capture: true });
        }

        document.addEventListener("focusin", function (event) {
            if (!isMobileViewport() || !form.contains(event.target) || !isFormField(event.target)) {
                return;
            }

            scheduleViewportSync();
            scheduleClamp(event.target);
        }, true);

        if (window.jQuery && typeof window.jQuery === "function") {
            window.jQuery(document).on("shown.bs.tab", '#adforest-ad-post-form [data-bs-toggle="pill"]', function () {
                scheduleViewportSync();
                scheduleClamp(form.querySelector(".tab-pane.active, .tab-pane.show.active") || form);
            });
        }

        document.addEventListener("click", function (event) {
            if (!isMobileViewport()) {
                return;
            }

            if (event.target.closest(".next-btn, .prev-btn, .adforest-stepper__item")) {
                scheduleViewportSync();
                scheduleClamp(form.querySelector(".tab-pane.active, .tab-pane.show.active") || form);
            }
        }, true);

        window.addEventListener("resize", function () {
            syncIOSViewportState();
            if (isMobileViewport()) {
                clampHorizontalScroll();
            }
        });

        window.addEventListener("orientationchange", function () {
            window.setTimeout(function () {
                syncIOSViewportState();
                if (isMobileViewport()) {
                    clampHorizontalScroll();
                }
            }, 120);
        });

        if (window.visualViewport && typeof window.visualViewport.addEventListener === "function") {
            window.visualViewport.addEventListener("resize", scheduleViewportSync);
        }

        bindHorizontalPanLock();
        syncIOSViewportState();
        scheduleClamp(form);
    }

    function init() {
        var form = document.getElementById("adforest-ad-post-form");
        if (!form) {
            return;
        }

        bindTermsAgreementCheckbox(form);
        renderContactMethodsUi(form);
        bindSubmitGuard(form);
        bindDraftPersistence(form);
        bindAjaxSuccessCleanup();
        restoreDraft(form);
        bindMobileOverflowGuard(form);
        if (!hasCustomContactMethods()) {
            enhanceAdPostPhoneField(form);
        }
        syncManagedProfileFields(form);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})(window, document);
