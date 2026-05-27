(function ($) {
    "use strict";
    var frameworkConfig = window.adforestFramework || {};
    var frameworkNonces = frameworkConfig.nonces || {};
    var frameworkStrings = frameworkConfig.i18n || {};
    var frameworkAjaxUrl = frameworkConfig.ajaxUrl || window.ajaxurl || '';

    var handleFrameworkError = function (message) {
        alert(message || frameworkStrings.genericError || 'An unexpected error occurred.');
    };

    $('#ad_catsdiv').hide();
    $('#ad_tagsdiv').hide();
    $('#ad_conditiondiv').hide();
    $('#ad_typediv').hide();
    $('#ad_warrantydiv').hide();
    $('#ad_currencydiv').hide();
    $('#ad_countrydiv').hide();

    jQuery(document).on('click', '.sb-update-ads-counter', function () {
        if (confirm(jQuery('#sb-admin-confirm').val())) {
            var adforest_ajax_url_admin = jQuery('#sb-admin-ajax').val();
            var sb_data = 'action=adforest_update_ads_counter';
            jQuery('.adforest-spin-load img').show();
            jQuery.ajax({
                url: adforest_ajax_url_admin,
                type: "POST",
                data: sb_data,
                dataType: "json",
                success: function (data) { jQuery('.adforest-spin-load img').hide(); if (typeof data.status !== 'undefined' && data.status == true) { alert(data.msg); window.location.reload(); } },
                error: function (xhr) { alert('Database updation fails.'); }
            });
            return false;
        } else { return false; }
    });
    jQuery(document).on('click', '#adforest-reject-submit', function (e) {
        'use strict';
        var reject_reason = jQuery('#rejection-reason').val();
        var ajax_url = jQuery('#ajax-url').val();
        var reject_post_id = jQuery('#reject_post_id').val();
        var redirect_url = jQuery('#redirect-url').val();
        var response_ = jQuery('.rej-response').val();
        jQuery('.rej-response').html('');
        $(".adforest-spinner").show();
        var request = $.ajax({
            url: ajax_url,
            method: "POST",
            data: { post_id: reject_post_id, ad_reject_reason: reject_reason, action: 'adforest_ad_rejection', security: jQuery('#sb_ad_rejection_nonce').val() }, dataType: "json"
        });
        request.done(function (response) { $(".adforest-spinner").hide(); if (typeof response.status !== 'undefined' && response.status == true) { jQuery('.rej-response').html('<span>' + response.message + '</span>'); window.location.href = redirect_url; } else { jQuery('.rej-response').html('<span>' + response.message + '</span>'); } });
    });
})(jQuery);
function adforest_fresh_install() { var is_fresh_copy = confirm("Are you installating it with fresh copy of WordPress? Please only select OK if it is fresh installation."); if (is_fresh_copy) { jQuery.ajax({ type: "POST", url: ajaxurl, data: { action: 'demo_data_start', is_fresh: 'yes' } }).done(function (msg) { }); } }
jQuery('.get_user_meta_id').on('click', function () {
    var is_process = confirm("Are you sure to delete it.");
    if (!is_process) {
        return;
    }
    var meta_id = jQuery(this).attr('data-mid');
    var security = frameworkNonces.deleteUserRating || '';
    if (!security) {
        return handleFrameworkError();
    }
    jQuery.ajax({
        type: "POST",
        url: frameworkAjaxUrl || ajaxurl,
        dataType: 'json',
        data: { action: 'sb_delete_user_rating', meta_id: meta_id, security: security }
    }).done(function (response) {
        if (response && response.success) {
            location.reload();
            return;
        }
        var message = (response && response.data && response.data.message) ? response.data.message : '';
        handleFrameworkError(message);
    }).fail(function () {
        handleFrameworkError();
    });
});

jQuery('.bids-in-admin').on('click', function () {
    var is_process = confirm("Are you sure to delete it.");
    if (!is_process) {
        return;
    }
    var meta_id = jQuery(this).attr('data-bid-meta');
    var security = frameworkNonces.deleteUserBid || '';
    if (!security) {
        return handleFrameworkError();
    }
    jQuery.ajax({
        type: "POST",
        url: frameworkAjaxUrl || ajaxurl,
        dataType: 'json',
        data: { action: 'sb_delete_user_bid', meta_id: meta_id, security: security }
    }).done(function (response) {
        if (response && response.success) {
            location.reload();
            return;
        }
        var message = (response && response.data && response.data.message) ? response.data.message : '';
        handleFrameworkError(message);
    }).fail(function () {
        handleFrameworkError();
    });
});
jQuery(document).ready(function () { jQuery("#publishing-action").append(jQuery("#reject-btn")); });
/*Function to execute when the Add Date button is clicked.*/
function redux_add_date() { (function ($) { 'use strict'; $(document).ready(function () { var date = new Date(); var text = $('#opt-blank-text'); text.val(date.toString()); }); })(jQuery) };
/*Function to execute when the Alert button is clicked.*/
function redux_show_alert() { alert('You clicked the Alert button!'); };
