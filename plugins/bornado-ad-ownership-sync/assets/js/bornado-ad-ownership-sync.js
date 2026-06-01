(function ($) {
    var config = window.bornadoOwnershipClaim || {};

    if (!config.ajaxUrl) {
        return;
    }

    function showFeedback($modal, message, isSuccess) {
        var $box = $modal.find('.bornad-claim-feedback');
        if (!$box.length) {
            return;
        }

        $box
            .removeClass('is-success is-error')
            .addClass(isSuccess ? 'is-success' : 'is-error')
            .text(message || config.genericError || '')
            .show();
    }

    $(document).on('click', '#bornad-smart-claim-transfer', function (event) {
        event.preventDefault();

        var $button = $(this);
        var $modal = $button.closest('.bornad-claim-modal');
        var defaultText = $button.attr('data-default-text') || $.trim($button.text());
        var adId = parseInt($button.attr('data-adid'), 10) || 0;

        if (!adId || $button.prop('disabled')) {
            return;
        }

        $button.prop('disabled', true).addClass('is-loading').text(config.loadingText || defaultText);
        $modal.find('.bornad-claim-feedback').hide().text('');

        $.post(config.ajaxUrl, {
            action: 'bornado_execute_phone_claim_transfer',
            security: config.nonce,
            ad_id: adId
        }).done(function (response) {
            if (response && response.success) {
                showFeedback($modal, response.data && response.data.message ? response.data.message : '', true);

                if (response.data && response.data.reload) {
                    window.setTimeout(function () {
                        window.location.reload();
                    }, parseInt(config.reloadDelay, 10) || 1400);
                }

                return;
            }

            showFeedback($modal, response && response.data && response.data.message ? response.data.message : config.genericError, false);
        }).fail(function (xhr) {
            var message = config.genericError || 'خطایی رخ داد.';

            if (xhr && xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                message = xhr.responseJSON.data.message;
            }

            showFeedback($modal, message, false);
        }).always(function () {
            $button.prop('disabled', false).removeClass('is-loading').text(defaultText);
        });
    });
})(jQuery);
