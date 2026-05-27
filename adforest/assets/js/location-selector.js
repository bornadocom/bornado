(function ($) {
    'use strict';

    var SELECTOR = '.adt-location-selector';
    var OPEN_CLASS = 'is-open';
    var LOADING_CLASS = 'is-loading';

    function getAjaxUrl() {
        if (window.adforestLocationSelector && window.adforestLocationSelector.ajaxUrl) {
            return window.adforestLocationSelector.ajaxUrl;
        }

        return $('#adforest_ajax_url').val() || '';
    }

    function getErrorMessage() {
        if (window.adforestLocationSelector && window.adforestLocationSelector.errorMessage) {
            return window.adforestLocationSelector.errorMessage;
        }

        return 'Unable to update location. Please try again.';
    }

    function shouldReloadOnChange() {
        if (window.adforestLocationSelector && Object.prototype.hasOwnProperty.call(window.adforestLocationSelector, 'reloadOnChange')) {
            return window.adforestLocationSelector.reloadOnChange !== false;
        }

        return true;
    }

    function updateDisplay($selector, name, image) {
        var $value = $selector.find('.adt-location-value');
        var $img = $selector.find('.adt-location-flag img');

        if (name) {
            $value.text(name);
        }

        if (image) {
            $img.attr('src', image);
            if (name) {
                $img.attr('alt', name);
            }
        }
    }

    function closeSelector($selector) {
        $selector.removeClass(OPEN_CLASS);
        $selector.find('.adt-location-toggle').attr('aria-expanded', 'false');
        $selector.find('.adt-location-dropdown').attr('aria-hidden', 'true');
    }

    function closeAllSelectors() {
        $(SELECTOR).each(function () {
            closeSelector($(this));
        });
    }

    $(document).on('click', '.adt-location-toggle', function (event) {
        event.preventDefault();
        var $toggle = $(this);
        var $selector = $toggle.closest(SELECTOR);

        if ($selector.hasClass(OPEN_CLASS)) {
            closeSelector($selector);
            return;
        }

        closeAllSelectors();
        $selector.addClass(OPEN_CLASS);
        $toggle.attr('aria-expanded', 'true');
        $selector.find('.adt-location-dropdown').attr('aria-hidden', 'false');

        setTimeout(function () {
            $selector.find('.adt-location-search-field').trigger('focus');
        }, 150);
    });

    $(document).on('click', function (event) {
        if (!$(event.target).closest(SELECTOR).length) {
            closeAllSelectors();
        }
    });

    $(document).on('keydown', function (event) {
        if (event.key === 'Escape') {
            closeAllSelectors();
        }
    });

    $(document).on('input', '.adt-location-search-field', function () {
        var query = ($(this).val() || '').toString().toLowerCase();
        var $dropdown = $(this).closest('.adt-location-dropdown');

        $dropdown.find('.adt-location-option').each(function () {
            var $option = $(this);
            var name = ($option.data('locName') || '').toString().toLowerCase();
            var $item = $option.closest('li');

            $item.toggle(name.indexOf(query) !== -1);
        });
    });

    $(document).on('click', '.adt-location-option', function (event) {
        event.preventDefault();
        var $option = $(this);
        var $selector = $option.closest(SELECTOR);
        var currentId = $selector.attr('data-selected');
        var locationId = $option.data('locId');
        var ajaxUrl = getAjaxUrl();

        if (!ajaxUrl) {
            console.warn('AdForest: AJAX URL missing for location selector.');
            return;
        }

        if ($selector.hasClass(LOADING_CLASS)) {
            return;
        }

        if (String(currentId) === String(locationId)) {
            closeSelector($selector);
            return;
        }

        $selector.addClass(LOADING_CLASS);

        $.post(ajaxUrl, {
            action: 'sb_set_site_location',
            location_id: locationId,
            security: $('#sb_set_site_location_nonce').val()
        }).done(function (response) {
            var success = false;
            var payload = {};

            if (typeof response === 'object' && response !== null) {
                success = !!response.success;
                payload = response.data || {};
            } else if ($.trim(response) === '1') {
                success = true;
            }

            if (!success) {
                var message = (payload && payload.message) ? payload.message : getErrorMessage();
                window.alert(message);
                return;
            }

            var name = payload.name || $option.data('locName');
            var image = payload.image || $option.data('locImage');

            updateDisplay($selector, name, image);

            $selector.attr('data-selected', locationId);
            $selector.find('.adt-location-option').attr('aria-selected', 'false').each(function () {
                $(this).closest('li').removeClass('is-active');
            });

            $option.attr('aria-selected', 'true');
            $option.closest('li').addClass('is-active');

            $selector.trigger('adforest:location:changed', [locationId, payload]);

            if (shouldReloadOnChange()) {
                window.location.reload();
            } else {
                closeSelector($selector);
            }
        }).fail(function () {
            window.alert(getErrorMessage());
        }).always(function () {
            $selector.removeClass(LOADING_CLASS);
        });
    });
})(jQuery);
