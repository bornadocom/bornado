(function ($) {
    var confirm_btn = $('#confirm_btn').val();
    var cancel_btn = $('#cancel_btn').val();
    var confirm_text = $('#confirm_text').val();
    var confirm_profile = $('#confirm_profile').val();
    var adforest_ajax_url = $('#adforest_ajax_url').val();
    var adforest_update_ad_status_nonce = $('#sb_update_ad_status_nonce').val();
    var adforest_bump_it_up_nonce = $('#sb_bump_it_up_nonce').val();
    var adforest_featured_ad_nonce = $('#sb_feature_ad_nonce').val();
    var adforest_delete_ad_alert_nonce = $('#sb_delete_ad_alert_nonce').val();
    var adforest_remove_ad_nonce = $('#sb_remove_ad_nonce').val();
    var mark_all_notifications_read_nonce = $('#mark_all_notifications_read_nonce').val();
    window.adforest_update_ad_status_nonce = adforest_update_ad_status_nonce || '';
    window.adforest_bump_it_up_nonce = adforest_bump_it_up_nonce || '';
    window.adforest_featured_ad_nonce = adforest_featured_ad_nonce || '';
    window.adforest_delete_ad_alert_nonce = adforest_delete_ad_alert_nonce || '';
    window.adforest_remove_ad_nonce = adforest_remove_ad_nonce || '';

    setTimeout(function () {
        $('body').addClass('loaded');
    }, 3000);

    $(document).on('click', '.adforest-email-verification-resend', function (e) {
        e.preventDefault();
        var $btn = $(this);
        var security = $(this).data('security');

        if ($btn.hasClass('processing')) {
            return;
        }

        $btn.addClass('processing');

        var originalText = $btn.data('original-text');
        if (!originalText) {
            originalText = $.trim($btn.text());
            $btn.data('original-text', originalText);
        }

        var loadingText = $btn.data('loading-text') || originalText;
        var successText = $btn.data('success-text') || originalText;

        $btn.prop('disabled', true).text(loadingText);

        var resetButton = function (delay) {
            setTimeout(function () {
                $btn.prop('disabled', false)
                    .removeClass('processing')
                    .text(originalText);
            }, delay);
        };

        $.post(adforest_ajax_url, { action: 'adforest_resend_email_verification', security })
            .done(function (response) {
                if (response && response.success) {
                    var successMessage = (response.data && response.data.message) ? response.data.message : successText;
                    toastr.success(successMessage, '', {
                        timeOut: 4000,
                        "closeButton": true,
                        "positionClass": "toast-top-right"
                    });
                    $btn.text(successText);
                    resetButton(3000);
                } else {
                    var errorMessage = (response && response.data && response.data.message) ? response.data.message : $('#_nonce_error').val();
                    toastr.error(errorMessage, '', {
                        timeOut: 4000,
                        "closeButton": true,
                        "positionClass": "toast-top-right"
                    });
                    resetButton(0);
                }
            })
            .fail(function () {
                toastr.error($('#_nonce_error').val(), '', {
                    timeOut: 4000,
                    "closeButton": true,
                    "positionClass": "toast-top-right"
                });
                resetButton(0);
            });
    });


    // Check if the changePasswordModal exists before initializing the modal and adding an event listener
    const changePasswordModalElement = document.getElementById('changePasswordModal');
    if (changePasswordModalElement) {
        const changePasswordModal = new bootstrap.Modal(changePasswordModalElement);
        const changePasswordButton = document.querySelector('.changePasswordDashboard');
        if (changePasswordButton) {
            changePasswordButton.addEventListener('click', function () {
                changePasswordModal.show();
            });
        }
    }

    // Check if the verification_modal exists before initializing the modal and adding an event listener
    const verificationModalElement = document.getElementById('verification_modal');
    if (verificationModalElement) {
        const phoneVerificationModal = new bootstrap.Modal(verificationModalElement);
        const verifyPhoneButton = document.querySelector('#sb-verify-phone');
        if (verifyPhoneButton) {
            verifyPhoneButton.addEventListener('click', function () {
                phoneVerificationModal.show();
            });
        }
    }

    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    })

    /* Update user profile */
    $(document).on('click', '#sb_verification_ph,#resend_now', function () {
        let security = $(this).data('security');
        var ph_number = $('#sb_ph_number').val();
        $('#sb_verification_ph_code').hide();
        $('#sb_verification_ph').hide();
        $('#sb_verification_ph_back').show();
        $.post(adforest_ajax_url, {
            action: 'sb_verification_system',
            sb_phone_numer: ph_number,
            security
        }).done(function (response) {
            var res_arr = response.split("|");
            if ($.trim(res_arr[0]) != "0") {
                $('#sb_verification_ph_back').hide();
                $('.sb_ver_ph_div').hide();
                $('.sb_ver_ph_code_div').show();
                $('#sb_verification_ph_code').show();
                toastr.success(res_arr[1], '', {
                    timeOut: 4000,
                    "closeButton": true,
                    "positionClass": "toast-top-right"
                });
            } else {
                $('#sb_verification_ph').show();
                $('#sb_verification_ph_back').hide();
                toastr.error(res_arr[1], '', { timeOut: 4000, "closeButton": true, "positionClass": "toast-top-right" });
            }
        });
    });
    $(document).on('click', '#change_pwd', function () {
        $('#sb_loading').show();
        $.post(adforest_ajax_url, {
            action: 'sb_change_password',
            security: $('#sb-profile-reset-pass-token').val(),
            sb_data: $("form#sb-change-password").serialize(),
        }).done(function (response) {
            $('#sb_loading').hide();
            var get_r = response.split('|');
            if ($.trim(get_r[0]) == '1') {
                toastr.success(get_r[1], '', { timeOut: 4000, "closeButton": true, "positionClass": "toast-top-right" });
                $('#myModal').modal('hide');
                window.location = $('#login_page').val();
            } else {
                toastr.error(get_r[1], '', { timeOut: 4000, "closeButton": true, "positionClass": "toast-top-right" });
            }
        }).fail(function () {
            $('#sb_loading').hide();
            toastr.error($('#_nonce_error').val(), '', {
                timeOut: 4000,
                "closeButton": true,
                "positionClass": "toast-top-right"
            });
        });
    });
    $(document).on('submit', '#sb_update_profile', function (e) {
        e.preventDefault();
        $('#sb_loading').show();
        $.post(adforest_ajax_url, {
            action: 'sb_update_profile',
            security: $('#sb-profile-token').val(),
            sb_data: $("form#sb_update_profile").serialize(),
        }).done(function (response) {
            $('#sb_loading').hide();
            if ($.trim(response) == '1') {
                var sb_user_name = $.sanitize($('input[name=sb_user_name]').val());
                var sb_user_address = $.sanitize($('input[name=sb_user_address]').val());
                var sb_user_type = $.sanitize($('select[name=sb_user_type]').val());
                if (sb_user_name != '') {
                    $('.sb_put_user_name').html(sb_user_name);
                }
                if (sb_user_address != '') {
                    $('.sb_put_user_address').html(sb_user_address);
                }
                if (sb_user_type != '') {
                    $('.sb_user_type').html(sb_user_type);
                }
                toastr.success($('#adforest_profile_msg').val(), '', {
                    timeOut: 4000,
                    "closeButton": true,
                    "positionClass": "toast-top-right"
                });
                location.reload();
            } else {

                toastr.error(response, '', { timeOut: 4000, "closeButton": true, "positionClass": "toast-top-right" });
            }
        }).fail(function () {
            $('#sb_loading').hide();
            toastr.error($('#_nonce_error').val(), '', {
                timeOut: 4000,
                "closeButton": true,
                "positionClass": "toast-top-right"
            });
        }
        );
    });


    /*Location while ad posting*/
    $('#sb_user_address').on('focus', function () {

        var map_type = sb_options.adforest_map_type;
        if (map_type !== 'google_map') { return; }
        // Phase A compatibility shim — adforest_location is defined by
        // adforest_load_search_countries() inline script (exposed via
        // window.adforest_location). On new Google Cloud projects the
        // legacy Autocomplete constructor is missing — feature-detect
        // before calling so the focus handler doesn't crash. The input
        // remains usable for manual address entry.
        if (!(window.google
            && google.maps
            && google.maps.places
            && typeof google.maps.places.Autocomplete === 'function')) {
            if (!window.__adfGPlacesWarned) {
                window.__adfGPlacesWarned = true;
                if (window.console && console.warn) {
                    console.warn('AdForest: Google Places Autocomplete unavailable. Falling back to manual address input.');
                }
            }
            return;
        }
        if (typeof window.adforest_location === 'function') {
            window.adforest_location();
        }
    });


    $('body').on('click', '.user_list', function () {

        $('#sb_loading').show();
        $('.message-history-active').removeClass('message-history-active');
        $(this).addClass('message-history-active');
        var second_user = $(this).attr('second_user');
        var inbox = $(this).attr('inbox');
        var msg_token = $(this).attr('sb_msg_token');
        var prnt = 'no';
        if (inbox == 'yes') {
            prnt = 'yes';
        }


        $('.block_user').hide();
        $('.unblock_user').hide();
        $('.block-to-' + second_user).show();
        $('.hide_receiver').hide();
        var cid = $(this).attr('cid');
        $('#' + second_user + '_' + cid).html('');
        $.post(adforest_ajax_url, {
            action: 'sb_get_messages',
            security: msg_token,
            ad_id: cid,
            user_id: second_user,
            receiver: second_user,
            inbox: prnt
        }).done(function (response) {
            $('#usr_id').val(second_user);
            $('#rece_id').val(second_user);
            $('#msg_receiver_id').val(second_user);
            $('#ad_post_id').val(cid)
            $('#sb_loading').hide();
            $('#messages').html(response);
            var dd_bottom = $('.list-wraps');
            $(dd_bottom).prop({ scrollTop: $('.messages').prop("scrollHeight") - 590 });
        }).fail(function () {
            $('#sb_loading').hide();
            $('#messages').html($('#_nonce_error').val());
        });
    });
    $(document).on('click', '.ad_title_show', function () {
        var cur_ad_id = $(this).attr('cid');
        $('.sb_ad_title').hide();
        $('#title_for_' + cur_ad_id).show();
    });
    $('body').on('click', '.remove_fav_ad', function (e) {
        var $this = $(this);
        var id = $this.attr('data-aaa-id');
        var nonce = $this.data('nonce') || $this.attr('data-nonce');

        // Guard against malformed buttons (missing nonce) so we surface a clear error
        // rather than triggering the backend "Security check failed" response.
        if (!nonce || !id) {
            toastr.error(adforest_pkg_data.i18n.missingData, '', {
                timeOut: 4000, "closeButton": true, "positionClass": "toast-top-right"
            });
            return;
        }

        $('#sb_loading').show();
        $.post(adforest_ajax_url, {
            action: 'sb_fav_remove_ad',
            ad_id: id,
            security: nonce
        }).done(function (response) {
            var get_r = (response || '').split('|');
            $('#sb_loading').hide();
            if ((get_r[0] || '').trim() == '1') {
                // Row fade-out + remove, with fallback for buttons not wrapped in a .holder-<id> container
                var $row = $('body').find('.holder-' + id);
                if ($row.length) {
                    $row.fadeOut(300, function () { $(this).remove(); });
                } else {
                    $this.closest('tr').fadeOut(300, function () { $(this).remove(); });
                }
                toastr.success(get_r[1], '', { timeOut: 4000, "closeButton": true, "positionClass": "toast-top-right" });
            } else {
                toastr.error(get_r[1], '', { timeOut: 4000, "closeButton": true, "positionClass": "toast-top-right" });
            }
        }).fail(function () {
            $('#sb_loading').hide();
            toastr.error(adforest_pkg_data.i18n.networkError, '', {
                timeOut: 4000, "closeButton": true, "positionClass": "toast-top-right"
            });
        });
    });
    (function ($) {
        $.sanitize = function (input) {
            var output = input.replace(/<script[^>]*?>.*?<\/script>/gi, '').replace(/<[\/\!]*?[^<>]*?>/gi, '').replace(/<style[^>]*?>.*?<\/style>/gi, '').replace(/<![\s\S]*?--[ \t\n\r]*>/gi, '');
            return output;
        };
    })(jQuery);

    if ($("#upload_user_dp").length > 0) {
        $('#upload_user_dp').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            $('#imgInp').click();
        });
    }

    $('#imgInp').on('change', function (e) {
        var files = e.target.files;
        if (files && files[0]) {
            var fd = new FormData();

            fd.append('my_file_upload[0]', files[0]);
            fd.append('action', 'upload_user_pic');
            let security = $(this).data('security')
            fd.append('security', security);

            $('#sb_loading').show();
            $.ajax({
                type: 'POST',
                url: adforest_ajax_url,
                data: fd,
                contentType: false,
                processData: false,
                success: function (res) {
                    $('#sb_loading').hide();
                    var res_arr = res.split("|");
                    if ($.trim(res_arr[0]) == "1") {
                        $('#user_dp').attr('src', res_arr[1]);
                        $('#img-upload').attr('src', res_arr[1]);

                        $('#imgInp').val('');
                    } else {
                        toastr.error(res_arr[1], '', {
                            timeOut: 4000,
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        });
                    }
                },
                error: function () {
                    $('#sb_loading').hide();
                    toastr.error(adforest_pkg_data.i18n.uploadFailed, '', {
                        timeOut: 4000,
                        "closeButton": true,
                        "positionClass": "toast-top-right"
                    });
                }
            });
        }
    });
    /*
     * Assign role vendor to user on profile page.
     */
    $('#role_as_vendor').on('click', function () {
        var user_id = $('#role_as_vendor').attr("data-user_id");
        var vendor_approve = $('#role_as_vendor').attr('data-vendor_approve');
        $('#sb_loading').show();
        $.post(adforest_ajax_url, {
            action: 'sb_change_role_user_to_vendor',
            user_id: user_id,
            vendor_approve: vendor_approve,
            security: adforest_pkg_data.vendor_role_nonce
        }).done(function (response) {
            $('#sb_loading').hide();
            window.location.reload();
            toastr.success(response, '', {
                timeOut: 4000,
                "closeButton": true,
                "positionClass": "toast-top-right"
            });
            location.reload();
        });
    });

    $(document).on('click', '.sb_make_feature_ad', function () {
        adID = $(this).attr('data-aaa-id');
        var url = $(this).attr('data-url');
        $.confirm({
            title: confirm_text,
            content: '',
            theme: 'Material',
            closeIcon: true,
            animation: 'scale',
            type: 'blue',
            buttons: {
                confirm: {
                    text: confirm_btn,
                    action: function () {

                        if (url != undefined) {
                            window.location.href = url;
                            return;
                        }
                        $('#sb_loading').show();
                        $.post(adforest_ajax_url, { action: 'sb_make_featured', ad_id: adID, nonce: window.adforest_featured_ad_nonce }).done(function (response) {
                            $('#sb_loading').hide();
                            if (response.success) {
                                toastr.success(response.data.message, '', {
                                    timeOut: 4000,
                                    "closeButton": true,
                                    "positionClass": "toast-top-right"
                                });
                                if (response.data.url) {
                                    location.replace(response.data.url);
                                } else {
                                    window.location.reload();
                                }
                            } else {
                                toastr.error(response.data.message, '', {
                                    timeOut: 4000,
                                    "closeButton": true,
                                    "positionClass": "toast-top-right"
                                });
                            }
                        });
                    },
                },
                cancel: {
                    text: cancel_btn,
                    function() {

                    },
                }

            }
        });
    });

    $(document).on('click', '.sb_make_feature_ad_new_pkg', async function () {
        $('#sb_loading').show();
        adID = $(this).attr('data-aaa-id');
        var url = $(this).attr('data-url');
        let formId = "make_listing_feartured_dashboard";
        let ad_packages = await fetchAdPackages(adforest_ajax_url, adID, formId);

        $('#sb_loading').hide();

        $.dialog({
            title: adforest_pkg_data.packageText,
            content: ad_packages,
            theme: 'Material',
            closeIcon: true,
            animation: 'scale',
            type: 'blue',
        });
    });

    $(document).on('submit', '#make_listing_feartured_dashboard', function (e) {
        e.preventDefault();

        var $form = $(this);
        var $submitBtn = $form.find('button[type="submit"], input[type="submit"]');

        $submitBtn.prop('disabled', true);
        $('#sb_loading').show();

        var ad_id = $form.find('input[name="ad_id"]').val();
        var ads_package = $form.find('input[name="ads_package"]:checked').val();

        $.ajax({
            url: adforest_ajax_url,
            method: 'POST',
            data: {
                action: 'sb_make_featured',
                ad_id: ad_id,
                ads_package: ads_package,
                nonce: window.adforest_featured_ad_nonce
            },
            success: function (response) {
                console.log(response);
                if (response.success) {
                    toastr.success(response.data.message, '', {
                        timeOut: 4000,
                        closeButton: true,
                        positionClass: 'toast-top-right'
                    });
                    if (response.data.url) {
                        location.replace(response.data.url);
                    } else {
                        window.location.reload();
                    }
                } else {
                    toastr.error(response.data.message, '', {
                        timeOut: 4000,
                        closeButton: true,
                        positionClass: 'toast-top-right'
                    });
                }
            },
            error: function (error) {
                console.error("Error submitting form", error);
            },
            complete: function () {
                $('#sb_loading').hide();
                $submitBtn.prop('disabled', false);
            }
        });
    });

    $(document).on('click', '.delete-my-events', function () {
        event_id = $(this).attr('data-myevent-id');
        $.confirm({
            title: confirm_text,
            content: '',
            theme: 'Material',
            closeIcon: true,
            animation: 'scale',
            type: 'blue',
            buttons: {
                confirm: {
                    text: confirm_btn,
                    action: function () {
                        $('#sb_loading').show();
                        $.post(adforest_ajax_url, {
                            action: 'remove_my_event',
                            event_id: event_id,
                            security: adforest_pkg_data.security,
                        }).done(function (response) {
                            $('#sb_loading').hide();
                            var get_r = response.split('|');
                            if ($.trim(get_r[0]) == '1') {
                                toastr.success(get_r[1], '', {
                                    timeOut: 4000,
                                    "closeButton": true,
                                    "positionClass": "toast-top-right"
                                });
                            } else {
                                toastr.success(get_r[1], '', {
                                    timeOut: 4000,
                                    "closeButton": true,
                                    "positionClass": "toast-top-right"
                                });
                            }
                        });
                    },
                },
                cancel: {
                    text: cancel_btn,
                    function() {

                    },
                }

            }
        });
    });
    $(document).on('click', '.bump_it_up', function () {
        adID = $(this).attr('data-aaa-id');
        $.confirm({
            title: confirm_text,
            content: '',
            theme: 'Material',
            closeIcon: true,
            animation: 'scale',
            type: 'blue',
            buttons: {
                confirm: {
                    text: confirm_btn,
                    action: function () {

                        $('#sb_loading').show();
                        $.post(adforest_ajax_url, {
                            action: 'sb_bump_it_up',
                            ad_id: adID,
                            nonce: window.adforest_bump_it_up_nonce
                        }).done(function (response) {
                            $('#sb_loading').hide();
                            // var get_r = response.split('|');
                            // var get_rs = response;
                            if (true === response.success) {
                                toastr.success(response.data.message, '', {
                                    timeOut: 4000,
                                    "closeButton": true,
                                    "positionClass": "toast-top-right"
                                });
                                if (response.data.url) {
                                    location.replace(response.data.url);
                                } else {
                                    window.location.reload();
                                }

                            } else {
                                toastr.error(response.data.message, '', {
                                    timeOut: 4000,
                                    "closeButton": true,
                                    "positionClass": "toast-top-right"
                                });
                            }
                        });
                    },
                },
                cancel: {
                    text: cancel_btn,
                    function() {

                    },
                }
            }
        });
    });

    $(document).on('click', '.bump_it_up_new_pkg', async function () {
        $('#sb_loading').show();
        var adID = $(this).attr('data-aaa-id');
        let formId = "make_listing_bump_up_dashboard";
        var ad_packages = await fetchAdPackages(adforest_ajax_url, adID, formId);
        $('#sb_loading').hide();
        $.dialog({
            title: adforest_pkg_data.i18n.selectPackage,
            content: ad_packages,
            theme: 'Material',
            closeIcon: true,
            animation: 'scale',
            type: 'blue',
        });
    });

    $(document).on('submit', '#make_listing_bump_up_dashboard', function (e) {
        e.preventDefault();
        $('#sb_loading').show();

        var ad_id = $(this).find('input[name="ad_id"]').val();
        var ads_package = $(this).find('input[name="ads_package"]:checked').val();
        console.log(window.adforest_bump_it_up_nonce)

        $.ajax({
            url: adforest_ajax_url,
            method: 'POST',
            data: {
                action: 'sb_bump_it_up',
                ad_id: ad_id,
                ads_package: ads_package,
                nonce: window.adforest_bump_it_up_nonce
            },
            success: function (response) {
                $('#sb_loading').hide();
                if (response.success) {
                    toastr.success(response.data.message, '', {
                        timeOut: 4000,
                        "closeButton": true,
                        "positionClass": "toast-top-right"
                    });
                    if (response.data.url) {
                        location.replace(response.data.url);
                    } else {
                        window.location.reload();
                    }
                } else {
                    toastr.error(response.data.message, '', {
                        timeOut: 4000,
                        "closeButton": true,
                        "positionClass": "toast-top-right"
                    });
                }
            },
            error: function (error) {
                $('#sb_loading').hide();
                console.error("Error submitting form", error);
            }
        });
    });

    /*My ads pagination*/
    // $('body').on('focus', '.ad_status', function () {
    //     previous = this.value;
    // }).on('click', '.ad_status', function () {
    //     adID = $(this).attr('data-adid');
    //     if (adID != "") {
    //         var $this = $(this);
    //         var status_val = $(this).attr('data-value');
    //         var bg_color_status = '#4caf50';
    //         if (status_val == 'active') {
    //             bg_color_status = '#4caf50';
    //         } else if (status_val == 'sold') {
    //             bg_color_status = '#3498db';
    //         } else if (status_val == 'expired') {
    //             bg_color_status = '#d9534f';
    //         }
    //         $.confirm({
    //             title: confirm_text,
    //             content: '',
    //             theme: 'Material',
    //             closeIcon: true,
    //             animation: 'scale',
    //             type: 'blue',
    //             buttons: {
    //                 confirm: {
    //                     text: confirm_btn,
    //                     action: function () {
    //                         $('#sb_loading').show();
    //                         $.post(adforest_ajax_url, {
    //                             action: 'sb_update_ad_status',
    //                             ad_id: adID,
    //                             status: status_val
    //                         }).done(function (response) {
    //                             $('#sb_loading').hide();
    //                             var get_r = response.split('|');
    //                             if ($.trim(get_r[0]) == '1') {
    //                                 toastr.success(get_r[1], '', {
    //                                     timeOut: 4000,
    //                                     "closeButton": true,
    //                                     "positionClass": "toast-top-right"
    //                                 });
    //                                 previous = this.value;
    //                                 jQuery('#status-dyn-' + $this.attr('adid') + '').css({
    //                                     "background-color": bg_color_status,
    //                                     "text-transform": "capitalize"
    //                                 });
    //                                 window.location.reload();
    //                                 //  jQuery('#status-dyn-' + $this.attr('adid') + '').html(status_text);
    //                             } else {
    //                                 toastr.error(get_r[1], '', {
    //                                     timeOut: 4000,
    //                                     "closeButton": true,
    //                                     "positionClass": "toast-top-right"
    //                                 });
    //                             }
    //                         });
    //                     },
    //                 },
    //                 cancel:
    //                 {
    //                     text: cancel_btn,
    //                     function() {
    //                     },
    //                 }
    //             }
    //         });
    //     }
    // });

    $(document).on('click', '.ad_package_info', function () {
        $('#sb_loading').show();
        var id = $(this).attr('data-adid');
        var nonce = $(this).attr('data-nonce');
        $.post(adforest_ajax_url, {
            action: 'sb_get_ad_package_info',
            ad_id: id,
            nonce
        }).done(function (response) {
            $('#sb_loading').hide();
            $.dialog({
                title: $('#ad_info_text').val(),
                content: response,
            });
        }).fail(function (response) {
            $('#sb_loading').hide();
            toastr.error(response, '', {
                timeOut: 4000,
                "closeButton": true,
                "positionClass": "toast-top-right"
            });
        });
    });

    $('body').on('click', '.remove_ad', function (e) {
        var id = $(this).attr('data-adid');
        $.confirm({
            title: confirm_text,
            content: '',
            theme: 'Material',
            closeIcon: true,
            animation: 'scale',
            type: 'blue',
            buttons: {
                confirm: {
                    text: confirm_btn,
                    action: function () {
                        $('#sb_loading').show();
                        $.post(adforest_ajax_url, { action: 'sb_remove_ad', ad_id: id, nonce: window.adforest_remove_ad_nonce }).done(function (response) {
                            $('#sb_loading').hide();
                            var get_r = response.split('|');
                            if ($.trim(get_r[0]) == '1') {
                                $('body').find('.holder-' + id).remove();
                                toastr.success(get_r[1], '', {
                                    timeOut: 4000,
                                    "closeButton": true,
                                    "positionClass": "toast-top-right"
                                });
                                window.location.reload();
                            } else {
                                toastr.error(get_r[1], '', {
                                    timeOut: 4000,
                                    "closeButton": true,
                                    "positionClass": "toast-top-right"
                                });
                            }
                        });
                    },
                },
                cancel: {
                    text: cancel_btn,
                    function() {

                    },
                }
            }
        });
    });
    /*select profile tabs*/
    $(document).on('click', '.messages_actions', function () {
        var sb_action = $(this).attr('sb_action');
        if (sb_action != "") {
            $('#sb_loading').show();
            $.post(adforest_ajax_url, { action: sb_action }).done(function (response) {
                $('#sb_loading').hide();
                $('#adforest_res').html(response);
                $('[data-toggle="tooltip"]').tooltip();
                var dd_bottom = $('.list-wraps');
                $(dd_bottom).prop({ scrollTop: $(dd_bottom).prop("scrollHeight") });
            });
        }
    });
    var dd_bottoms = $('.package-details');
    if (dd_bottoms.length > 0) {
        // const pss = new PerfectScrollbar(".package-details");
    }


    var dd_bottom = $('.sms-notification-admin');
    if (dd_bottom.length > 0) {
        // const pss = new PerfectScrollbar(".sms-notification-admin");
    }


    /*Load Messages*/
    $('body').on('click', '.get_msgs', function () {
        $('#sb_loading').show();
        $.post(adforest_ajax_url, {
            action: 'sb_load_messages',
            ad_id: $(this).attr('ad_msg'),
        }).done(function (response) {
            $('#sb_loading').hide();
            $('#adforest_res').html(response);
            if ($('#file_attacher').length > 0) {
                adforest_ajax_url = adforest_ajax_url;
                var attachmentsDropzone = new Dropzone(document.getElementById('file_attacher'), {
                    url: adforest_ajax_url,
                    autoProcessQueue: true,
                    previewsContainer: "#attachment-wrapper",
                    previewTemplate: '<span class="dz-preview dz-file-preview"><span class="dz-details"><span class="dz-filename"><i class="fa fa-link"></i>&nbsp;&nbsp;&nbsp;<span data-dz-name></span></span>&nbsp;&nbsp;&nbsp;<span class="dz-size" data-dz-size></span>&nbsp;&nbsp;&nbsp;<i class="fa fa-times" style="cursor:pointer;font-size:15px;" data-dz-remove></i></span><span class="dz-progress"><span class="dz-upload" data-dz-uploadprogress></span></span><i class="ti ti-refresh ti-spin"></i></span>',
                    clickable: "a.msgAttachFile",
                    acceptedFiles: $('#provided_format').val(),
                    maxFilesize: 15,
                    maxFiles: 4
                });
                attachmentsDropzone.on("sending", function () {
                    console.log("eeeee");
                    $("#send_msg ,#send_ad_message").attr("disabled", true);
                });
                attachmentsDropzone.on("queuecomplete", function () {
                    $("#send_msg, #send_ad_message").attr("disabled", false);
                });
            }
            var dd_bottom = $('.list-wraps');
            $(dd_bottom).prop({ scrollTop: $(dd_bottom).prop("scrollHeight") });


            if ($('#is_mobile').length > 0) {
                if ($('#is_mobile').val() == '1') {
                    setTimeout(function () {
                        $('.list-wrap').attr('data-ps-id', '');

                        $('.list-wraps').attr('data-ps-id', '');
                        $('.list-wraps').css({ "maxHeight": "unset" });
                    }, 1000);
                }
            }


        });
    });

    $('body').on('click', '#send_msg', function () {
        $('#send_chat_message').on('submit', function (e) {
            e.preventDefault();

            let inbox = $('#send_msg').attr('inbox');
            let sb_msg_token = $('#send_msg').attr('sb_msg_token');
            let prnt = (inbox === 'yes') ? 'yes' : 'no';

            let fd = new FormData();
            if ($('#file_attacher').length > 0) {
                let fileUpload = $('#file_attacher').get(0).dropzone;

                if (fileUpload !== undefined) {
                    let files = fileUpload.files;
                    for (let i = 0; i < files.length; i++) {
                        fd.append("message_file[]", files[i]);
                    }
                }
            }

            let sb_data = $(this).serialize();
            fd.append('action', 'sb_send_message');
            fd.append('sb_data', sb_data);
            fd.append('security', sb_msg_token);

            $('#sb_loading').show();
            $.ajax({
                type: 'POST',
                url: adforest_ajax_url,
                data: fd,
                contentType: false,
                processData: false,
                success: function (response) {
                    console.log(response);
                    $('#sb_loading').hide();

                    let get_r = response.split('|');
                    if ($.trim(get_r[0]) == '1') {
                        toastr.success(get_r[1], '', {
                            timeOut: 4000,
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        });
                        $('#sb_forest_message').val('');
                        if ($('.dz-preview').length > 0) {
                            $('.dz-preview').remove();
                            Dropzone.forElement('#file_attacher').removeAllFiles(true);
                        }
                        $.post(adforest_ajax_url, {
                            action: 'sb_get_messages',
                            security: sb_msg_token,
                            ad_id: $("#ad_post_id").val(),
                            user_id: $('#usr_id').val(),
                            inbox: prnt
                        }).done(function (response) {
                            let get_r = response.split('|');
                            if (response !== undefined && $.trim(get_r[0]) == '0') {
                                toastr.error(get_r[1], '', {
                                    timeOut: 4000,
                                    "closeButton": true,
                                    "positionClass": "toast-top-right"
                                });
                            } else {
                                $('#messages').html(response);
                                $('.message-details .list-wraps').scrollTop(20000).perfectScrollbar('update');
                            }
                        });
                    } else {
                        toastr.error(get_r[1], '', {
                            timeOut: 4000,
                            "closeButton": true,
                            "positionClass": "toast-top-right"
                        });
                        $(".close").trigger("click");
                    }
                },
                error: function () {
                    $('#sb_loading').hide();
                    toastr.error($('#_nonce_error').val(), '', {
                        timeOut: 4000,
                        "closeButton": true,
                        "positionClass": "toast-top-right"
                    });
                }
            });
            return false;
        });
    })

    if (jQuery('[data-fancybox]').length > 0) {
        jQuery('[data-fancybox]').fancybox();
    }


    /*======== DROPDOWN NOTIFY ========*/
    var dropdownToggle = $('.notify-toggler');
    var dropdownNotify = $('.dropdown-notify');
    if (dropdownToggle.length !== 0) {
        dropdownToggle.on('click', function () {
            if (!dropdownNotify.is(':visible')) {
                dropdownNotify.fadeIn(5);
            } else {
                dropdownNotify.fadeOut(5);
            }
        });
    }

    $("#sidebar-toggler").on("click", function () {
        var sidebar = $('.sidebar-fixed');
        if (
            sidebar.hasClass("sidebar-fixed") ||
            sidebar.hasClass("sidebar-static")
        ) {
            $(this)
                .addClass("sidebar-toggle")
                .removeClass("sidebar-offcanvas-toggle");
            if (window.isMinified === false) {
                sidebar
                    .removeClass("sidebar-collapse sidebar-minified-out")
                    .addClass("sidebar-minified");
                window.isMinified = true;
                window.isCollapsed = false;
            } else {
                sidebar.removeClass("sidebar-minified");
                sidebar.addClass("sidebar-minified-out");
                window.isMinified = false;
            }
        }
    });
    if ($(window).width() >= 768 && $(window).width() < 992) {
        var sidebar = $('.sidebar-fixed');
        if (
            sidebar.hasClass("sidebar-fixed") ||
            sidebar.hasClass("sidebar-static")
        ) {
            sidebar
                .removeClass("sidebar-collapse sidebar-minified-out")
                .addClass("sidebar-minified");
            window.isMinified = true;
        }
    }


    if ($(window).width() < 768) {
        $(".sidebar-toggle").on("click", function () {
            // $("body").css("overflow", "hidden");
            $('body').prepend('<div class="mobile-sticky-body-overlay"></div>')
        });
        $(document).on("click", '.mobile-sticky-body-overlay', function (e) {
            $(this).remove();
            sidebar = $('.sidebar-fixed');
            sidebar.removeClass("sidebar-mobile-in").addClass("sidebar-mobile-out");
            $("body").css("overflow", "auto");
        });
    }

    /*======== SIDEBAR TOGGLE FOR MOBILE ========*/
    if ($(window).width() < 768) {
        $(document).on("click", ".sidebar-toggle", function (e) {
            e.preventDefault();
            var min = "sidebar-mobile-in",
                min_out = "sidebar-mobile-out",
                sidebar = $('.sidebar-fixed');
            $(sidebar).hasClass(min)
                ? $(sidebar)
                    .removeClass(min)
                    .addClass(min_out)
                : $(sidebar)
                    .addClass(min)
                    .removeClass(min_out)
        });
    }

    /* Back To Top */

    $(window).scroll(function () {
        var offset = 300,
            offset_opacity = 1200,
            scroll_top_duration = 700,
            $back_to_top = $('.cd-top');
        var ad_post_btn = $('.sticky-post-button');
        ($(this).scrollTop() > offset) ? ad_post_btn.addClass('sticky-post-button-visible') : ad_post_btn.removeClass('sticky-post-button-visible').removeClass('sticky-post-button-fadeout');
        ($(this).scrollTop() > offset) ? $back_to_top.addClass('cd-is-visible') : $back_to_top.removeClass('cd-is-visible cd-fade-out');
        if ($(this).scrollTop() > offset_opacity) {
            $back_to_top.addClass('cd-fade-out');
            ad_post_btn.addClass('sticky-post-button-fadeout');
        }
    });


    $back_to_top = $('.cd-top');
    $(document).on('click', '.cd-top', function (event) {
        event.preventDefault();
        $('body,html').animate({ scrollTop: 0, }, 700);

    });
    /* Candidate Deleting Saved alerts */
    $(".del_save_alert").on("click", function () {
        var alert_id = $(this).attr("data-value");
        $.confirm({
            title: confirm_text,
            content: '',
            theme: 'Material',
            closeIcon: true,
            animation: 'scale',
            type: 'red',
            buttons: {
                confirm: function () {
                    $('#sb_loading').show();
                    $.post(adforest_ajax_url, {
                        action: 'del_job_alerts',
                        alert_id: alert_id,
                        nonce: window.adforest_delete_ad_alert_nonce
                    }).done(function (response) {
                        $('#sb_loading').hide();
                        var get_r = response.split('|');
                        if ($.trim(get_r[0]) == '1') {
                            $("#alert_detail_table_row_" + alert_id).remove();
                            toastr.success(get_r[1], '', {
                                timeOut: 4000,
                                "closeButton": true,
                                "positionClass": "toast-top-right"
                            });
                        } else {
                            toastr.error(get_r[1], '', {
                                timeOut: 4000,
                                "closeButton": true,
                                "positionClass": "toast-top-right"
                            });
                        }
                    });
                },
                cancel: {
                    text: cancel_btn,
                    function() {

                    },
                }
            }
        });
    });

    $(".delete_site_user").on("click", function () {
        userID = $(this).attr('data-user-id');
        let nonce = $("#sb_delete_site_user_nonce").val();
        $.confirm({
            title: confirm_text,
            content: '',
            theme: 'Material',
            closeIcon: true,
            animation: 'scale',
            type: 'red',
            buttons: {
                confirm: {
                    text: confirm_btn,
                    action: function () {
                        $('#sb_loading').show();
                        $.post(adforest_ajax_url, {
                            action: 'delete_site_user_func',
                            del_user_id: userID,
                            nonce
                        }).done(function (response) {
                            $('#sb_loading').hide();
                            var get_r = response.split('|');
                            if ($.trim(get_r[0]) == '1') {
                                toastr.success(get_r[1], '', {
                                    timeOut: 4000,
                                    "closeButton": true,
                                    "positionClass": "toast-top-right"
                                });
                                location.reload();
                            } else {
                                toastr.error(get_r[1], '', {
                                    timeOut: 4000,
                                    "closeButton": true,
                                    "positionClass": "toast-top-right"
                                });
                            }
                        });
                    }
                },
                cancel: {
                    text: cancel_btn,
                    function() {

                    }
                },
            }
        });
    });

    $(document).on('click', '#sb_verification_ph_code', function () {
        var ph_code = $('#sb_ph_number_code').val();
        var security = $('#sb_verification_code_nonce').val();
        $('#sb_verification_ph_code').hide();
        $('#sb_verification_ph_back').show();
        $.post(adforest_ajax_url, { action: 'sb_verification_code', sb_code: ph_code, security: security }).done(function (response) {
            var res_arr = response.split("|");
            if ($.trim(res_arr[0]) != "0") {
                toastr.success(res_arr[1], '', {
                    timeOut: 4000,
                    "closeButton": true,
                    "positionClass": "toast-top-right"
                });
                location.reload();
            } else {
                $('#sb_verification_ph_code').show();
                $('#sb_verification_ph_back').hide();
                toastr.error(res_arr[1], '', { timeOut: 4000, "closeButton": true, "positionClass": "toast-top-right" });
            }
        });
    });
    /*======== 1. SCROLLBAR SIDEBAR ========*/
    // $(".sidebar-scrollbar")
    //     .slimScroll({
    //         opacity: 0,
    //         height: "100%",
    //         color: "#808080",
    //         size: "5px",
    //         wheelStep: 10
    //     })
    //     .mouseover(function () {
    //         $(this)
    //             .next(".slimScrollBar")
    //             .css("opacity", 0.5);
    //     });

    // $("select").select2({ placeholder: $("#select_place_holder"), allowClear: true, width: '100%' });


    $(document).ready(function () {

        if ($('#spinner').length > 0) {
            document.getElementById('spinner').style.display = 'none';
        }
    });


    /* Profile Badge Start */
    $(document).on('click', '#profile-badge', function () {
        adID = $(this).attr('data-aaa-id');
        var url = $(this).attr('data-url');
        $.confirm({
            title: confirm_profile,
            content: '',
            theme: 'Material',
            closeIcon: true,
            animation: 'scale',
            type: 'blue',
            buttons: {
                confirm: {
                    text: confirm_btn,
                    action: function () {
                        $('#sb_loading').show();
                        $.post(adforest_ajax_url, { action: 'sb_profile_badge', ad_id: adID, }).done(function (response) {
                            $('#sb_loading').hide();
                            if (true === response.success) {
                                toastr.success(response.data.message, '', {
                                    timeOut: 4000,
                                    "closeButton": true,
                                    "positionClass": "toast-top-right"
                                });
                                if (response.data.url) {
                                    location.replace(response.data.url);
                                }

                            } else {
                                toastr.error(response.data.message, '', {
                                    timeOut: 4000,
                                    "closeButton": true,
                                    "positionClass": "toast-top-right"
                                });
                            }
                        });
                    },
                },
                cancel: {
                    text: cancel_btn,
                    function() {

                    },
                }

            }
        });
    });

    /* Profile Badge Ends */

    async function fetchAdPackages(adforest_ajax_url, adID, formId) {
        let result;
        try {
            result = await $.ajax({
                url: adforest_ajax_url,
                type: 'POST',
                data: {
                    action: 'load_feature_ad_modal',
                    adID,
                    formId,
                    security: $('#adforest_load_feature_ad_modal_nonce').val(),
                }
            });
            return result;
        } catch (error) {
            console.error(error);
        }
    }

    /*end of directory listing code*/
})(jQuery);

jQuery(document).ready(function ($) {
    let adforest_ajax_url = $('#adforest_ajax_url').val();
    let confirm_text = $('#confirm_text').val();
    let confirm_btn = $('#confirm_btn').val();
    let cancel_btn = $('#cancel_btn').val();
    let no_more_ads_to_load = $('#no_more_ads_to_load').val();
    let load_more_ads_dashboard = $('#load_more_ads_dashboard').val();
    let currentPage = 1;

    $('#load-more-ads').on('click', function (e) {
        let security = $(this).data('security');
        $('#sb_loading').show();
        e.preventDefault();
        currentPage++;

        $.post(adforest_ajax_url, {
            action: 'load_more_ads_dashboard_table',
            page: currentPage,
            security
        }).done((response) => {
            $('#sb_loading').hide();
            let html = response.trim();

            if (html) {
                $('.top-selling-table tbody').append(html);
            } else {
                $('#load-more-ads').text(no_more_ads_to_load).prop('disabled', true);
            }
        })
    });

    var paged = 2;
    $('#load-more-myads').on('click', function (e) {
        e.preventDefault();
        let checkAdType = $(this).attr("data-ad-type");
        let security = $(this).data('security');

        $.ajax({
            url: adforest_ajax_url,
            type: 'POST',
            data: {
                action: 'load_more_dashboard_ads',
                paged: paged,
                ad_type: checkAdType,
                security
            },
            beforeSend: function () {
                $('#load-more-myads').text(adforest_pkg_data.i18n.loading);
            },
            success: function (response) {
                $('[data-toggle="tooltip"]').tooltip();
                if (response === 'no_more_posts') {
                    toastr.error(no_more_ads_to_load, '', {
                        timeOut: 4000,
                        "closeButton": true,
                        "positionClass": "toast-top-right"
                    });
                    // $('#load-more-myads').text(no_more_ads_to_load).prop('disabled', true);
                    $('#load-more-myads').hide();
                } else {
                    if (checkAdType === 'my_ads') {
                        $('.dashboard-my-ads tbody').append(response);
                    } else if (checkAdType === 'featured_ads') {
                        $('.dashboard-feature-ads tbody').append(response);
                    } else if (checkAdType === 'rejected_ads') {
                        $('.dashboard-rejected-ads tbody').append(response);
                    } else if (checkAdType === 'fav_ads') {
                        $('.dashboard-favorite-ads tbody').append(response);
                    } else if (checkAdType === 'inactive_ads') {
                        $('.dashboard-inactive-ads tbody').append(response);
                    } else if (checkAdType === 'expired_ads') {
                        $('.dashboard-expired-ads tbody').append(response);
                    }
                    $('#load-more-myads').text(load_more_ads_dashboard);
                    paged++;
                }
            }
        });
    });

    /* My ads pagination */
    $('body').off('click', '.ad_status').on('click', '.ad_status', function () {
        adID = $(this).attr('data-adid');
        if (adID != "") {
            var $this = $(this);
            var status_val = $(this).attr('data-value');
            var bg_color_status = '#4caf50';
            var adStatusNonce = '';
            if (typeof window.adforest_update_ad_status_nonce !== 'undefined' && window.adforest_update_ad_status_nonce) {
                adStatusNonce = window.adforest_update_ad_status_nonce;
            } else {
                adStatusNonce = $this.data('security');
            }
            if (!adStatusNonce) {
                toastr.error($('#_nonce_error').val(), '', {
                    timeOut: 4000,
                    "closeButton": true,
                    "positionClass": "toast-top-right"
                });
                return;
            }

            if (status_val == 'active') {
                bg_color_status = '#4caf50';
            } else if (status_val == 'sold') {
                bg_color_status = '#3498db';
            } else if (status_val == 'expired') {
                bg_color_status = '#d9534f';
            }

            $.confirm({
                title: confirm_text,
                content: '',
                theme: 'Material',
                closeIcon: true,
                animation: 'scale',
                type: 'blue',
                buttons: {
                    confirm: {
                        text: confirm_btn,
                        action: function () {
                            $('#sb_loading').show();
                            $.post(adforest_ajax_url, {
                                action: 'sb_update_ad_status',
                                ad_id: adID,
                                status: status_val,
                                security: adStatusNonce
                            }).done(function (response) {
                                $('#sb_loading').hide();
                                var get_r = response.split('|');
                                if ($.trim(get_r[0]) == '1') {
                                    toastr.success(get_r[1], '', {
                                        timeOut: 4000,
                                        "closeButton": true,
                                        "positionClass": "toast-top-right"
                                    });
                                    previous = this.value;
                                    $('#status-dyn-' + $this.attr('adid')).css({
                                        "background-color": bg_color_status,
                                        "text-transform": "capitalize"
                                    });
                                    window.location.reload();
                                } else {
                                    toastr.error(get_r[1], '', {
                                        timeOut: 4000,
                                        "closeButton": true,
                                        "positionClass": "toast-top-right"
                                    });
                                }
                            });
                        },
                    },
                    cancel: {
                        text: cancel_btn,
                        action: function () {
                        }
                    }
                }
            });
        }
    });

    document.querySelectorAll('div.tagsinput').forEach(function (div) {
        div.setAttribute('tabindex', '0');
    });

    $(document).on('click', '.notification-item-dash', function (event) {
        const isModifiedClick = event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.which === 2;
        if (isModifiedClick) {
            return;
        }

        const $item = $(this);
        const destination = $item.attr('href');
        if (!destination) {
            return;
        }

        const notificationType = $item.data('notification-type');
        const messageId = $item.data('message-id');
        const notificationId = $item.data('notification-id');
        const payload = {
            action: 'adforest_mark_notification_message_as_read',
            nonce: mark_all_notifications_read_nonce
        };

        if (notificationType === 'theme' && notificationId) {
            payload.notification_type = 'theme';
            payload.notification_id = notificationId;
        } else if (messageId) {
            payload.message_id = messageId;
        } else {
            window.location.href = destination;
            return;
        }

        // Wait for mark-as-read to actually persist server-side before
        // navigating so the destination page renders the freshly
        // decremented count. Two redundant guards prevent the original
        // bug (clicks silently doing nothing) from coming back:
        //   1. Explicit 800ms AJAX timeout — jQuery aborts a slow XHR
        //      and triggers .always() instead of leaving it pending.
        //   2. 1000ms safety setTimeout — fires doNavigate even if the
        //      AJAX layer somehow never resolves.
        event.preventDefault();
        $("#sb_loading").show();
        let navigated = false;
        const doNavigate = function () {
            if (navigated) return;
            navigated = true;
            try { $("#sb_loading").hide(); } catch (e) {}
            window.location.assign(destination);
        };
        try {
            $.ajax({
                type: 'POST',
                url: adforest_ajax_url,
                data: payload,
                timeout: 800
            }).always(doNavigate);
        } catch (e) {
            doNavigate();
        }
        setTimeout(doNavigate, 1000);
    });

    if ($('#sb_user_address').length > 0) {
        // google.maps.event.addDomListener(window, 'load', initAutocomplete)
    }

    if ($("#sb_user_address_leaflet").length > 0) {
        document.getElementById('sb_user_address_leaflet').addEventListener('input', function () {
            let query = this.value;
            let suggestionsBox = document.getElementById('suggestions-box');

            if (query.length < 3) {
                suggestionsBox.innerHTML = "";
                return;
            }

            var _nomCC = (typeof adforestNominatim !== 'undefined' && adforestNominatim.countrycodes) ? '&countrycodes=' + adforestNominatim.countrycodes : '';
            var _nomFT = (typeof adforestNominatim !== 'undefined' && adforestNominatim.featuretype) ? '&featuretype=' + adforestNominatim.featuretype : '';
            var _nomVB = (typeof adforestNominatim !== 'undefined' && (adforestNominatim.defaultLat || adforestNominatim.defaultLng)) ? '&viewbox=' + (adforestNominatim.defaultLng-1) + ',' + (adforestNominatim.defaultLat-1) + ',' + (adforestNominatim.defaultLng+1) + ',' + (adforestNominatim.defaultLat+1) + '&bounded=0' : '';
            fetch("https://nominatim.openstreetmap.org/search?format=json&addressdetails=1&q=" + encodeURIComponent(query) + _nomCC + _nomFT + _nomVB)
                .then(response => response.json())
                .then(data => {
                    if (typeof adforestNominatim !== 'undefined' && adforestNominatim.featuretype) {
                        var _cityTypes = ['city', 'town', 'village', 'municipality', 'administrative'];
                        data = data.filter(function(p) { return _cityTypes.indexOf(p.type) !== -1; });
                    }
                    suggestionsBox.innerHTML = "";
                    data.forEach(place => {
                        let suggestion = document.createElement('div');
                        suggestion.className = 'suggestion-item';
                        suggestion.textContent = place.display_name;
                        suggestion.addEventListener('click', function () {
                            document.getElementById('sb_user_address_leaflet').value = place.display_name;
                            let latInput = $('#sb_user_address_lat');
                            let lngInput = $('#sb_user_address_long');
                            if (latInput && lngInput) {
                                latInput.value = place.lat;
                                lngInput.value = place.lon;
                            }
                            suggestionsBox.innerHTML = "";
                        });
                        suggestionsBox.appendChild(suggestion);
                    });
                })
                .catch(error => {
                    //console.error("Error fetching location suggestions:", error);
                });
        });
    }
});
