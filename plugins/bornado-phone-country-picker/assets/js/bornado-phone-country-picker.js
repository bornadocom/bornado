(function (window, document) {
    "use strict";

    var config = window.BornadoPhoneCountryPicker || {};
    var integrations = Array.isArray(config.integrations) ? config.integrations : [];
    var countries = Array.isArray(config.countries) ? config.countries : [];
    var suggestedCountry = config.suggestedCountry && typeof config.suggestedCountry === "object"
        ? config.suggestedCountry
        : null;
    var legacyDefaultCountry = config.legacyDefaultCountry && typeof config.legacyDefaultCountry === "object"
        ? config.legacyDefaultCountry
        : null;
    var countryMap = buildCountryMap(countries);
    var observer = null;
    var panelSequence = 0;

    function getI18n(key) {
        return config.i18n && config.i18n[key] ? String(config.i18n[key]) : key;
    }

    function readSearchContextCookie() {
        var cookieName = "bornado_search_context";
        var searchCoreConfig = window.BornadoSearchCoreConfig || {};
        var prefix;
        var parts;
        var index;

        if (searchCoreConfig.cookieName) {
            cookieName = String(searchCoreConfig.cookieName);
        }

        prefix = cookieName + "=";
        parts = String(document.cookie || "").split(";");

        for (index = 0; index < parts.length; index += 1) {
            var item = parts[index].trim();
            if (item.indexOf(prefix) !== 0) {
                continue;
            }

            try {
                return JSON.parse(decodeURIComponent(item.slice(prefix.length))) || {};
            } catch (error) {
                return {};
            }
        }

        return {};
    }

    function resolveLocationIdsFromRuntime() {
        var routeContext = window.BornadoSearchCoreConfig && window.BornadoSearchCoreConfig.routeContext
            ? window.BornadoSearchCoreConfig.routeContext
            : {};
        var runtimeParams = null;
        var cookieContext = readSearchContextCookie();
        var ids = {
            countryId: 0,
            cityId: 0
        };

        if (window.BornadoSearchCore && typeof window.BornadoSearchCore.getEffectiveCurrentSearchParams === "function") {
            runtimeParams = window.BornadoSearchCore.getEffectiveCurrentSearchParams();
        } else {
            runtimeParams = new window.URLSearchParams(window.location.search);
        }

        ids.countryId = Number(routeContext.countryId || 0);
        ids.cityId = Number(routeContext.cityId || 0);

        if (!ids.countryId) {
            ids.countryId = Number(
                runtimeParams.get("bornado_country") ||
                runtimeParams.get("ad_country") ||
                runtimeParams.get("location") ||
                runtimeParams.get("country_id") ||
                cookieContext.bornado_country ||
                cookieContext.ad_country ||
                cookieContext.location ||
                cookieContext.country_id ||
                0
            );
        }

        if (!ids.cityId) {
            ids.cityId = Number(
                runtimeParams.get("bornado_city") ||
                runtimeParams.get("city_id") ||
                cookieContext.bornado_city ||
                cookieContext.city_id ||
                0
            );
        }

        return ids;
    }

    function getCountryByTermId(termId) {
        var numericTermId = Number(termId || 0);
        var match = null;

        if (!numericTermId) {
            return null;
        }

        countries.forEach(function (country) {
            if (!match && Number(country && country.termId ? country.termId : 0) === numericTermId) {
                match = country;
            }
        });

        return match;
    }

    function getCountryByCountryCode(countryCode) {
        var normalizedCountryCode = String(countryCode || "").trim().toUpperCase();
        var match = null;

        if (!normalizedCountryCode) {
            return null;
        }

        countries.forEach(function (country) {
            if (!match && String(country && country.countryCode ? country.countryCode : "").toUpperCase() === normalizedCountryCode) {
                match = country;
            }
        });

        return match;
    }

    function countryCodeFromLocale(locale) {
        var normalized = String(locale || "").trim();
        var parts;

        if (!normalized) {
            return "";
        }

        parts = normalized.replace("_", "-").split("-");

        if (parts.length > 1 && parts[1]) {
            return String(parts[1]).toUpperCase();
        }

        if (parts[0] === "fa") {
            return "IR";
        }

        if (parts[0] === "en") {
            return "GB";
        }

        if (parts[0] === "ar") {
            return "AE";
        }

        return "";
    }

    function countryCodeFromTimezone(timezone) {
        var normalized = String(timezone || "").trim();
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

        return timezoneMap[normalized] || "";
    }

    function resolveBrowserSuggestedCountry() {
        var htmlLang = document.documentElement && document.documentElement.lang
            ? String(document.documentElement.lang)
            : "";
        var locales = [];
        var timezone = "";
        var idx;
        var country;

        if (htmlLang) {
            locales.push(htmlLang);
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
        } catch (error) {
            timezone = "";
        }

        country = getCountryByCountryCode(countryCodeFromTimezone(timezone));
        return country || null;
    }

    function resolveRuntimeSuggestedCountry() {
        var ids = resolveLocationIdsFromRuntime();
        var browserCountry = null;

        if (ids.countryId) {
            return getCountryByTermId(ids.countryId);
        }

        if (suggestedCountry && suggestedCountry.dialCode) {
            return suggestedCountry;
        }

        browserCountry = resolveBrowserSuggestedCountry();
        if (browserCountry) {
            return browserCountry;
        }

        return null;
    }

    function normalizeDialCode(value) {
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

    function buildCountryMap(items) {
        var map = Object.create(null);

        items.forEach(function (item) {
            var dialCode = normalizeDialCode(item && item.dialCode ? item.dialCode : "");
            if (!dialCode || map[dialCode]) {
                return;
            }
            map[dialCode] = item;
        });

        return map;
    }

    function getIntegrationForSelect(select) {
        var name = String(select && select.name ? select.name : "");
        var index;

        for (index = 0; index < integrations.length; index += 1) {
            if (String(integrations[index].selectName || "") === name) {
                return integrations[index];
            }
        }

        return null;
    }

    function resolvePhoneInput(select, integration) {
        var form = select.form || select.closest("form");
        var selector = integration && integration.phoneInputSelector ? String(integration.phoneInputSelector) : "";
        var rootSelector = integration && integration.rootSelector ? String(integration.rootSelector) : "";
        var root = rootSelector ? select.closest(rootSelector) : null;
        root = root || select.parentNode;

        if (root && selector) {
            var scoped = root.querySelector(selector);
            if (scoped) {
                return scoped;
            }
        }

        if (form && selector) {
            return form.querySelector(selector);
        }

        return null;
    }

    function resolveHelperNode(select, integration) {
        var selector = integration && integration.helperSelector ? String(integration.helperSelector) : "";
        var rootSelector = integration && integration.rootSelector ? String(integration.rootSelector) : "";
        var root = rootSelector ? select.closest(rootSelector) : null;
        root = root || select.parentNode;

        if (!selector || !root || !root.querySelector) {
            return null;
        }

        return root.querySelector(selector);
    }

    function buildOptionsFromSelect(select) {
        var options = [];

        Array.prototype.forEach.call(select.options || [], function (option) {
            var dialCode = normalizeDialCode(option.value);
            var base = dialCode && countryMap[dialCode] ? countryMap[dialCode] : null;
            var label = option.textContent ? String(option.textContent).trim() : "";
            var fallbackName = label.replace(/\s*\(.+?\)\s*$/, "").trim();

            if (!dialCode) {
                return;
            }

            options.push({
                termId: base && base.termId ? Number(base.termId) : 0,
                dialCode: dialCode,
                countryCode: base && base.countryCode ? String(base.countryCode) : "",
                flagEmoji: base && base.flagEmoji ? String(base.flagEmoji) : "",
                flagUrl: base && base.flagUrl ? String(base.flagUrl) : "",
                displayNameFa: base && base.displayNameFa ? String(base.displayNameFa) : fallbackName,
                displayNameEn: base && base.displayNameEn ? String(base.displayNameEn) : "",
                searchTokens: base && base.searchTokens ? String(base.searchTokens) : label + " " + dialCode,
                isPinned: !!(base && base.isPinned),
                isTierOne: !!(base && base.isTierOne),
                sourceLabel: base && base.source ? String(base.source) : "",
                suggested: false
            });
        });

        return options;
    }

    function sortOptions(options, activeDialCode) {
        return options.slice().sort(function (left, right) {
            var leftActive = normalizeDialCode(left.dialCode) === activeDialCode ? 1 : 0;
            var rightActive = normalizeDialCode(right.dialCode) === activeDialCode ? 1 : 0;

            if (leftActive !== rightActive) {
                return rightActive - leftActive;
            }

            if (!!left.isPinned !== !!right.isPinned) {
                return left.isPinned ? -1 : 1;
            }

            return String(left.displayNameFa || left.displayNameEn || left.dialCode).localeCompare(
                String(right.displayNameFa || right.displayNameEn || right.dialCode),
                "fa"
            );
        });
    }

    function applySuggestedDefault(select, phoneInput, options) {
        var currentDialCode = normalizeDialCode(select.value);
        var runtimeSuggestedCountry = resolveRuntimeSuggestedCountry();
        var suggestedDialCode = normalizeDialCode(runtimeSuggestedCountry && runtimeSuggestedCountry.dialCode ? runtimeSuggestedCountry.dialCode : "");
        var legacyDialCode = normalizeDialCode(legacyDefaultCountry && legacyDefaultCountry.dialCode ? legacyDefaultCountry.dialCode : "");
        var phoneHasValue = !!(phoneInput && String(phoneInput.value || "").trim() !== "");
        var hasSuggestedOption = options.some(function (item) {
            return normalizeDialCode(item.dialCode) === suggestedDialCode;
        });

        if (!suggestedDialCode || !hasSuggestedOption || phoneHasValue) {
            return;
        }

        if (!currentDialCode || currentDialCode === legacyDialCode) {
            select.value = suggestedDialCode;
        }
    }

    function createRoot(select) {
        var root = document.createElement("div");
        root.className = "bpcp";
        root.setAttribute("dir", "rtl");
        root.dataset.enhancedFor = String(select.name || "phone_dial_code");
        return root;
    }

    function createTrigger(select) {
        var button = document.createElement("button");
        button.type = "button";
        button.className = "bpcp__trigger";
        button.setAttribute("aria-haspopup", "dialog");
        button.setAttribute("aria-expanded", "false");
        button.setAttribute("aria-label", getI18n("selectedLabel"));
        button.dataset.triggerFor = String(select.name || "phone_dial_code");
        return button;
    }

    function createPanel() {
        var panel = document.createElement("div");
        var search = document.createElement("input");
        var list = document.createElement("div");
        var empty = document.createElement("div");
        var panelId;
        var searchId;

        panel.className = "bpcp__panel";
        panel.hidden = true;
        panelSequence += 1;
        panelId = "bpcp-panel-" + panelSequence;
        searchId = "bpcp-search-" + panelSequence;
        panel.id = panelId;

        search.type = "search";
        search.id = searchId;
        search.className = "bpcp__search";
        search.placeholder = getI18n("searchPlaceholder");
        search.autocomplete = "off";
        search.setAttribute("aria-label", getI18n("searchPlaceholder"));

        list.className = "bpcp__list";
        empty.className = "bpcp__empty";
        empty.hidden = true;
        empty.textContent = getI18n("emptySearch");

        panel.appendChild(search);
        panel.appendChild(list);
        panel.appendChild(empty);

        return {
            panel: panel,
            search: search,
            list: list,
            empty: empty
        };
    }

    function resolvePortalParent(select) {
        return select.closest(".modal, .bornado-auth-modal, .bornado-auth-inline") || document.body;
    }

    function closeAllPanels(exceptRoot) {
        Array.prototype.forEach.call(document.querySelectorAll(".bpcp.is-open"), function (root) {
            if (exceptRoot && root === exceptRoot) {
                return;
            }

            root.classList.remove("is-open");
            if (root._bpcpState) {
                if (root._bpcpState.trigger) {
                    root._bpcpState.trigger.setAttribute("aria-expanded", "false");
                }
                if (root._bpcpState.panel) {
                    root._bpcpState.panel.hidden = true;
                }
            }
        });
    }

    function flagEmojiFromCountryCode(countryCode) {
        var normalized = String(countryCode || "").trim().toUpperCase().replace(/[^A-Z]/g, "");
        if (normalized.length !== 2 || typeof String.fromCodePoint !== "function") {
            return "";
        }

        return String.fromCodePoint(127397 + normalized.charCodeAt(0))
            + String.fromCodePoint(127397 + normalized.charCodeAt(1));
    }

    function getFlagFallbackText(country) {
        if (!country) {
            return "";
        }

        var countryCode = country && country.countryCode ? String(country.countryCode) : "";
        var emoji = country && country.flagEmoji ? String(country.flagEmoji) : "";
        var generated = emoji || flagEmojiFromCountryCode(countryCode);

        if (generated) {
            return generated;
        }

        return countryCode || "##";
    }

    function renderFlagMarkup(country, className) {
        var flagUrl = country && country.flagUrl ? String(country.flagUrl) : "";
        var fallback = getFlagFallbackText(country);
        var alt = country && (country.displayNameEn || country.displayNameFa)
            ? String(country.displayNameEn || country.displayNameFa)
            : "flag";

        if (!country) {
            return '<span class="' + className + ' bpcp__flag-fallback bpcp__flag-fallback--empty"></span>';
        }

        if (!flagUrl) {
            return '<span class="' + className + ' bpcp__flag-fallback">' + escapeHtml(fallback) + "</span>";
        }

        return ''
            + '<span class="' + className + '">'
            + '<img class="bpcp__flag-image" src="' + escapeHtml(flagUrl) + '" alt="' + escapeHtml(alt) + '" loading="lazy" referrerpolicy="no-referrer" onload="this.nextElementSibling.style.display=\'none\'" onerror="this.style.display=\'none\'">'
            + '<span class="bpcp__flag-fallback" aria-hidden="true">' + escapeHtml(fallback) + "</span>"
            + "</span>";
    }

    function renderTrigger(trigger, country) {
        var name = country ? String(country.displayNameFa || country.displayNameEn || "") : getI18n("placeholderLabel");
        var dialCode = country ? String(country.dialCode || "") : "";
        trigger.innerHTML = ""
            + '<span class="bpcp__trigger-copy">'
            + renderFlagMarkup(country, "bpcp__trigger-flag")
            + '<span class="bpcp__trigger-text">'
            + '<span class="bpcp__trigger-name">' + escapeHtml(name) + "</span>"
            + '<span class="bpcp__trigger-meta">' + (dialCode ? '<bdi dir="ltr">' + escapeHtml(dialCode) + '</bdi>' : "") + "</span>"
            + "</span>"
            + "</span>"
            + '<span class="bpcp__trigger-chevron" aria-hidden="true"></span>';
    }

    function escapeHtml(value) {
        var node = document.createElement("span");
        node.textContent = String(value || "");
        return node.innerHTML;
    }

    function renderList(state) {
        var activeDialCode = normalizeDialCode(state.select.value);
        var query = String(state.search.value || "").trim().toLowerCase();
        var visibleCount = 0;

        state.list.innerHTML = "";

        sortOptions(state.options, activeDialCode).forEach(function (country) {
            var searchTokens = String(country.searchTokens || "").toLowerCase();
            var isActive = normalizeDialCode(country.dialCode) === activeDialCode;
            var shouldShow = !query || searchTokens.indexOf(query) !== -1;

            if (!shouldShow) {
                return;
            }

            visibleCount += 1;

            var item = document.createElement("button");
            item.type = "button";
            item.className = "bpcp__item" + (isActive ? " is-selected" : "");
            item.dataset.dialCode = String(country.dialCode || "");
            item.innerHTML = ""
                + '<span class="bpcp__item-main">'
                + renderFlagMarkup(country, "bpcp__item-flag")
                + '<span class="bpcp__item-text">'
                + '<span class="bpcp__item-name">' + escapeHtml(country.displayNameFa || country.displayNameEn || "") + "</span>"
                + '<span class="bpcp__item-subtitle">'
                + (country.displayNameEn ? escapeHtml(country.displayNameEn) + " " : "")
                + (country.countryCode ? '<bdi dir="ltr">' + escapeHtml(country.countryCode) + "</bdi> " : "")
                + "</span>"
                + "</span>"
                + "</span>"
                + '<span class="bpcp__item-dial"><bdi dir="ltr">' + escapeHtml(country.dialCode || "") + "</bdi></span>";

            state.list.appendChild(item);
        });

        state.empty.hidden = visibleCount > 0;
    }

    function syncSelectedState(state) {
        var currentDialCode = normalizeDialCode(state.select.value);
        var currentCountry = state.options.find(function (country) {
            return normalizeDialCode(country.dialCode) === currentDialCode;
        }) || state.options[0] || null;

        renderTrigger(state.trigger, currentCountry);
        renderList(state);
    }

    function positionPanel(state) {
        if (!state || !state.panel || state.panel.hidden) {
            return;
        }

        if (window.innerWidth <= 767) {
            state.panel.style.width = "";
            state.panel.style.left = "";
            state.panel.style.top = "";
            state.panel.style.bottom = "";
            return;
        }

        var rect = state.trigger.getBoundingClientRect();
        var viewportWidth = Math.max(document.documentElement.clientWidth || 0, window.innerWidth || 0);
        var viewportHeight = Math.max(document.documentElement.clientHeight || 0, window.innerHeight || 0);
        var gutter = 12;
        var preferredWidth = Math.max(rect.width, 320);
        var panelWidth = Math.min(420, preferredWidth, viewportWidth - (gutter * 2));
        var left = Math.min(rect.left, viewportWidth - panelWidth - gutter);
        var top = rect.bottom + 8;
        var availableBelow = viewportHeight - rect.bottom - gutter;
        var maxHeight = Math.max(220, Math.min(360, availableBelow - 8));

        if (availableBelow < 260 && rect.top > 280) {
            state.panel.style.top = "auto";
            state.panel.style.bottom = Math.max(gutter, viewportHeight - rect.top + 8) + "px";
            maxHeight = Math.max(220, Math.min(360, rect.top - (gutter * 2)));
        } else {
            state.panel.style.bottom = "auto";
            state.panel.style.top = Math.round(top) + "px";
        }

        state.panel.style.width = Math.round(panelWidth) + "px";
        state.panel.style.left = Math.max(gutter, Math.round(left)) + "px";
        state.panel.style.maxHeight = Math.round(maxHeight + 64) + "px";
        state.list.style.maxHeight = Math.max(160, Math.round(maxHeight - 74)) + "px";
    }

    function openPanel(state) {
        closeAllPanels(state.root);
        state.root.classList.add("is-open");
        state.trigger.setAttribute("aria-expanded", "true");
        state.panel.hidden = false;
        state.search.value = "";
        renderList(state);
        positionPanel(state);
        window.setTimeout(function () {
            state.search.focus();
        }, 0);
    }

    function closePanel(state) {
        state.root.classList.remove("is-open");
        state.trigger.setAttribute("aria-expanded", "false");
        state.panel.hidden = true;
    }

    function enhanceSelect(select) {
        if (!select || select.dataset.bpcpEnhanced === "1") {
            return;
        }

        var integration = getIntegrationForSelect(select);
        var options = buildOptionsFromSelect(select);
        var phoneInput = resolvePhoneInput(select, integration);
        var portalParent = resolvePortalParent(select);

        if (!integration || !options.length) {
            return;
        }

        applySuggestedDefault(select, phoneInput, options);

        var root = createRoot(select);
        var trigger = createTrigger(select);
        var panelParts = createPanel();
        var state = {
            root: root,
            select: select,
            trigger: trigger,
            panel: panelParts.panel,
            search: panelParts.search,
            list: panelParts.list,
            empty: panelParts.empty,
            options: options,
            phoneInput: phoneInput
        };

        select.dataset.bpcpEnhanced = "1";
        select.classList.add("bpcp__native");
        select.setAttribute("tabindex", "-1");

        select.parentNode.insertBefore(root, select);
        root.appendChild(select);
        root.appendChild(trigger);
        portalParent.appendChild(panelParts.panel);
        panelParts.panel._bpcpRoot = root;
        root._bpcpState = state;

        trigger.addEventListener("click", function () {
            if (root.classList.contains("is-open")) {
                closePanel(state);
                return;
            }

            openPanel(state);
        });

        panelParts.search.addEventListener("input", function () {
            renderList(state);
        });

        panelParts.panel.addEventListener("mousedown", function (event) {
            event.stopPropagation();
        });

        panelParts.panel.addEventListener("click", function (event) {
            event.stopPropagation();
        });

        panelParts.list.addEventListener("click", function (event) {
            var button = event.target.closest(".bpcp__item");
            if (!button) {
                return;
            }

            select.value = String(button.dataset.dialCode || "");
            select.dispatchEvent(new Event("change", { bubbles: true }));
            syncSelectedState(state);
            closePanel(state);
            trigger.focus();
        });

        select.addEventListener("change", function () {
            syncSelectedState(state);
        });

        syncSelectedState(state);
    }

    function enhanceAll(root) {
        var scope = root && root.querySelectorAll ? root : document;
        var selectors = integrations.map(function (item) {
            return 'select[name="' + String(item.selectName || "") + '"]';
        }).join(",");

        if (!selectors) {
            return;
        }

        Array.prototype.forEach.call(scope.querySelectorAll(selectors), enhanceSelect);
    }

    function bindGlobalEvents() {
        document.addEventListener("click", function (event) {
            var target = event.target;
            var root = target && target.closest ? target.closest(".bpcp") : null;
            var panel = target && target.closest ? target.closest(".bpcp__panel") : null;

            if (!root && panel && panel._bpcpRoot) {
                root = panel._bpcpRoot;
            }

            closeAllPanels(root);
        });

        document.addEventListener("keydown", function (event) {
            if (event.key === "Escape") {
                closeAllPanels(null);
            }
        });

        window.addEventListener("resize", function () {
            Array.prototype.forEach.call(document.querySelectorAll(".bpcp.is-open"), function (root) {
                if (root._bpcpState) {
                    positionPanel(root._bpcpState);
                }
            });
        }, { passive: true });

        window.addEventListener("scroll", function () {
            Array.prototype.forEach.call(document.querySelectorAll(".bpcp.is-open"), function (root) {
                if (root._bpcpState) {
                    positionPanel(root._bpcpState);
                }
            });
        }, { passive: true, capture: true });
    }

    function bindObserver() {
        if (observer || typeof MutationObserver === "undefined") {
            return;
        }

        observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                mutation.addedNodes.forEach(function (node) {
                    if (!node || node.nodeType !== 1) {
                        return;
                    }

                    if (node.matches && node.matches("select[name='phone_dial_code'], select[name='bornado_phone_dial_code']")) {
                        enhanceSelect(node);
                        return;
                    }

                    enhanceAll(node);
                });
            });
        });

        observer.observe(document.documentElement, { childList: true, subtree: true });
    }

    function init() {
        if (!integrations.length) {
            return;
        }

        enhanceAll(document);
        bindGlobalEvents();
        bindObserver();
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})(window, document);
