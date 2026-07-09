(function (window, document) {
    "use strict";
    var BIDI_SELECTOR = 'input[type="text"], input:not([type]), input[type="search"], input[type="password"], input[type="tel"], textarea';

    function filterNoisyNonceLog() {
        if (!window.console || typeof window.console.log !== "function" || window.console.__bornadoNonceLogFiltered) {
            return;
        }

        var originalLog = window.console.log.bind(window.console);

        window.console.log = function () {
            if (arguments.length > 0 && arguments[0] === "Nonce: ") {
                return;
            }

            return originalLog.apply(window.console, arguments);
        };

        window.console.__bornadoNonceLogFiltered = true;
    }

    function installGooglePlacesStubIfNeeded() {
        var needsAddressAutocomplete = !!document.getElementById("ad_address") || !!document.getElementById("sb_user_address");
        var googleMaps;

        if (!needsAddressAutocomplete) {
            return;
        }

        if (
            window.google &&
            window.google.maps &&
            window.google.maps.places &&
            typeof window.google.maps.places.Autocomplete === "function"
        ) {
            return;
        }

        window.google = window.google || {};
        googleMaps = window.google.maps = window.google.maps || {};
        googleMaps.places = googleMaps.places || {};
        googleMaps.event = googleMaps.event || {};

        if (typeof googleMaps.places.Autocomplete !== "function") {
            googleMaps.places.Autocomplete = function () {
                return {
                    bindTo: function () {},
                    getPlace: function () {
                        return null;
                    }
                };
            };
        }

        if (typeof googleMaps.event.addListener !== "function") {
            googleMaps.event.addListener = function () {
                return null;
            };
        }
    }

    function isEligibleBidiField(element) {
        var tagName;
        var type;

        if (!element || element.nodeType !== 1 || element.disabled) {
            return false;
        }

        tagName = String(element.tagName || "").toUpperCase();
        type = String(element.getAttribute("type") || "").toLowerCase();

        if (element.hasAttribute("data-bornado-dir-optout")) {
            return false;
        }

        if (element.hasAttribute("dir") && (!element.dataset || element.dataset.bornadoAutoDir !== "1")) {
            return false;
        }

        if (tagName === "TEXTAREA") {
            return !element.readOnly;
        }

        if (tagName !== "INPUT") {
            return false;
        }

        return [
            "",
            "text",
            "search",
            "password",
            "tel"
        ].indexOf(type) !== -1;
    }

    function isNumericLikeText(text) {
        var value = String(text || "").trim();

        if (!value) {
            return false;
        }

        return /^[0-9\u06F0-\u06F9\u0660-\u0669\s+\-()/.]+$/.test(value);
    }

    function getCharacterDirection(character) {
        if (!character) {
            return "";
        }

        if (/[A-Za-z]/.test(character)) {
            return "ltr";
        }

        if (/[\u0590-\u08FF\uFB1D-\uFDFD\uFE70-\uFEFC]/.test(character)) {
            return "rtl";
        }

        return "";
    }

    function detectTextDirection(text) {
        var value = String(text || "").trim();
        var index;
        var direction;

        for (index = 0; index < value.length; index += 1) {
            direction = getCharacterDirection(value.charAt(index));
            if (direction) {
                return direction;
            }
        }

        return "";
    }

    function rememberBidiDefaults(element) {
        if (!element || !element.dataset) {
            return;
        }

        if (typeof element.dataset.bornadoOriginalDir === "undefined") {
            element.dataset.bornadoOriginalDir = element.getAttribute("dir") || "";
        }

        if (typeof element.dataset.bornadoOriginalTextAlign === "undefined") {
            element.dataset.bornadoOriginalTextAlign = element.style.textAlign || "";
        }
    }

    function restoreBidiDefaults(element) {
        if (!element || !element.dataset) {
            return;
        }

        if (element.dataset.bornadoOriginalDir) {
            element.setAttribute("dir", element.dataset.bornadoOriginalDir);
        } else {
            element.removeAttribute("dir");
        }

        if (element.dataset.bornadoOriginalTextAlign) {
            element.style.textAlign = element.dataset.bornadoOriginalTextAlign;
        } else {
            element.style.removeProperty("text-align");
        }

        if (element.dataset.bornadoAutoDir) {
            delete element.dataset.bornadoAutoDir;
        }
    }

    function applyBidiDirection(element) {
        var valueDirection;
        var placeholderDirection;
        var direction;
        var valueText;
        var placeholderText;

        if (!isEligibleBidiField(element)) {
            return;
        }

        rememberBidiDefaults(element);

        valueText = element.value;
        placeholderText = element.getAttribute("placeholder");
        valueDirection = isNumericLikeText(valueText) ? "ltr" : detectTextDirection(valueText);
        placeholderDirection = isNumericLikeText(placeholderText) ? "ltr" : detectTextDirection(placeholderText);
        direction = valueDirection || placeholderDirection;

        if (!direction) {
            restoreBidiDefaults(element);
            return;
        }

        if (element.dataset) {
            element.dataset.bornadoAutoDir = "1";
        }

        element.setAttribute("dir", direction);
        element.style.textAlign = direction === "rtl" ? "right" : "left";
    }

    function syncBidiDirectionWithin(root) {
        var scope = root && root.querySelectorAll ? root : document;
        var fields;

        if (isEligibleBidiField(root)) {
            applyBidiDirection(root);
        }

        fields = scope.querySelectorAll(BIDI_SELECTOR);
        Array.prototype.forEach.call(fields, applyBidiDirection);
    }

    function nodeNeedsBidiScan(node) {
        if (!node || node.nodeType !== 1) {
            return false;
        }

        if (isEligibleBidiField(node)) {
            return true;
        }

        return !!(node.querySelector && node.querySelector(BIDI_SELECTOR));
    }

    function queueBidiSyncFactory() {
        var queuedNodes = [];
        var scheduled = false;

        function flushQueue() {
            scheduled = false;
            queuedNodes.splice(0).forEach(function (node) {
                syncBidiDirectionWithin(node);
            });
        }

        return function queueBidiSync(node) {
            if (!nodeNeedsBidiScan(node)) {
                return;
            }

            queuedNodes.push(node);
            if (scheduled) {
                return;
            }

            scheduled = true;
            if (typeof window.requestAnimationFrame === "function") {
                window.requestAnimationFrame(flushQueue);
                return;
            }

            window.setTimeout(flushQueue, 16);
        };
    }

    function installAutomaticBidiFieldSupport() {
        var observer;
        var queueBidiSync = queueBidiSyncFactory();

        syncBidiDirectionWithin(document);

        document.addEventListener("focusin", function (event) {
            applyBidiDirection(event.target);
        }, true);

        document.addEventListener("input", function (event) {
            applyBidiDirection(event.target);
        }, true);

        document.addEventListener("change", function (event) {
            applyBidiDirection(event.target);
        }, true);

        if (typeof window.MutationObserver !== "function" || !document.body) {
            return;
        }

        observer = new window.MutationObserver(function (mutations) {
            Array.prototype.forEach.call(mutations, function (mutation) {
                if (mutation.type === "attributes" && mutation.target) {
                    applyBidiDirection(mutation.target);
                    return;
                }

                Array.prototype.forEach.call(mutation.addedNodes, function (node) {
                    queueBidiSync(node);
                });
            });
        });

        observer.observe(document.body, {
            subtree: true,
            childList: true,
            attributes: true,
            attributeFilter: ["placeholder"]
        });
    }

    filterNoisyNonceLog();

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", function () {
            installGooglePlacesStubIfNeeded();
            installAutomaticBidiFieldSupport();
        }, { once: true });
    } else {
        installGooglePlacesStubIfNeeded();
        installAutomaticBidiFieldSupport();
    }
})(window, document);
