(function (window, document) {
    "use strict";

    var interactionState = new WeakMap();
    var SEARCH_FORM_IDS = {
        ad_type_form: true,
        ad_condition_form: true,
        ad_warranty_form: true,
        ad_currency_form: true,
        search_cats_w: true
    };
    var CHANGE_SUBMIT_CLASSES = {
        "submit-on-change": true,
        "submit-on-condition-change": true,
        "submit-on-warranty-change": true,
        "submit-on-currency-change": true
    };

    function getInputFromTarget(target) {
        if (!target) {
            return null;
        }

        if (target.tagName === "INPUT") {
            return target;
        }

        if (target.closest) {
            var label = target.closest("label");
            if (label) {
                if (label.control) {
                    return label.control;
                }

                var htmlFor = label.getAttribute("for");
                if (htmlFor) {
                    return document.getElementById(htmlFor);
                }

                return label.querySelector("input");
            }
        }

        return null;
    }

    function isToggleCandidate(input) {
        if (!input || input.tagName !== "INPUT" || input.disabled) {
            return false;
        }

        var type = String(input.type || "").toLowerCase();
        if (type !== "radio") {
            return false;
        }

        var form = input.form;
        if (!form) {
            return false;
        }

        if (form.closest && form.closest("#adforest-ajax-sidebar")) {
            return true;
        }

        if (SEARCH_FORM_IDS[form.id]) {
            return true;
        }

        if (
            input.classList.contains("submit_on_select") ||
            input.classList.contains("submit-on-change") ||
            input.classList.contains("submit-on-condition-change") ||
            input.classList.contains("submit-on-warranty-change") ||
            input.classList.contains("submit-on-currency-change")
        ) {
            return true;
        }

        return false;
    }

    function rememberInteractionState(target) {
        var input = getInputFromTarget(target);
        if (!isToggleCandidate(input)) {
            return;
        }

        interactionState.set(input, {
            checked: !!input.checked
        });
    }

    function isAjaxManaged(input) {
        return !!(
            window.adforestAjaxSearchApi &&
            input &&
            input.form &&
            input.form.closest &&
            input.form.closest("#adforest-ajax-sidebar")
        );
    }

    function dispatchChange(input) {
        var event;

        if (!input) {
            return;
        }

        if (typeof window.Event === "function") {
            event = new window.Event("change", { bubbles: true });
        } else {
            event = document.createEvent("Event");
            event.initEvent("change", true, true);
        }

        input.dispatchEvent(event);
    }

    function submitForm(form) {
        if (!form) {
            return;
        }

        if (typeof form.requestSubmit === "function") {
            form.requestSubmit();
            return;
        }

        form.submit();
    }

    function maybeSubmitFallback(input) {
        var classList;
        var hasChangeSubmitClass = false;

        if (!input || !input.form || isAjaxManaged(input)) {
            return;
        }

        classList = input.classList;
        if (!classList) {
            return;
        }

        Object.keys(CHANGE_SUBMIT_CLASSES).forEach(function (className) {
            if (classList.contains(className)) {
                hasChangeSubmitClass = true;
            }
        });

        if (hasChangeSubmitClass) {
            return;
        }

        if (
            classList.contains("submit_on_select") ||
            /submit\s*\(/i.test(String(input.getAttribute("onclick") || ""))
        ) {
            window.setTimeout(function () {
                submitForm(input.form);
            }, 0);
        }
    }

    function clearRadio(input) {
        if (!input) {
            return;
        }

        input.checked = false;
        input.removeAttribute("checked");
        dispatchChange(input);
        maybeSubmitFallback(input);
    }

    function handleRadioClick(event) {
        var input = getInputFromTarget(event.target);
        var previousState;

        if (!isToggleCandidate(input)) {
            return;
        }

        previousState = interactionState.get(input);
        if (!previousState || !previousState.checked) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === "function") {
            event.stopImmediatePropagation();
        }

        clearRadio(input);
    }

    function handleRadioKeydown(event) {
        var input = getInputFromTarget(event.target);
        var key = String(event.key || "");

        if (!isToggleCandidate(input) || !input.checked) {
            return;
        }

        if (key !== " " && key !== "Spacebar" && key !== "Enter") {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        if (typeof event.stopImmediatePropagation === "function") {
            event.stopImmediatePropagation();
        }

        clearRadio(input);
    }

    document.addEventListener("pointerdown", function (event) {
        rememberInteractionState(event.target);
    }, true);

    document.addEventListener("mousedown", function (event) {
        rememberInteractionState(event.target);
    }, true);

    document.addEventListener("touchstart", function (event) {
        rememberInteractionState(event.target);
    }, true);

    document.addEventListener("click", handleRadioClick, true);
    document.addEventListener("keydown", handleRadioKeydown, true);
})(window, document);
