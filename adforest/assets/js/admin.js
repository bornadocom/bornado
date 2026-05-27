(function ($) {
    "use strict";
    var adforestAdminConfig = window.adforestAdmin || {};
    var adforestAdminNonces = adforestAdminConfig.nonces || {};
    var adforestAdminStrings = adforestAdminConfig.i18n || {};
    var handleAdminError = function (message) {
        alert(message || adforestAdminStrings.genericError || 'Action failed. Please try again.');
    };
    /*verifying Purchase code...*/
    $('#verify_it').on('click', function () {

        var purchase_code = document.getElementById('adforest_code').value;
        if (purchase_code != "") {
            $.post(ajaxurl, { action: 'verify_code', code: purchase_code }).done(function (response) {
                $('#adforest_code').val('');
                if (response.trim() == 'Looks good, now you can install required plugins.') {
                    alert(response);
                    location.reload();
                } else {
                    alert(response);
                }
            });
        }
    });
    jQuery(document).ready(function ($) {
        jQuery(document).on('click', '.wpb-textinput.cats_cat, .wpb-textinput.cats_d_cat, .wpb-textinput.cats_round_cat, .wpb-textinput.catsm_cat', function () {
            var $this = jQuery(this);
            $this.attr('autocomplete', 'off');
            $this.addClass('sb-input-loading');
            var adforest_ajax_url_admin = jQuery('#sb-admin-ajax').val();
            var sb_data = 'action=adforest_term_autocomplete';
            jQuery.ajax({
                url: adforest_ajax_url_admin,
                type: "POST",
                data: sb_data,
                dataType: "json",
                success: function (data) {
                    $this.removeClass('sb-input-loading');
                    jQuery('.sb-admin-dropdown').remove();
                    $this.after(data);
                },
                error: function (xhr) {
                    alert('Ajax request fail');
                }
            });
            return false;

        });


        jQuery(document).on('click', '.wpb-textinput.cats_l_event_cat', function () {
            var $this = jQuery(this);
            $this.attr('autocomplete', 'off');
            $this.addClass('sb-input-loading');
            var adforest_ajax_url_admin = jQuery('#sb-admin-ajax').val();
            var sb_data = 'action=adforest_term_autocomplete';
            jQuery.ajax({
                url: adforest_ajax_url_admin,
                type: "POST",
                data: sb_data,
                dataType: "json",
                success: function (data) {
                    $this.removeClass('sb-input-loading');
                    jQuery('.sb-admin-dropdown').remove();
                    $this.after(data);
                },
                error: function (xhr) {
                    alert('Ajax request fail');
                }
            });
            return false;

        });


        if ($('.taxonomy-image').length > 1) {
            $('.theme-cat-image').closest('.form-field').remove();
        }


        jQuery(document).on('click', '.sb-select-term', function () {
            var selected_val = jQuery(this).data('sb-term-value');
            jQuery(this).closest("div.edit_form_line").find("input[name='cats_cat'], input[name='cats_d_cat'], input[name='cats_round_cat'], input[name='catsm_cat']").val(selected_val);
            jQuery(this).closest(".sb-admin-dropdown").remove();
        });
    });


    $('#sb_deactivate_license').on('click', function () {
        var nonce = $(this).attr('data-deactivate-nonce');
        if (confirm("Do you really want to deactivate the license")) {
            $.post(ajaxurl, { action: 'sb_deactivate_license', nonce: nonce }).done(function (response) {
                alert(response?.data?.message);
                if (response?.success) {
                    location.reload();
                }
            });
        } else {
        }
    });


    $('#send_user_confirmation_email').on('click', function (e) {

        e.preventDefault();

        if (confirm("Are you sure ?")) {
            var userId = $('#send_user_confirmation_email').data('user_id');
            $.post(ajaxurl, {
                action: 'sb_send_user_account_confirmation_mail',
                user_id: userId,
                security: jQuery('#sb_send_user_account_confirmation_mail_nonce').val()
            }).done(function (response) {
                alert(response);

            });
        } else {
        }


    });


    $('#sb_deactivate_license').on('click', function () {
        if (confirm("Do you really want to deactivate the license")) {
            $.post(ajaxurl, { action: 'sb_deactivate_license' }).done(function (response) {
                alert(response);
                location.reload();
            });
        } else {
        }
    });


    $('#set_imported_images').on('click', function () {
        if (confirm("Are you sure?") !== true) {
            return;
        }
        var security = adforestAdminNonces.setImportedAdImages || '';
        if (!security) {
            return handleAdminError();
        }
        $(this).text('importing......');
        $(this).prop('disable', true);
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: { action: 'sb_set_imported_ad_images', security: security }
        }).done(function (response) {
            var message = (response && response.data && response.data.message) ? response.data.message : '';
            alert(message);
            if (response && response.success) {
                location.reload();
            }
        }).fail(function () {
            handleAdminError();
        });
    });


    $('#sb_make_ads_activated').on('click', function () {
        if (confirm("Are you sure?") !== true) {
            return;
        }
        var security = adforestAdminNonces.setAllAdsActivated || '';
        if (!security) {
            return handleAdminError();
        }
        var this_btn = $(this);
        this_btn.text('Activating........');
        this_btn.prop('disable', true);
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: { action: 'sb_set_all_ads_activated', security: security }
        }).done(function (response) {
            this_btn.text('Activate ads');
            var message = (response && response.data && response.data.message) ? response.data.message : '';
            if (response && response.success === true) {
                alert(message);
                location.reload();
                return;
            }
            handleAdminError(message);
        }).fail(function () {
            this_btn.text('Activate ads');
            handleAdminError();
        });
    })


})(jQuery);
