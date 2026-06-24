(function (window, document, $) {
    "use strict";

    var config = window.bornadoProfileAvatarManager || {};
    var ajaxUrl = typeof config.ajaxUrl === "string" ? config.ajaxUrl : "";
    var deleteNonce = typeof config.deleteNonce === "string" ? config.deleteNonce : "";
    var defaultAvatarUrl = typeof config.defaultAvatarUrl === "string" ? config.defaultAvatarUrl : "";

    function getI18n(key, fallback) {
        if (config.i18n && config.i18n[key]) {
            return String(config.i18n[key]);
        }

        return fallback || key;
    }

    function notify(type, message) {
        if (window.toastr && typeof window.toastr[type] === "function") {
            window.toastr[type](message, "", {
                timeOut: type === "success" ? 2500 : 4000,
                closeButton: true,
                positionClass: "toast-top-right"
            });
            return;
        }

        if (message) {
            window.alert(message);
        }
    }

    function updateVisibleAvatars(url) {
        if (!url) {
            return;
        }

        $("#img-upload, #user_dp, .adt-user-avatar img, .adt-user-trigger img").attr("src", url);
    }

    function init() {
        var $removeButton = $("[data-bornado-avatar-remove]");
        var $previewWrap = $(".user-dp-container");
        var $fileInput = $("#imgInp");

        if (!$removeButton.length || !$previewWrap.length) {
            return;
        }

        function setRemoveVisible(isVisible) {
            $removeButton.prop("hidden", !isVisible);
            $removeButton.attr("aria-hidden", isVisible ? "false" : "true");
            $previewWrap.toggleClass("has-custom-avatar", !!isVisible);
        }

        $removeButton.on("click", function (event) {
            event.preventDefault();
            event.stopPropagation();

            if (!ajaxUrl || !deleteNonce || $removeButton.prop("disabled")) {
                return;
            }

            $removeButton.prop("disabled", true).addClass("is-loading");

            $.ajax({
                type: "POST",
                url: ajaxUrl,
                dataType: "json",
                data: {
                    action: "bornado_delete_user_profile_picture",
                    security: deleteNonce
                }
            }).done(function (response) {
                if (response && response.success) {
                    var avatarUrl = response.data && response.data.avatarUrl ? String(response.data.avatarUrl) : defaultAvatarUrl;
                    updateVisibleAvatars(avatarUrl);
                    setRemoveVisible(false);
                    notify("success", response.data && response.data.message ? response.data.message : getI18n("deleteDone", ""));
                    return;
                }

                notify("error", response && response.data && response.data.message ? response.data.message : getI18n("deleteFailed", ""));
            }).fail(function () {
                notify("error", getI18n("deleteFailed", ""));
            }).always(function () {
                $removeButton.prop("disabled", false).removeClass("is-loading");
            });
        });

        if ($fileInput.length) {
            $fileInput.on("change", function () {
                if (this.files && this.files.length) {
                    setRemoveVisible(true);
                }
            });
        }
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})(window, document, window.jQuery);
