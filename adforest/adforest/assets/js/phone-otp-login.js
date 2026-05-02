/**
 * AdForest Phone OTP Login Handler
 *
 * Secure OTP-based phone authentication
 * Requires: jQuery
 *
 * @package AdForest
 * @since 5.0.0
 */

(function($) {
    'use strict';

    /**
     * Phone OTP Login Module
     */
    var PhoneOTPLogin = {
        /**
         * Store session token from OTP request
         */
        sessionToken: null,

        /**
         * Store phone number for verification
         */
        phoneNumber: null,

        /**
         * Initialize the module
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind event handlers
         */
        bindEvents: function() {
            // Step 1: Request OTP
            $(document).on('submit', '#phone-login-form', this.handleRequestOTP.bind(this));

            // Step 2: Verify OTP
            $(document).on('submit', '#otp-verify-form', this.handleVerifyOTP.bind(this));

            // Resend OTP
            $(document).on('click', '.resend-otp-btn', this.handleResendOTP.bind(this));
        },

        /**
         * Step 1: Request OTP
         * Calls sb_login_check_user AJAX action
         *
         * @param {Event} e Form submit event
         */
        handleRequestOTP: function(e) {
            e.preventDefault();

            var self = this;
            var $form = $(e.target);
            var $submitBtn = $form.find('button[type="submit"]');
            var phoneNumber = $form.find('input[name="sb_reg_phone"]').val();

            if (!phoneNumber || phoneNumber.trim() === '') {
                this.showError('Please enter a valid phone number.');
                return;
            }

            // Store phone number for later
            this.phoneNumber = phoneNumber.trim();

            // Disable button during request
            $submitBtn.prop('disabled', true).text('Sending OTP...');

            $.ajax({
                url: adforest_phone_otp.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sb_login_check_user',
                    nonce: adforest_phone_otp.sb_phone_login_nonce,
                    form_data: $form.serialize()
                },
                success: function(response) {
                    $submitBtn.prop('disabled', false).text('Send OTP');

                    if (response.success) {
                        // Store session token (REQUIRED for verification)
                        self.sessionToken = response.data.session_token;

                        // Show OTP input form
                        self.showOTPForm();

                        self.showSuccess(response.data.message);
                    } else {
                        self.showError(response.data.message || 'Failed to send OTP.');
                    }
                },
                error: function(xhr, status, error) {
                    $submitBtn.prop('disabled', false).text('Send OTP');
                    self.showError('Network error. Please try again.');
                    console.error('OTP Request Error:', error);
                }
            });
        },

        /**
         * Step 2: Verify OTP and Login
         * Calls sb_login_user_with_otp AJAX action
         *
         * @param {Event} e Form submit event
         */
        handleVerifyOTP: function(e) {
            e.preventDefault();

            var self = this;
            var $form = $(e.target);
            var $submitBtn = $form.find('button[type="submit"]');
            var otpCode = $form.find('input[name="otp_code"]').val();

            // Validate OTP format
            if (!otpCode || !/^\d{6}$/.test(otpCode)) {
                this.showError('Please enter a valid 6-digit OTP code.');
                return;
            }

            // Validate session token exists
            if (!this.sessionToken) {
                this.showError('Session expired. Please request a new OTP.');
                return;
            }

            // Disable button during request
            $submitBtn.prop('disabled', true).text('Verifying...');

            $.ajax({
                url: adforest_phone_otp.ajaxurl,
                type: 'POST',
                data: {
                    action: 'sb_login_user_with_otp',
                    nonce: adforest_phone_otp.sb_otp_nonce_phone_login,
                    phone_number: this.phoneNumber,
                    otp_code: otpCode,
                    session_token: this.sessionToken
                },
                success: function(response) {
                    $submitBtn.prop('disabled', false).text('Verify & Login');

                    if (response.success) {
                        self.showSuccess(response.data.message);

                        // Redirect to dashboard or reload page
                        setTimeout(function() {
                            window.location.reload();
                        }, 1000);
                    } else {
                        self.showError(response.data.message || 'Verification failed.');

                        // Clear OTP input on failure
                        $form.find('input[name="otp_code"]').val('').focus();
                    }
                },
                error: function(xhr, status, error) {
                    $submitBtn.prop('disabled', false).text('Verify & Login');
                    self.showError('Network error. Please try again.');
                    console.error('OTP Verify Error:', error);
                }
            });
        },

        /**
         * Handle Resend OTP
         *
         * @param {Event} e Click event
         */
        handleResendOTP: function(e) {
            e.preventDefault();

            // Reset session token
            this.sessionToken = null;

            // Trigger new OTP request
            $('#phone-login-form').trigger('submit');
        },

        /**
         * Show OTP input form
         * Hide phone input, show OTP verification
         */
        showOTPForm: function() {
            $('#phone-login-step').hide();
            $('#otp-verify-step').show();

            // Focus on OTP input
            $('input[name="otp_code"]').focus();

            // Start countdown timer (optional)
            this.startResendTimer();
        },

        /**
         * Start resend countdown timer
         */
        startResendTimer: function() {
            var $timer = $('.resend-timer');
            var $resendBtn = $('.resend-otp-btn');
            var seconds = 60;

            $resendBtn.prop('disabled', true);

            var interval = setInterval(function() {
                seconds--;
                $timer.text('(' + seconds + 's)');

                if (seconds <= 0) {
                    clearInterval(interval);
                    $timer.text('');
                    $resendBtn.prop('disabled', false);
                }
            }, 1000);
        },

        /**
         * Show success message
         *
         * @param {string} message Success message
         */
        showSuccess: function(message) {
            // Customize based on your theme's notification system
            if (typeof toastr !== 'undefined') {
                toastr.success(message);
            } else {
                alert(message);
            }
        },

        /**
         * Show error message
         *
         * @param {string} message Error message
         */
        showError: function(message) {
            // Customize based on your theme's notification system
            if (typeof toastr !== 'undefined') {
                toastr.error(message);
            } else {
                alert(message);
            }
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        PhoneOTPLogin.init();
    });

})(jQuery);
