(function (window, document, $) {
    "use strict";

    var config = window.bornadoProfilePublicContact || {};

    function getI18n(key, fallback) {
        if (config.i18n && config.i18n[key]) {
            return String(config.i18n[key]);
        }

        return fallback || key;
    }

    function notifyError(message) {
        if (window.toastr && typeof window.toastr.error === "function") {
            window.toastr.error(message, "", {
                timeOut: 4000,
                closeButton: true,
                positionClass: "toast-top-right"
            });
            return;
        }

        if (message) {
            window.alert(message);
        }
    }

    function revealContactRow(button) {
        var $button = $(button);
        var $row = $button.closest(".bornado-profile-contact-list__item");
        var $panel = $button.closest("[data-bornado-profile-contact]");
        var $valueBox = $row.find("[data-contact-value]");
        var userId = Number($panel.data("user-id") || 0);
        var methodKey = String($button.attr("data-method-key") || $row.attr("data-contact-key") || "");

        if (!$row.length || !userId || !methodKey || $button.prop("disabled")) {
            return;
        }

        if ($row.hasClass("is-revealed")) {
            return;
        }

        $button.prop("disabled", true);
        $row.addClass("is-loading");

        $.ajax({
            type: "POST",
            url: config.ajaxUrl,
            dataType: "json",
            data: {
                action: "bornado_reveal_profile_contacts",
                user_id: userId,
                method_key: methodKey,
                security: config.nonce
            }
        }).done(function (response) {
            if (!(response && response.success && response.data && response.data.html)) {
                notifyError(response && response.data && response.data.message ? response.data.message : getI18n("genericError", ""));
                return;
            }

            $valueBox.html(response.data.html);
            $valueBox.removeClass("is-hidden").addClass("is-filled");
            $row.addClass("is-revealed");
        }).fail(function (xhr) {
            var message = getI18n("genericError", "");
            if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                message = xhr.responseJSON.data.message;
            }
            notifyError(message);
        }).always(function () {
            $button.prop("disabled", false);
            $row.removeClass("is-loading");
        });
    }

    function init() {
        $(document).on("click", "[data-bornado-reveal-contact]", function (event) {
            event.preventDefault();
            revealContactRow(event.currentTarget);
        });
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})(window, document, window.jQuery);
