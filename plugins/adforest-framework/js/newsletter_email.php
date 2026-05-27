<?php
function process_newsletter_email()
{
    global $adforest_theme;
    ?>

    <script type="text/javascript"> (function ($) {
            "use strict"
            const adforest_ajax_url = adforest_custom_data.ajax_url;
            const button = $("#save_email_footer_btn");
            const btn_icon = $("#btn-primary-icon")
            const spinner = button.find('#btn-spinner-icon');

            $("#save_email_footer_form").submit(function (e) {
                e.preventDefault();
                button.prop('disabled', true);
                spinner.show();
                btn_icon.hide();

                let email = $("#footer_email").val();
                let footer_email = $("#footer_email");
                let adforest_action = "footer_action";
                let is_valid_email = adforestValidateEmail(email);

                if (is_valid_email) {
                    footer_email.prop("disabled", true);
                    $.ajax({
                        url: adforest_ajax_url,
                        type: 'POST',
                        data: {
                            action: 'adforest_mailchimp_subcribe',
                            adforest_action,
                            security: $("#adforest_mailchimp_subcribe_nonce").val()
                        }
                    }).done(function (response) {
                        button.prop('disabled', false);
                        spinner.hide();
                        btn_icon.show();
                        footer_email.prop("disabled", false);

                        if (response.success) {
                            // toastr.success(response.data.msg, 'Success', { timeOut: 2500, "closeButton": true, "positionClass": "toast-bottom-right" });
                            toastr.success('<?php echo adforest_translate('mc_success_msg'); ?>', '<?php echo adforest_translate('cart_success'); ?>!', { timeOut: 2500, "closeButton": true, "positionClass": "toast-bottom-right" }); $('#sb_email').val('');
                        } else { toastr.error('<?php echo adforest_translate('mc_error_msg'); ?>', '<?php echo adforest_translate('cart_error'); ?>!', { timeOut: 2500, "closeButton": true, "positionClass": "toast-bottom-right" }); }
                    }).fail(function () {
                        button.prop('disabled', false);
                        spinner.hide();
                        btn_icon.show();
                        footer_email.prop("disabled", false);
                        toastr.error('<?php echo adforest_translate('mc_error_msg'); ?>', '<?php echo adforest_translate('cart_error'); ?>!', { timeOut: 2500, "closeButton": true, "positionClass": "toast-bottom-right" });
                    });
                } else {
                    button.prop('disabled', false);
                    spinner.hide();
                    btn_icon.show();
                    footer_email.prop("disabled", false);
                    toastr.error('<?php echo adforest_translate('email_error_msg'); ?>', '<?php echo adforest_translate('cart_error'); ?>!', { timeOut: 2500, "closeButton": true, "positionClass": "toast-bottom-right" });
                }
            });
        })(jQuery);


        function adforestValidateEmail(email) {
            var filter = /^[\w\-\.\+]+\@[a-zA-Z0-9\.\-]+\.[a-zA-z0-9]{2,4}$/;
            if (filter.test(email)) {
                return true;
            } else {
                return false;
            }
        }
    </script>
    <style type="text/css">
        <?php if (isset($adforest_theme['search_breadcrumb_bg']['url']) && $adforest_theme['search_breadcrumb_bg']['url'] != "") { ?>
            .breadcrumb-1 {
                background: rgba(0, 0, 0, 0) url("<?php echo esc_url($adforest_theme['search_breadcrumb_bg']['url']); ?>") center center no-repeat;
                background-color: #6c6e73;
                background-repeat: no-repeat;
                background-size: cover;
                color: #fff;
                position: relative;
            }

        <?php }


        if ($adforest_theme['footer_bg']['url'] != "" && $adforest_theme['footer_options'] == 'with_bg') { ?>
            .adt-footer-section {
                background-color: #232323;
                background-position: center center;
                background-repeat: no-repeat;
                background-size: cover;
                color: #c9c9c9;
                background-image: url("<?php echo esc_url($adforest_theme['footer_bg']['url']); ?>");
                position: relative;
            }

        <?php }
        if (isset($adforest_theme['design_type']) && $adforest_theme['design_type'] == 'classic' && $adforest_theme['breadcrumb_bg']['url'] != "") { ?>
            .page-header-area {
                background: rgba(0, 0, 0, 0) url("<?php echo esc_url($adforest_theme['breadcrumb_bg']['url']); ?>") no-repeat scroll center center / cover !important;
                padding: 50px 0;
                text-align: left;
                position: relative;
            }

        <?php }
        if (isset($adforest_theme['design_type']) && $adforest_theme['design_type'] == 'modern' && isset($adforest_theme['sb_header']) && $adforest_theme['sb_header'] == 'modern' && $adforest_theme['breadcrumb_bg_modern_header']['url'] != "" && $adforest_theme['ad_layout_style_modern'] == '5' && is_singular('ad_post')) { ?>
            .page-header-area {
                background: rgba(0, 0, 0, 0) url("<?php echo esc_url($adforest_theme['breadcrumb_bg_modern_header']['url']); ?>") repeat !important;
                padding: 25px 0;
                text-align: left;
                padding: 25px 0;
            }

        <?php } else if (isset($adforest_theme['design_type']) && $adforest_theme['design_type'] == 'modern' && $adforest_theme['breadcrumb_bg_modern']['url'] != "") { ?>
                .page-header-area {
                    background: rgba(0, 0, 0, 0) url("<?php echo esc_url($adforest_theme['breadcrumb_bg_modern']['url']); ?>") repeat !important;
                    padding: 25px 0;
                    text-align: left;
                    padding: 25px 0;
                }

        <?php } ?>
    </style>

    <?php
}

add_action('wp_footer', 'process_newsletter_email', "100");