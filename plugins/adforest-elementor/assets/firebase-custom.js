(function ($) {

    const loading = $('#sb_loading');
    const sb_options = get_strings;
    const adforest_ajax_url = get_strings.ajax_url;

    if ($("#login_with_phone").length > 0) {
        let loginWithPhoneButton = $("#login_with_phone_btn");
        let loginBtnSpinner = $("#login_with_ph_spinner");
        loginWithPhoneButton.prop('disabled', true);
        let validatePhoneNumber = /^\+?([0-9]{2})\)?[-. ]?([0-9]{8,15})$/;
        $("#sb_reg_phone").on("change input keyup", function () {
            if (($(this).val().match(validatePhoneNumber))) {
                loginWithPhoneButton.prop('disabled', false);
            } else {
                loginWithPhoneButton.prop('disabled', true);
            }
        });

        $('#login_with_phone').on('submit', function (e) {
            e.preventDefault();
            let form = $(this);
            form.parsley().validate();
            loginWithPhoneButton.prop('disabled', true);
            $('#sb_loading').show();
            loginBtnSpinner.show();
            let phoneVal = $("#sb_reg_phone").val();
            let nonce = $("#phone_login_nonce").val();
            if (phoneVal.includes("+")) {
                $.post(adforest_ajax_url, {
                    action: 'sb_login_check_user', form_data: form.serialize(), nonce: nonce
                }).done(function (response) {
                    $('#sb_loading').hide();
                    loginBtnSpinner.hide();
                    if (response['success'] == true) {
                        let projectID = $('#sb-fb-projectid').val();
                        let appID = $('#sb-fb-appid').val();
                        let senderID = $('#sb-fb-senderid').val();
                        let apiKey = $('#sb-fb-apikey').val();
                        let firebaseConfig2 = {
                            apiKey: apiKey, projectId: projectID, messagingSenderId: senderID, appId: appID
                        };
                        // Initialize Firebase
                        !firebase.apps.length ? firebase.initializeApp(firebaseConfig2) : firebase.app()

                        firebase.analytics();
                        firebase.auth().languageCode = document.documentElement.lang;
                        var otpNum = $("#sb_reg_phone").val();
                        window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier('firebase-recaptcha', {
                            'size': 'normal', 'callback': function (response) {
                                // reCAPTCHA solved, allow signInWithPhoneNumber.
                                // ...
                            }, 'expired-callback': function () {
                                // Response expired. Ask user to solve reCAPTCHA aga
                                // ...
                            }
                        });
                        toastr.success($('#verify_recaptcha').val(), '', {
                            timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                        });
                        var cverify = window.recaptchaVerifier;
                        !firebase.apps.length ? firebase.initializeApp(firebaseConfig2) : firebase.app()
                        firebase.auth().signInWithPhoneNumber(otpNum, cverify).then(function (response) {
                            let myModal = new bootstrap.Modal(document.getElementById('verification_modal'));
                            myModal.show();
                            var notice = $('#verification-notice').val() + " " + otpNum;
                            toastr.success(notice, '', {
                                timeOut: 4000, "closeButton": true, "positionClass": "toast-top-right"
                            });
                            window.confirmationResult = response;
                        }).catch(function (error) {

                            toastr.error(error['code'], error['message'], {
                                timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                            });
                        })
                        /*Resend otp */
                        /* Resend code to user number  */
                        $('#sb_verification_resend').on('click', function () {

                            $('#firebase-recaptcha2').show();
                            window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier('firebase-recaptcha2', {
                                'size': 'normal', 'callback': function (response) {
                                    // reCAPTCHA solved, allow signInWithPhoneNumber.
                                    // ...
                                }, 'expired-callback': function () {
                                    // Response expired. Ask user to solve reCAPTCHA aga
                                    // ...
                                }
                            });
                            toastr.success($('#verify_recaptcha').val(), '', {
                                timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                            });
                            let cverify = window.recaptchaVerifier;
                            firebase.auth().signInWithPhoneNumber(otpNum, cverify).then(function (response) {
                                $('#firebase-recaptcha2').hide();
                                let notice = sb_options.verification_notice + " " + otpNum;
                                toastr.success(notice, '', {
                                    timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                                });
                                window.confirmationResult = response;
                            }).catch(function (error) {
                                toastr.error(error['code'], error['message'], {
                                    timeOut: 4000, "closeButton": true, "positionClass": "toast-top-right"
                                });
                            })
                        });
                        /*  verifying otp  */
                        $('#sb_verify_otp').on('click', function () {
                            $("#verify-spinner-icon").show();
                            let otp = $('#sb_ph_number_code').val();
                            let nonce = $('#sb_otp_nonce_phone_login').val();
                            $(this).prop('disabled', true);
                            $("#sb_verification_ph_back").show();

                            confirmationResult.confirm(otp).then(function (response) {
                                let userobj = response.user;
                                let phoneNum = userobj.phoneNumber;


                                // ✅ Get Firebase ID Token
                                userobj.getIdToken().then(function (idToken) {

                                    let form_data = form.serialize();
                                    let ajaxData = {
                                        action: 'sb_login_user_with_otp',
                                        phone_number: phoneNum,
                                        id_token: idToken,   // <-- send to backend
                                        form_data: form_data,
                                        nonce: nonce
                                    };


                                    $.post(adforest_ajax_url, ajaxData).done(function (response) {
                                        $("#verify-spinner-icon").hide();
                                        $("#sb_verification_ph_back").hide();
                                        $('#sb_verify_otp').show();

                                        if (response['success'] == true) {
                                            toastr.success(response['data']['message'], '', {
                                                timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                                            });
                                            window.location = sb_options.sb_after_login_page;
                                        } else {
                                            toastr.error(response['data']['message'], "", {
                                                timeOut: 2000, "closeButton": true, "positionClass": "toast-top-right"
                                            });
                                        }
                                    }).fail(function (xhr, status, error) {
                                        console.error('AJAX error:', error);
                                        console.error('Response:', xhr.responseText);
                                        $("#verify-spinner-icon").hide();
                                        $("#sb_verification_ph_back").hide();
                                        $('#sb_verify_otp').show();
                                        toastr.error('Server error occurred', "", {
                                            timeOut: 2000, "closeButton": true, "positionClass": "toast-top-right"
                                        });
                                    });
                                }).catch(function (error) {
                                    console.error('Error getting Firebase ID token:', error);
                                    $("#verify-spinner-icon").hide();
                                    $("#sb_verification_ph_back").hide();
                                    $('#sb_verify_otp').show();
                                    toastr.error('Error getting verification token', "", {
                                        timeOut: 2000, "closeButton": true, "positionClass": "toast-top-right"
                                    });
                                });

                            }).catch((error) => {
                                console.error('Firebase verification error:', error);
                                $("#verify-spinner-icon").hide();
                                toastr.error(error['code'], error['message'], {
                                    timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                                });
                            });
                        });
                    } else {
                        loginWithPhoneButton.show();
                        toastr.error(response['data']['message'], "", {
                            timeOut: 2000, "closeButton": true, "positionClass": "toast-top-right"
                        });
                    }
                })
            } else {
                $('#sb_loading').hide();
                $('#sb_login_submit').show();
                $('#sb_login_msg').hide();
                toastr.error(sb_options.invalid_phone, "", {
                    timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                });
            }
        });


        // if (form.parsley().isValid()) {

        // }
    }

    if ($('#sb-verify-phone-firebase').length > 0) {
        let projectID = $('#sb-fb-projectid').val();
        let appID = $('#sb-fb-appid').val();
        let senderID = $('#sb-fb-senderid').val();
        let apiKey = $('#sb-fb-apikey').val();

        let firebaseConfig = {
            apiKey: apiKey, projectId: projectID, messagingSenderId: senderID, appId: appID
        };
        // Initialize Firebase
        firebase.initializeApp(firebaseConfig);
        firebase.analytics();
        firebase.auth().languageCode = document.documentElement.lang;
        let loginphone = $('#sb-verify-phone-firebase');
        if (loginphone.length > 0) {
            let otpNum = $("#user-otp-num").val();
            loginphone.on('click', function () {
                window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier('firebase-recaptcha', {
                    'size': 'normal', 'callback': function (response) {
                        // reCAPTCHA solved, allow signInWithPhoneNumber.
                        // ...
                    }, 'expired-callback': function () {
                        // Response expired. Ask user to solve reCAPTCHA aga
                        // ...
                    }
                });
                let cverify = window.recaptchaVerifier;
                firebase.auth().signInWithPhoneNumber(otpNum, cverify).then(function (response) {
                    // $('#verification_modal').modal('show');
                    const verificationModal = document.getElementById('verification_modal');
                    const firebaseVerification = new bootstrap.Modal(verificationModal);
                    firebaseVerification.show();
                    let notice = $('#verification-notice').val() + " " + otpNum;
                    toastr.success(notice, '', {
                        timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                    });

                    window.confirmationResult = response;
                }).catch(function (error) {

                    toastr.error(error['code'], error['message'], {
                        timeOut: 2000, "closeButton": true, "positionClass": "toast-top-right"
                    });
                })
            });
            jQuery(function ($) {
                $('#sb_ph_number_code').on('keypress', function (e) {
                    if (e.which === 13) {
                        e.preventDefault();
                        $('#sb_verify_otp').trigger('click');
                    }
                });
            });
            /*  verifying otp  */
            jQuery(function ($) {
                $('#sb_verify_otp').on('click', function () {
                    const $btn = $(this);
                    const $backBtn = $('#sb_verification_ph_back');
                    const $input = $('#sb_ph_number_code');
                    const otp = $input.val().trim();
                    const errorClass = 'sb-otp-error';
                    $input.next('.' + errorClass).remove();

                    if (!otp) {
                        $('<span>')
                            .addClass(errorClass)
                            .css({
                                color: 'red',
                                display: 'block',
                                marginTop: '5px',
                                fontSize: '0.9em'
                            })
                            .text(sb_options.enter_otp)
                            .insertAfter($input);
                        return;
                    }

                    $btn.hide();
                    $backBtn.show();
                    confirmationResult.confirm(otp)
                        .then(function (response) {
                            const phoneNum = response.user.phoneNumber;
                            const adforest_ajax = $('#adforest_ajax_url').val();
                            const security = $('#sb_login_otp_nonce').val();

                            return $.post(adforest_ajax, {
                                action: 'sb_verify_firebase_otp',
                                phone_number: phoneNum,
                                security
                            });
                        })
                        .then(function (response) {
                            $backBtn.hide();
                            $btn.show();

                            if (response.success) {
                                toastr.success(response.data.message, '', {
                                    timeOut: 2000, closeButton: true, positionClass: 'toast-top-right'
                                });
                            } else {
                                toastr.error(response.data.message, '', {
                                    timeOut: 3000, closeButton: true, positionClass: 'toast-top-right'
                                });
                            }

                            setTimeout(function () {
                                window.location.reload();
                            }, 500);
                        })
                        .catch(function (error) {
                            $backBtn.hide();
                            $btn.show();

                            toastr.error(error.code, error.message, {
                                timeOut: 3000, closeButton: true, positionClass: 'toast-top-right'
                            });
                        });
                });
            });
        }
    }


    if ($('#sb-pre-code').length > 0 && $('#sb-pre-code').val() == 1) {
        $.getJSON("https://extreme-ip-lookup.com/json/", function (data) {

            if (typeof data.country !== 'undefined') {
                $.getJSON("https://restcountries.eu/rest/v2/name/" + data.country, function (datas) {
                    if (data.country == "India") {
                        $("#sb_reg_email").val("+91");
                    } else if (typeof datas[0].callingCodes[0] !== undefined) {
                        $("#sb_reg_email").val("+" + datas[0].callingCodes[0]);
                    }
                });
            }
        })
    }

    function ValidateEmail(mail) {
        if (/^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9-]+(?:\.[a-zA-Z0-9-]+)*$/.test(mail)) {
            return (true)
        }

        return (false)
    }

    if ($('#adforest-number-sign-up').length > 0) {
        $("#adforest-number-sign-up")
            .parsley()
            .on("field:validated", function () {
            })
            .on("form:error", function () {
                $(".ad_errors").show();
                $(".parsley-errors-list").show();
            })

        $('#adforest-number-sign-up').on('submit', function (e) {
            e.preventDefault();
            let form = $(this);
            loading.show();
            let numberVal = $("#adforest_reg_number").val();
            if (numberVal.includes("+")) {
                $.post(adforest_ajax_url, {
                    action: 'sb_register_check_user', form_data: form.serialize(), security: $('#sb_register_check_user_nonce').val()
                }).done(function (response) {
                    loading.hide();
                    if (response['success'] === true) {
                        let projectId = $('#sb-fb-projectid').val();
                        let appId = $('#sb-fb-appid').val();
                        let messagingSenderId = $('#sb-fb-senderid').val();
                        let apiKey = $('#sb-fb-apikey').val();
                        let firebaseConfig = {
                            apiKey, projectId, messagingSenderId, appId
                        };

                        // Initialize Firebase
                        !firebase.apps.length ? firebase.initializeApp(firebaseConfig) : firebase.app()
                        firebase.analytics();
                        firebase.auth().languageCode = document.documentElement.lang;

                        window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier('firebase-recaptcha', {
                            'size': 'normal', 'callback': function (response) {
                                // reCAPTCHA solved, allow signInWithPhoneNumber.
                                // ...
                            }, 'expired-callback': function () {
                                // Response expired. Ask user to solve reCAPTCHA aga
                                // ...
                            }
                        });
                        toastr.success($('#verify_recaptcha').val(), '', {
                            timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                        });

                        let recaptchaVerifier = window.recaptchaVerifier;
                        firebase.auth().signInWithPhoneNumber(numberVal, recaptchaVerifier).then(function (response) {
                            let myModal = new bootstrap.Modal(document.getElementById('verification_modal'));
                            myModal.show();
                            let notice = sb_options.verification_notice + " " + numberVal;
                            toastr.success(notice, '', {
                                timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                            });
                            window.confirmationResult = response;
                        }).catch(function (error) {
                            toastr.error(error['code'], error['message'], {
                                timeOut: 2000, "closeButton": true, "positionClass": "toast-top-right"
                            });
                        })

                        /* Resend code to user number  */
                        $('#sb_verification_resend').on('click', function () {
                            $('#firebase-recaptcha2').show();
                            window.recaptchaVerifier = new firebase.auth.RecaptchaVerifier('firebase-recaptcha2', {
                                'size': 'normal', 'callback': function (response) {
                                    // reCAPTCHA solved, allow signInWithPhoneNumber.
                                    // ...
                                }, 'expired-callback': function () {
                                    // Response expired. Ask user to solve reCAPTCHA aga
                                    // ...
                                }
                            });
                            toastr.success($('#verify_recaptcha').val(), '', {
                                timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                            });
                            let recaptchaVerifier = window.recaptchaVerifier;
                            firebase.auth().signInWithPhoneNumber(numberVal, recaptchaVerifier).then(function (response) {
                                let notice = sb_options.verification_notice + " " + numberVal;

                                $('#firebase-recaptcha2').hide();

                                toastr.success(notice, '', {
                                    timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                                });
                                window.confirmationResult = response;
                            }).catch(function (error) {
                                toastr.error(error['code'], error['message'], {
                                    timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                                });
                            })

                        });

                        /*  verifying otp  */
                        $('#sb_verify_otp').on('click', function () {
                            let otp = $('#sb_ph_number_code').val();
                            $(this).prop('disabled', true);
                            $("#verify-spinner-icon").show();
                            confirmationResult.confirm(otp).then(function (response) {
                                let userobj = response.user;
                                let phoneNum = userobj.phoneNumber;
                                let form_data = $("#adforest-number-sign-up").serialize();
                                $.post(adforest_ajax_url, {
                                    action: 'sb_register_user_with_otp',
                                    phone_number: phoneNum,
                                    form_data: form_data,
                                    security: $('#sb_register_user_with_otp_nonce').val(),
                                }).done(function (response) {
                                    $("#verify-spinner-icon").hide();
                                    if (response['success'] == true) {

                                        $("#sb_verification_ph_back").hide();
                                        $('#sb_verify_otp').show();
                                        toastr.success(response['data']['message'], '', {
                                            timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                                        });

                                        window.location = sb_options.sb_after_login_page;
                                    } else {
                                        $("#sb_verification_ph_back").hide();
                                        $('#sb_verify_otp').show();
                                        toastr.error(response['data']['message'], "", {
                                            timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                                        });
                                    }

                                });
                            }).catch((error) => {
                                $("#verify-spinner-icon").hide();
                                toastr.error(error['code'], error['message'], {
                                    timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                                });
                            })
                        });
                    } else {
                        toastr.error(response['data']['message'], "", {
                            timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                        });
                    }
                })
            } else {
                loading.hide();
                toastr.error($('#invalid_phone').val(), "", {
                    timeOut: 3000, "closeButton": true, "positionClass": "toast-top-right"
                });
            }
        })
    }
}(jQuery));