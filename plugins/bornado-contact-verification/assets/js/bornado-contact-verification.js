(function ($) {
    function showNotice(type, message) {
        if (window.toastr && typeof window.toastr[type] === 'function') {
            window.toastr[type](message, '', {
                timeOut: 4000,
                closeButton: true,
                positionClass: 'toast-top-right'
            });
            return;
        }

        window.alert(message);
    }

    function sendRequest(action, payload) {
        return $.post(bornadoContactVerification.ajaxUrl, $.extend({ action: action }, payload || {}));
    }

    function withBusyState($button, busyText, callback) {
        if (!$button || !$button.length || $button.data('busy')) {
            return;
        }

        var originalText = $.trim($button.text());
        $button.data('busy', true);
        $button.data('original-text', originalText);
        $button.prop('disabled', true).text(busyText);

        callback(function restoreButton() {
            $button.data('busy', false);
            $button.prop('disabled', false).text($button.data('original-text') || originalText);
        });
    }

    function openWhatsappModal() {
        var modalElement = document.getElementById('bornado-whatsapp-verification-modal');
        if (!modalElement) {
            return;
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
            return;
        }

        modalElement.style.display = 'block';
    }

    function closeWhatsappModal() {
        var modalElement = document.getElementById('bornado-whatsapp-verification-modal');
        if (!modalElement || !window.bootstrap || !window.bootstrap.Modal) {
            return;
        }

        window.bootstrap.Modal.getOrCreateInstance(modalElement).hide();
    }

    $(document).on('click', '[data-bornado-email-send]', function (event) {
        event.preventDefault();

        var $button = $(this);
        withBusyState($button, bornadoContactVerification.messages.sending, function (restoreButton) {
            sendRequest('bornado_contact_verification_send_email', {
                security: bornadoContactVerification.nonces.email
            }).done(function (response) {
                if (response && response.success) {
                    showNotice('success', response.data && response.data.message ? response.data.message : bornadoContactVerification.messages.unknownError);
                    $('[data-bornado-email-status]').text('در انتظار تأیید');
                    $button.hide();
                    restoreButton();
                    return;
                }

                showNotice('error', response && response.data && response.data.message ? response.data.message : bornadoContactVerification.messages.unknownError);
                restoreButton();
            }).fail(function (xhr) {
                var response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                showNotice('error', response && response.data && response.data.message ? response.data.message : bornadoContactVerification.messages.unknownError);
                restoreButton();
            });
        });
    });

    $(document).on('click', '[data-bornado-whatsapp-send]', function (event) {
        event.preventDefault();

        var $button = $(this);
        withBusyState($button, bornadoContactVerification.messages.sending, function (restoreButton) {
            sendRequest('bornado_contact_verification_send_whatsapp', {
                security: bornadoContactVerification.nonces.whatsapp
            }).done(function (response) {
                if (response && response.success) {
                    showNotice('success', response.data && response.data.message ? response.data.message : bornadoContactVerification.messages.unknownError);
                    $('[data-bornado-whatsapp-status]').text('در انتظار تأیید');
                    openWhatsappModal();
                    restoreButton();
                    return;
                }

                showNotice('error', response && response.data && response.data.message ? response.data.message : bornadoContactVerification.messages.unknownError);
                restoreButton();
            }).fail(function (xhr) {
                var response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                showNotice('error', response && response.data && response.data.message ? response.data.message : bornadoContactVerification.messages.unknownError);
                restoreButton();
            });
        });
    });

    $(document).on('submit', '#bornado-whatsapp-verify-form', function (event) {
        event.preventDefault();

        var $form = $(this);
        var $submit = $form.find('[type="submit"]');
        var code = $.trim(String($form.find('input[name="code"]').val() || ''));

        withBusyState($submit, bornadoContactVerification.messages.verifying, function (restoreButton) {
            sendRequest('bornado_contact_verification_verify_whatsapp', {
                security: bornadoContactVerification.nonces.verify,
                code: code
            }).done(function (response) {
                if (response && response.success) {
                    showNotice('success', response.data && response.data.message ? response.data.message : bornadoContactVerification.messages.whatsappVerified);
                    $('[data-bornado-whatsapp-status]').text('تأیید شده');
                    closeWhatsappModal();
                    window.setTimeout(function () {
                        window.location.reload();
                    }, 500);
                    restoreButton();
                    return;
                }

                showNotice('error', response && response.data && response.data.message ? response.data.message : bornadoContactVerification.messages.unknownError);
                restoreButton();
            }).fail(function (xhr) {
                var response = xhr && xhr.responseJSON ? xhr.responseJSON : null;
                showNotice('error', response && response.data && response.data.message ? response.data.message : bornadoContactVerification.messages.unknownError);
                restoreButton();
            });
        });
    });
})(jQuery);
