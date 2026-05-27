jQuery(document).ready(function ($) {
    const ajaxurl = adforest_custom_data.ajax_url;

    $(".adforest_add_cart").on("click", function () {
        $("#sb_loading").show();
        $.post(ajaxurl, {
            action: "sb_add_cart",
            product_id: $(this).attr("data-product-id"),
            qty: $(this).attr("data-product-qty"),
            security: $('#sb_add_cart_nonce').val(),
        }).done(function (response) {
            $("#sb_loading").hide();
            let get_r = response.split("|");
            if (get_r[0].trim() == "1") {
                toastr.success(get_r[1], "", {
                    timeOut: 4000,
                    closeButton: true,
                    positionClass: "toast-top-right",
                });
                window.location = get_r[2];
            } else {
                toastr.error(get_r[1], "", {
                    timeOut: 4000,
                    closeButton: true,
                    positionClass: "toast-top-right",
                });
                // window.location = get_r[2];
            }
        }).fail(function (response) {
            $("#sb_loading").hide();
            toastr.error(response.statusText, "", {
                timeOut: 4000,
                closeButton: true,
                positionClass: "toast-top-right",
            });
        })
    });

    $('.ad-bidding-timer-box-inner[data-countdown]').each(function () {
        let $el = $(this);
        let endTs = parseInt($el.data('countdown'), 10);
        if (isNaN(endTs)) return;

        function updateCountdown() {
            let now = Date.now();
            let diff = endTs - now;
            if (diff < 0) diff = 0;

            let seconds = Math.floor((diff / 1000) % 60);
            let minutes = Math.floor((diff / 1000 / 60) % 60);
            let hours = Math.floor((diff / (1000 * 60 * 60)) % 24);
            let days = Math.floor(diff / (1000 * 60 * 60 * 24));

            function pad(n) { return n < 10 ? '0' + n : n; }

            $el.find('#days').text(days);
            $el.find('#hours').text(pad(hours));
            $el.find('#minutes').text(pad(minutes));
            $el.find('#seconds').text(pad(seconds));

            if (diff === 0 && timerId) {
                clearInterval(timerId);
            }
        }

        updateCountdown();
        let timerId = setInterval(updateCountdown, 1000);
    });

    function startCountdown($clock) {
        var endTime = parseInt($clock.data('countdown'), 10);
        if (isNaN(endTime)) return;

        var $days = $clock.find('[data-unit="days"]'),
            $hours = $clock.find('[data-unit="hours"]'),
            $minutes = $clock.find('[data-unit="minutes"]'),
            $seconds = $clock.find('[data-unit="seconds"]');

        function update() {
            var now = Date.now(),
                diff = Math.max(0, endTime - now);

            var d = Math.floor(diff / 86400000),
                h = Math.floor((diff % 86400000) / 3600000),
                m = Math.floor((diff % 3600000) / 60000),
                s = Math.floor((diff % 60000) / 1000);

            $days.text(d);
            $hours.text(h);
            $minutes.text(m);
            $seconds.text(s);

            if (diff <= 0) {
                clearInterval(timer);
            }
        }

        update();
        var timer = setInterval(update, 1000);
    }

    $('.adt-deal-countdown-desc-clock[data-countdown]').each(function () {
        startCountdown($(this));
    });
});

jQuery(document).ready(function ($) {
    $(document).on('elementor:init', function () {
        elementor.hooks.addAction('panel/open_editor/widget', function (panel, model) {
            const select2Field = $('.elementor-control-popular_categories select');

            if (select2Field.length > 0) {
                select2Field.select2({
                    maximumSelectionLength: 3,
                });
            }
        });
    });
});