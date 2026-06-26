document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    var sequence = 0;

    function ensureId(node, prefix) {
        if (!node) {
            return "";
        }

        if (node.id) {
            return node.id;
        }

        sequence += 1;
        node.id = String(prefix || "bornado-field") + "-" + sequence;
        return node.id;
    }

    function setAriaLabelFromPlaceholder(input) {
        if (!input || input.getAttribute("aria-label")) {
            return;
        }

        var placeholder = String(input.getAttribute("placeholder") || "").trim();
        if (placeholder !== "") {
            input.setAttribute("aria-label", placeholder);
        }
    }

    function fixForgotPasswordEmailLabel(scope) {
        var root = scope && scope.querySelectorAll ? scope : document;
        var input = root.querySelector("#sb_forgot_email");
        var label;

        if (!input) {
            return;
        }

        label = input.closest("form") ? input.closest("form").querySelector("label:not([for])") : null;
        if (!label) {
            return;
        }

        label.setAttribute("for", ensureId(input, "sb-forgot-email"));
    }

    function fixFooterNewsletterInput(scope) {
        var root = scope && scope.querySelectorAll ? scope : document;
        var input = root.querySelector("#footer_email");

        if (!input) {
            return;
        }

        setAriaLabelFromPlaceholder(input);
        if (!input.getAttribute("autocomplete")) {
            input.setAttribute("autocomplete", "email");
        }
    }

    function fixGeneratedSearchInputs(scope) {
        var root = scope && scope.querySelectorAll ? scope : document;

        root.querySelectorAll(".bornado-mobile-choice__search, .adf-mobile-category-sheet__search, .bpcp__search").forEach(function (input) {
            ensureId(input, "bornado-search");
            setAriaLabelFromPlaceholder(input);
            if (!input.getAttribute("autocomplete")) {
                input.setAttribute("autocomplete", "off");
            }
        });
    }

    function fixRangeSliderInputs(scope) {
        var root = scope && scope.querySelectorAll ? scope : document;

        root.querySelectorAll(".adt-ads-range-slider.irs-hidden-input, #price-slider-topbar-search.irs-hidden-input").forEach(function (input) {
            ensureId(input, "bornado-range-slider");
            if (input.getAttribute("name") === "") {
                input.removeAttribute("name");
            }
            input.setAttribute("aria-hidden", "true");
            input.setAttribute("autocomplete", "off");
        });
    }

    function applyFixes(scope) {
        fixForgotPasswordEmailLabel(scope);
        fixFooterNewsletterInput(scope);
        fixGeneratedSearchInputs(scope);
        fixRangeSliderInputs(scope);
    }

    applyFixes(document);

    if (typeof MutationObserver === "undefined" || !document.body) {
        return;
    }

    new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            mutation.addedNodes.forEach(function (node) {
                if (node && node.nodeType === 1) {
                    applyFixes(node);
                }
            });
        });
    }).observe(document.body, {
        childList: true,
        subtree: true
    });
});
