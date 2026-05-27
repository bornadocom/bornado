(function ($) {
    const config = window.bornadoAuthModal || {};
    const modalElement = document.getElementById('bornado-auth-modal');

    if (!modalElement || !config.ajaxUrl) {
        return;
    }

    const bootstrapModal = window.bootstrap && window.bootstrap.Modal
        ? new window.bootstrap.Modal(modalElement)
        : null;

    const state = {
        mode: 'login',
        method: config.phoneEnabled ? 'phone' : 'email',
        otpFlow: null,
        redirectUrl: config.afterLoginUrl || window.location.href,
        resendCountdown: 0,
        resendInterval: null,
        sendingCode: false,
        verifyingCode: false,
        previousView: 'login-email',
        captchaWidgets: {
            login: null,
            register: null
        },
        firebase: {
            appReady: false,
            auth: null,
            verifier: null
        }
    };

    const elements = {
        notice: document.getElementById('bornado-auth-notice'),
        modalTitle: document.getElementById('bornado-auth-modal-title'),
        modeButtons: Array.from(modalElement.querySelectorAll('[data-auth-mode]')),
        methodButtons: Array.from(modalElement.querySelectorAll('[data-auth-method]')),
        views: Array.from(modalElement.querySelectorAll('.bornado-auth-view')),
        otpTarget: document.getElementById('bornado-auth-otp-target'),
        otpCode: document.getElementById('bornado-auth-otp-code'),
        changeNumber: document.getElementById('bornado-auth-change-number'),
        resendButton: document.getElementById('bornado-auth-resend-code'),
        resendTimer: document.getElementById('bornado-auth-resend-timer'),
        openForgot: document.getElementById('bornado-auth-open-forgot'),
        backFromForgot: document.getElementById('bornado-auth-back-from-forgot'),
        registerPhoneWrap: document.getElementById('bornado-auth-register-phone-wrap')
    };

    const forms = {
        phoneLogin: document.getElementById('bornado-auth-phone-login-form'),
        phoneRegister: document.getElementById('bornado-auth-phone-register-form'),
        otp: document.getElementById('bornado-auth-otp-form'),
        emailLogin: document.getElementById('bornado-auth-email-login-form'),
        emailRegister: document.getElementById('bornado-auth-email-register-form'),
        forgot: document.getElementById('bornado-auth-forgot-form')
    };
    const phoneCountries = Array.isArray(config.phoneCountries) ? config.phoneCountries : [];
    const defaultPhoneCountry = config.defaultPhoneCountry && typeof config.defaultPhoneCountry === 'object'
        ? config.defaultPhoneCountry
        : null;

    init();

    function init() {
        if (!config.phoneEnabled) {
            elements.methodButtons.forEach(function (button) {
                if (button.dataset.authMethod === 'phone') {
                    button.hidden = true;
                }
            });
            state.method = 'email';
        }

        if (!config.showRegisterPhone && elements.registerPhoneWrap) {
            elements.registerPhoneWrap.hidden = true;
            const input = elements.registerPhoneWrap.querySelector('input');
            if (input) {
                input.disabled = true;
            }
        }

        updateCaptchaFlags();
        bindModeButtons();
        bindMethodButtons();
        bindTriggers();
        bindForms();
        bindModalLifecycle();
        enhancePhoneFields();
        syncView();
    }

    function bindModeButtons() {
        elements.modeButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                setMode(button.dataset.authMode || 'login');
            });
        });
    }

    function bindMethodButtons() {
        elements.methodButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (button.hidden) {
                    return;
                }
                setMethod(button.dataset.authMethod || 'phone');
            });
        });
    }

    function bindTriggers() {
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-bornado-auth-open]');
            if (trigger) {
                event.preventDefault();
                openModal({
                    mode: trigger.dataset.mode || 'login',
                    method: trigger.dataset.method || (config.phoneEnabled ? 'phone' : 'email'),
                    redirectUrl: resolveRedirectUrl(trigger.getAttribute('href'))
                });
                return;
            }

            const link = event.target.closest('a[href]');
            if (!link || !shouldInterceptAuthLink(link)) {
                return;
            }

            event.preventDefault();

            const parsed = safeUrl(link.href);
            const defaultMode = matchUrlPath(link.href, config.signUpUrl) ? 'register' : 'login';
            const queryMethod = parsed.searchParams.get('log_type') || parsed.searchParams.get('reg_type') || '';

            openModal({
                mode: defaultMode,
                method: queryMethod === 'email' ? 'email' : (config.phoneEnabled ? 'phone' : 'email'),
                redirectUrl: parsed.searchParams.get('u') || window.location.href
            });
        });
    }

    function bindForms() {
        if (forms.phoneLogin) {
            forms.phoneLogin.addEventListener('submit', function (event) {
                event.preventDefault();
                startPhoneFlow('login');
            });
        }

        if (forms.phoneRegister) {
            forms.phoneRegister.addEventListener('submit', function (event) {
                event.preventDefault();
                startPhoneFlow('register');
            });
        }

        if (forms.otp) {
            forms.otp.addEventListener('submit', function (event) {
                event.preventDefault();
                verifyPhoneCode();
            });
        }

        if (forms.emailLogin) {
            forms.emailLogin.addEventListener('submit', function (event) {
                event.preventDefault();
                submitEmailLogin();
            });
        }

        if (forms.emailRegister) {
            forms.emailRegister.addEventListener('submit', function (event) {
                event.preventDefault();
                submitEmailRegister();
            });
        }

        if (forms.forgot) {
            forms.forgot.addEventListener('submit', function (event) {
                event.preventDefault();
                submitForgotPassword();
            });
        }

        if (elements.changeNumber) {
            elements.changeNumber.addEventListener('click', async function () {
                if (state.sendingCode || state.verifyingCode) {
                    return;
                }
                state.otpFlow = null;
                stopResendCountdown();
                if (elements.otpCode) {
                    elements.otpCode.value = '';
                }
                await signOutFirebase();
                await resetFirebaseVerifier();
                syncView();
                showNotice('info', '');
            });
        }

        if (elements.resendButton) {
            elements.resendButton.addEventListener('click', function () {
                if (!state.otpFlow || state.resendCountdown > 0 || state.sendingCode || state.verifyingCode) {
                    return;
                }
                resendPhoneCode();
            });
        }

        if (elements.openForgot) {
            elements.openForgot.addEventListener('click', function () {
                state.previousView = 'login-email';
                switchView('forgot');
            });
        }

        if (elements.backFromForgot) {
            elements.backFromForgot.addEventListener('click', function () {
                switchView(state.previousView || 'login-email');
            });
        }
    }

    function sanitizeDialCode(value) {
        let cleaned = String(value || '').trim().replace(/[^\d+]/g, '');

        if (!cleaned) {
            return '';
        }

        if (cleaned.indexOf('00') === 0) {
            cleaned = '+' + cleaned.slice(2);
        } else if (cleaned.charAt(0) !== '+') {
            cleaned = '+' + cleaned.replace(/^\++/, '');
        }

        cleaned = '+' + cleaned.replace(/[^\d]/g, '');

        return /^\+\d{1,4}$/.test(cleaned) ? cleaned : '';
    }

    function normalizePhone(value, dialCode) {
        const raw = String(value || '').trim();
        const normalizedDialCode = sanitizeDialCode(dialCode);
        let cleaned;
        let digitsOnly;
        let dialDigits;

        if (!raw) {
            return '';
        }

        cleaned = raw.replace(/[^\d+]/g, '');
        if (!cleaned) {
            return '';
        }

        if (cleaned.indexOf('00') === 0) {
            cleaned = '+' + cleaned.slice(2);
        }

        if (cleaned.charAt(0) === '+') {
            cleaned = '+' + cleaned.replace(/[^\d]/g, '');
            return cleaned;
        }

        if (!normalizedDialCode) {
            return '';
        }

        digitsOnly = cleaned.replace(/[^\d]/g, '');
        dialDigits = normalizedDialCode.replace(/[^\d]/g, '');

        if (!digitsOnly || !dialDigits) {
            return '';
        }

        if (digitsOnly.indexOf(dialDigits) === 0) {
            return '+' + digitsOnly;
        }

        return '+' + dialDigits + digitsOnly.replace(/^0+/, '');
    }

    function inferCountryFromPhone(value) {
        const normalized = normalizePhone(value, '');
        let match = null;

        if (!normalized) {
            return null;
        }

        phoneCountries.forEach(function (country) {
            const dialCode = sanitizeDialCode(country && country.dialCode ? country.dialCode : '');

            if (!dialCode) {
                return;
            }

            if (normalized.indexOf(dialCode) === 0) {
                if (!match || String(dialCode).length > String(match.dialCode || '').length) {
                    match = country;
                }
            }
        });

        return match;
    }

    function getPhoneDialCode(form, inputName) {
        if (!form) {
            return defaultPhoneCountry && defaultPhoneCountry.dialCode ? defaultPhoneCountry.dialCode : '';
        }

        const selectName = inputName === 'sb_reg_contact' ? 'sb_reg_contact_dial_code' : 'phone_dial_code';
        const select = form.querySelector('select[name="' + selectName + '"]');

        return select && select.value
            ? select.value
            : (defaultPhoneCountry && defaultPhoneCountry.dialCode ? defaultPhoneCountry.dialCode : '');
    }

    function applyNormalizedPhoneInput(form, inputName) {
        const input = form ? form.querySelector('input[name="' + inputName + '"]') : null;
        const dialCode = getPhoneDialCode(form, inputName);
        const normalized = input ? normalizePhone(input.value, dialCode) : '';

        if (input && normalized) {
            input.value = normalized;
        }

        return normalized;
    }

    function enhancePhoneFields() {
        if (!phoneCountries.length) {
            return;
        }

        function localPhonePlaceholder() {
            return '9121234567';
        }

        [
            { form: forms.phoneLogin, inputName: 'phone_number', selectName: 'phone_dial_code' },
            { form: forms.phoneRegister, inputName: 'phone_number', selectName: 'phone_dial_code' },
            { form: forms.emailRegister, inputName: 'sb_reg_contact', selectName: 'sb_reg_contact_dial_code' }
        ].forEach(function (fieldConfig) {
            const form = fieldConfig.form;
            const input = form ? form.querySelector('input[name="' + fieldConfig.inputName + '"]') : null;
            const field = input ? input.closest('.bornado-auth-field') : null;
            const fieldLabel = input ? field.querySelector('label[for="' + input.id + '"]') : null;
            let row;
            let select;

            if (!form || !input || !field) {
                return;
            }

            select = field.querySelector('select[name="' + fieldConfig.selectName + '"]');
            if (!select) {
                select = document.createElement('select');
                select.name = fieldConfig.selectName;
                select.className = 'bornado-auth-country-select';
                select.setAttribute('aria-label', getI18n('countryLabel'));

                phoneCountries.forEach(function (country) {
                    const option = document.createElement('option');
                    option.value = String(country.dialCode || '');
                    option.textContent = String(country.name || '') + ' (' + String(country.dialCode || '') + ')';
                    select.appendChild(option);
                });
            }

            row = field.querySelector('.bornado-auth-phone-row');
            if (!row) {
                row = document.createElement('div');
                row.className = 'bornado-auth-phone-row';
                if (fieldLabel && fieldLabel.nextSibling) {
                    field.insertBefore(row, fieldLabel.nextSibling);
                } else {
                    field.appendChild(row);
                }
            }

            if (select.parentNode !== row) {
                row.appendChild(select);
            }

            if (input.parentNode !== row) {
                row.appendChild(input);
            }

            const selectedCountry = inferCountryFromPhone(input.value) || defaultPhoneCountry;
            if (selectedCountry && selectedCountry.dialCode) {
                select.value = String(selectedCountry.dialCode);
            }

            function syncPlaceholder() {
                input.setAttribute('placeholder', localPhonePlaceholder());
            }

            select.addEventListener('change', syncPlaceholder);
            input.addEventListener('blur', function () {
                applyNormalizedPhoneInput(form, fieldConfig.inputName);
            });
            form.addEventListener('submit', function () {
                applyNormalizedPhoneInput(form, fieldConfig.inputName);
            }, true);

            syncPlaceholder();
        });
    }

    function bindModalLifecycle() {
        modalElement.addEventListener('hidden.bs.modal', function () {
            resetState();
        });
    }

    function openModal(options) {
        setMode(options.mode || 'login');
        setMethod(options.method || (config.phoneEnabled ? 'phone' : 'email'));
        state.redirectUrl = options.redirectUrl || window.location.href;
        showNotice('info', '');

        if (bootstrapModal) {
            bootstrapModal.show();
        } else {
            modalElement.classList.add('show');
            modalElement.style.display = 'block';
        }
    }

    function setMode(mode) {
        state.mode = mode === 'register' ? 'register' : 'login';
        elements.modeButtons.forEach(function (button) {
            button.classList.toggle('is-active', button.dataset.authMode === state.mode);
        });
        updateModalTitle();
        syncView();
    }

    function setMethod(method) {
        state.method = method === 'email' ? 'email' : 'phone';
        if (!config.phoneEnabled) {
            state.method = 'email';
        }
        elements.methodButtons.forEach(function (button) {
            button.classList.toggle('is-active', button.dataset.authMethod === state.method);
        });
        syncView();
    }

    function syncView() {
        let nextView = state.mode + '-' + state.method;

        if (state.otpFlow) {
            nextView = 'otp';
        }

        switchView(nextView);
    }

    function switchView(viewName) {
        elements.views.forEach(function (view) {
            const isActive = view.dataset.view === viewName;
            view.classList.toggle('is-active', isActive);
        });

        if (viewName === 'login-email') {
            renderCaptchaWidget('login');
        } else if (viewName === 'register-email') {
            renderCaptchaWidget('register');
        }
    }

    function updateCaptchaFlags() {
        const enabled = Boolean(config.captchaSiteKey);

        [forms.emailLogin, forms.emailRegister].forEach(function (form) {
            if (!form) {
                return;
            }
            const field = form.querySelector('input[name="is_captcha"]');
            if (field) {
                field.value = enabled ? 'yes' : 'no';
            }
        });
    }

    function renderCaptchaWidget(type) {
        if (config.captchaType !== 'v2' || !config.captchaSiteKey || !window.grecaptcha) {
            return;
        }

        const containerId = type === 'login' ? 'bornado-auth-login-recaptcha' : 'bornado-auth-register-recaptcha';
        const container = document.getElementById(containerId);

        if (!container) {
            return;
        }

        if (state.captchaWidgets[type] === null) {
            try {
                state.captchaWidgets[type] = window.grecaptcha.render(containerId, {
                    sitekey: config.captchaSiteKey
                });
            } catch (error) {
                // Widget is already rendered or grecaptcha is still booting.
            }
        }
    }

    async function startPhoneFlow(mode) {
        if (!config.phoneEnabled) {
            setMethod('email');
            return;
        }

        if (state.sendingCode || state.verifyingCode) {
            return;
        }

        const form = mode === 'register' ? forms.phoneRegister : forms.phoneLogin;
        const phoneInput = form.querySelector('input[name="phone_number"]');
        const phoneDialCode = getPhoneDialCode(form, 'phone_number');
        const phoneNumber = normalizePhone(phoneInput.value, phoneDialCode);
        const rememberField = form.querySelector('input[name="remember"]');
        const displayNameField = form.querySelector('input[name="display_name"]');
        const remember = mode === 'login' && rememberField && rememberField.checked ? '1' : '0';
        const displayName = mode === 'register'
            ? ((displayNameField && displayNameField.value) || '').trim()
            : '';

        if (!isValidPhone(phoneNumber)) {
            showNotice('error', getI18n('invalidPhone'));
            phoneInput.focus();
            return;
        }

        if (mode === 'register') {
            if (!displayName) {
                showNotice('error', getI18n('nameRequired'));
                form.querySelector('input[name="display_name"]').focus();
                return;
            }

            const terms = document.getElementById('bornado-auth-register-terms-phone');
            if (terms && !terms.checked) {
                showNotice('error', getI18n('termsRequired'));
                terms.focus();
                return;
            }
        }

        toggleSubmitState(form, true);
        setPhoneRequestState(true);
        showNotice('info', getI18n('loading'));

        try {
            state.otpFlow = null;
            stopResendCountdown();
            await postAjax({
                action: 'bornado_auth_phone_preflight',
                security: config.phonePreflightNonce,
                mode: mode,
                phone_number: phoneNumber,
                phone_dial_code: phoneDialCode
            });

            await sendPhoneCode({
                mode: mode,
                phoneNumber: phoneNumber,
                phoneDialCode: phoneDialCode,
                remember: remember,
                displayName: displayName
            });
        } catch (error) {
            showNotice('error', extractMessage(error));
        } finally {
            setPhoneRequestState(false);
            toggleSubmitState(form, false);
        }
    }

    async function resendPhoneCode() {
        if (!state.otpFlow || state.sendingCode || state.verifyingCode) {
            return;
        }

        setPhoneRequestState(true);
        setOtpActionState(false);
        showNotice('info', getI18n('loading'));

        try {
            await postAjax({
                action: 'bornado_auth_phone_preflight',
                security: config.phonePreflightNonce,
                mode: state.otpFlow.mode,
                phone_number: state.otpFlow.phoneNumber,
                phone_dial_code: state.otpFlow.phoneDialCode || ''
            });

            await sendPhoneCode(state.otpFlow, true);
        } catch (error) {
            showNotice('error', extractMessage(error));
        } finally {
            setPhoneRequestState(false);
            setOtpActionState(false);
        }
    }

    async function sendPhoneCode(flow, isResend) {
        await signOutFirebase();
        await ensureFirebase();
        await resetFirebaseVerifier();
        const recaptchaContainer = rebuildFirebaseRecaptchaContainer();

        state.firebase.verifier = new window.firebase.auth.RecaptchaVerifier(recaptchaContainer, {
            size: 'invisible',
            callback: function () {},
            'expired-callback': function () {
                showNotice('error', getI18n('verifyRecaptcha'));
            }
        });

        await state.firebase.verifier.render();

        const confirmationResult = await state.firebase.auth.signInWithPhoneNumber(flow.phoneNumber, state.firebase.verifier);
        state.otpFlow = {
            mode: flow.mode,
            phoneNumber: flow.phoneNumber,
            phoneDialCode: flow.phoneDialCode || '',
            remember: flow.remember || '0',
            displayName: flow.displayName || '',
            confirmationResult: confirmationResult
        };

        elements.otpTarget.textContent = (config.i18n && config.i18n.verificationSent ? config.i18n.verificationSent : '') + ' ' + flow.phoneNumber;
        elements.otpCode.value = '';
        switchView('otp');
        startResendCountdown();
        showNotice('success', isResend ? getI18n('verificationSent') : getI18n('verificationSent'));
    }

    async function verifyPhoneCode() {
        if (!state.otpFlow || !state.otpFlow.confirmationResult || state.verifyingCode || state.sendingCode) {
            showNotice('error', getI18n('genericError'));
            return;
        }

        const code = (elements.otpCode.value || '').trim();
        if (!/^\d{6}$/.test(code)) {
            showNotice('error', code ? getI18n('otpLength') : getI18n('otpRequired'));
            elements.otpCode.focus();
            return;
        }

        toggleSubmitState(forms.otp, true);
        setOtpActionState(true);
        showNotice('info', getI18n('loading'));

        try {
            const firebaseResponse = await state.otpFlow.confirmationResult.confirm(code);
            const idToken = await firebaseResponse.user.getIdToken();
            const action = state.otpFlow.mode === 'register'
                ? 'bornado_auth_firebase_register'
                : 'bornado_auth_firebase_login';
            const nonce = state.otpFlow.mode === 'register'
                ? config.firebaseRegisterNonce
                : config.firebaseLoginNonce;

            const response = await postAjax({
                action: action,
                security: nonce,
                phone_number: state.otpFlow.phoneNumber,
                phone_dial_code: state.otpFlow.phoneDialCode || '',
                id_token: idToken,
                remember: state.otpFlow.remember || '0',
                display_name: state.otpFlow.displayName || ''
            });

            const isRegisterFlow = state.otpFlow.mode === 'register';
            showNotice('success', response.data && response.data.message ? response.data.message : getI18n(isRegisterFlow ? 'phoneRegisterSuccess' : 'phoneLoginSuccess'));
            await signOutFirebase();
            redirectAfterSuccess(isRegisterFlow ? 'register' : 'login');
        } catch (error) {
            showNotice('error', extractMessage(error));
            if (elements.otpCode) {
                elements.otpCode.focus();
                elements.otpCode.select();
            }
        } finally {
            setOtpActionState(false);
            toggleSubmitState(forms.otp, false);
        }
    }

    async function submitEmailLogin() {
        const email = (forms.emailLogin.querySelector('input[name="sb_reg_email"]').value || '').trim();
        const password = forms.emailLogin.querySelector('input[name="sb_reg_password"]').value || '';

        if (!isValidEmail(email)) {
            showNotice('error', getI18n('invalidEmail'));
            return;
        }

        if (!password) {
            showNotice('error', getI18n('passwordRequired'));
            return;
        }

        toggleSubmitState(forms.emailLogin, true);
        showNotice('info', getI18n('loading'));

        try {
            await ensureEmailCaptcha(forms.emailLogin, 'contact_form', 'login');
            const response = await postAjax({
                action: 'sb_login_user',
                security: config.loginNonce,
                sb_data: $(forms.emailLogin).serialize()
            }, false);

            if (String(response).trim() !== '1') {
                throw response;
            }

            showNotice('success', getI18n('phoneLoginSuccess'));
            redirectAfterSuccess('login');
        } catch (error) {
            showNotice('error', extractMessage(error));
        } finally {
            toggleSubmitState(forms.emailLogin, false);
        }
    }

    async function submitEmailRegister() {
        const name = (forms.emailRegister.querySelector('input[name="sb_reg_name"]').value || '').trim();
        const email = (forms.emailRegister.querySelector('input[name="sb_reg_email"]').value || '').trim();
        const password = forms.emailRegister.querySelector('input[name="sb_reg_password"]').value || '';
        const passwordConfirm = forms.emailRegister.querySelector('input[name="sb_reg_password_confirm"]').value || '';
        const terms = document.getElementById('bornado-auth-register-terms-email');

        if (!name) {
            showNotice('error', getI18n('nameRequired'));
            return;
        }

        if (!isValidEmail(email)) {
            showNotice('error', getI18n('invalidEmail'));
            return;
        }

        if (!password) {
            showNotice('error', getI18n('passwordRequired'));
            return;
        }

        if (password !== passwordConfirm) {
            showNotice('error', getI18n('passwordMismatch'));
            return;
        }

        if (terms && !terms.checked) {
            showNotice('error', getI18n('termsRequired'));
            return;
        }

        toggleSubmitState(forms.emailRegister, true);
        showNotice('info', getI18n('loading'));

        try {
            applyNormalizedPhoneInput(forms.emailRegister, 'sb_reg_contact');
            await ensureEmailCaptcha(forms.emailRegister, 'register_form', 'register');
            const response = await postAjax({
                action: 'sb_register_user',
                security: config.registerNonce,
                sb_data: $(forms.emailRegister).serialize()
            }, false);

            const normalized = String(response).trim();

            if (normalized === '1') {
                showNotice('success', getI18n('phoneRegisterSuccess'));
                redirectAfterSuccess('register');
                return;
            }

            if (normalized === '2') {
                showNotice('success', (window.get_strings && window.get_strings.verify_account_msg) || 'ایمیل تایید حساب برای شما ارسال شد.');
                return;
            }

            if (normalized === '3') {
                showNotice('success', 'حساب شما پس از تایید مدیر فعال خواهد شد.');
                return;
            }

            throw response;
        } catch (error) {
            showNotice('error', extractMessage(error));
        } finally {
            toggleSubmitState(forms.emailRegister, false);
        }
    }

    async function submitForgotPassword() {
        const email = (forms.forgot.querySelector('input[name="sb_forgot_email"]').value || '').trim();

        if (!isValidEmail(email)) {
            showNotice('error', getI18n('invalidEmail'));
            return;
        }

        toggleSubmitState(forms.forgot, true);
        showNotice('info', getI18n('loading'));

        try {
            const response = await postAjax({
                action: 'sb_forgot_password',
                security: config.forgotNonce,
                sb_data: $(forms.forgot).serialize()
            }, false);

            if (String(response).trim() !== '1') {
                throw response;
            }

            showNotice('success', getI18n('forgotSuccess'));
            forms.forgot.reset();
            switchView('login-email');
        } catch (error) {
            showNotice('error', extractMessage(error));
        } finally {
            toggleSubmitState(forms.forgot, false);
        }
    }

    async function ensureEmailCaptcha(form, actionName, widgetType) {
        clearCaptchaToken(form);

        if (!config.captchaSiteKey) {
            updateCaptchaFlag(form, false);
            return;
        }

        updateCaptchaFlag(form, true);

        if (config.captchaType === 'v3') {
            if (!window.grecaptcha || !window.grecaptcha.execute) {
                throw new Error(getI18n('verifyRecaptcha'));
            }

            const token = await new Promise(function (resolve, reject) {
                window.grecaptcha.ready(function () {
                    window.grecaptcha.execute(config.captchaSiteKey, {action: actionName}).then(resolve).catch(reject);
                });
            });

            await postAjax({
                action: 'sb_google_captcha3_verification',
                security: config.googleCaptchaNonce,
                token: token
            });

            setCaptchaToken(form, token);
            return;
        }

        renderCaptchaWidget(widgetType);

        const widgetId = state.captchaWidgets[widgetType];
        if (widgetId === null || !window.grecaptcha) {
            throw new Error(getI18n('verifyRecaptcha'));
        }

        const response = window.grecaptcha.getResponse(widgetId);
        if (!response) {
            throw new Error(getI18n('verifyRecaptcha'));
        }
    }

    function updateCaptchaFlag(form, enabled) {
        const field = form.querySelector('input[name="is_captcha"]');
        if (field) {
            field.value = enabled ? 'yes' : 'no';
        }
    }

    function setCaptchaToken(form, token) {
        clearCaptchaToken(form);
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'g-recaptcha-response';
        hidden.value = token;
        hidden.dataset.bornadoCaptcha = '1';
        form.prepend(hidden);
    }

    function clearCaptchaToken(form) {
        const existing = form.querySelector('input[data-bornado-captcha="1"]');
        if (existing) {
            existing.remove();
        }
    }

    function toggleSubmitState(form, isLoading) {
        const button = form ? form.querySelector('.bornado-auth-submit') : null;
        if (!button) {
            return;
        }

        if (!button.dataset.defaultText) {
            button.dataset.defaultText = button.textContent;
        }

        button.disabled = isLoading;
        button.textContent = isLoading ? getI18n('loading') : button.dataset.defaultText;
    }

    function startResendCountdown() {
        clearInterval(state.resendInterval);
        state.resendCountdown = 30;
        updateResendUi();

        state.resendInterval = window.setInterval(function () {
            state.resendCountdown -= 1;
            updateResendUi();

            if (state.resendCountdown <= 0) {
                clearInterval(state.resendInterval);
                state.resendInterval = null;
            }
        }, 1000);
    }

    function updateResendUi() {
        if (!elements.resendButton || !elements.resendTimer) {
            return;
        }

        const disabled = state.resendCountdown > 0 || state.sendingCode || state.verifyingCode;
        elements.resendButton.disabled = disabled;
        elements.resendTimer.hidden = !disabled;

        if (state.sendingCode) {
            elements.resendTimer.hidden = false;
            elements.resendTimer.textContent = getI18n('loading');
        } else if (disabled && state.resendCountdown > 0) {
            elements.resendTimer.textContent = getI18n('resendIn') + ' ' + state.resendCountdown + ' ' + getI18n('seconds');
        } else {
            elements.resendTimer.textContent = '';
        }
    }

    function stopResendCountdown() {
        clearInterval(state.resendInterval);
        state.resendInterval = null;
        state.resendCountdown = 0;
        updateResendUi();
    }

    function setPhoneRequestState(isLoading) {
        state.sendingCode = Boolean(isLoading);

        if (elements.changeNumber) {
            elements.changeNumber.disabled = state.sendingCode || state.verifyingCode;
        }

        updateResendUi();
    }

    function setOtpActionState(isLoading) {
        state.verifyingCode = Boolean(isLoading);

        if (elements.changeNumber) {
            elements.changeNumber.disabled = state.sendingCode || state.verifyingCode;
        }

        updateResendUi();
    }

    function showNotice(type, message) {
        if (!elements.notice) {
            return;
        }

        if (!message) {
            elements.notice.hidden = true;
            elements.notice.className = 'bornado-auth-modal__notice';
            elements.notice.textContent = '';
            return;
        }

        elements.notice.hidden = false;
        elements.notice.className = 'bornado-auth-modal__notice is-' + type;
        elements.notice.textContent = message;
    }

    function resetState() {
        state.mode = 'login';
        state.method = config.phoneEnabled ? 'phone' : 'email';
        state.otpFlow = null;
        state.redirectUrl = config.afterLoginUrl || window.location.href;
        state.previousView = 'login-email';
        state.sendingCode = false;
        state.verifyingCode = false;

        stopResendCountdown();

        Object.keys(forms).forEach(function (key) {
            if (forms[key]) {
                forms[key].reset();
                toggleSubmitState(forms[key], false);
            }
        });

        showNotice('info', '');
        updateCaptchaFlags();
        setPhoneRequestState(false);
        setOtpActionState(false);

        if (window.grecaptcha && config.captchaType === 'v2') {
            ['login', 'register'].forEach(function (type) {
                if (state.captchaWidgets[type] !== null) {
                    try {
                        window.grecaptcha.reset(state.captchaWidgets[type]);
                    } catch (error) {}
                }
            });
        }

        signOutFirebase();
        resetFirebaseVerifier();
        syncView();
    }

    async function ensureFirebase() {
        if (!config.phoneEnabled) {
            throw new Error(getI18n('genericError'));
        }

        if (!window.firebase || !window.firebase.auth) {
            throw new Error(getI18n('genericError'));
        }

        if (!state.firebase.appReady) {
            if (!window.firebase.apps || !window.firebase.apps.length) {
                window.firebase.initializeApp(config.firebase || {});
            } else {
                window.firebase.app();
            }

            if (window.firebase.analytics) {
                try {
                    window.firebase.analytics();
                } catch (error) {}
            }

            state.firebase.auth = window.firebase.auth();
            state.firebase.auth.languageCode = document.documentElement.lang || 'fa';
            state.firebase.appReady = true;
        }
    }

    async function resetFirebaseVerifier() {
        if (state.firebase.verifier && typeof state.firebase.verifier.clear === 'function') {
            try {
                state.firebase.verifier.clear();
            } catch (error) {}
        }
        state.firebase.verifier = null;
        rebuildFirebaseRecaptchaContainer();
    }

    function rebuildFirebaseRecaptchaContainer() {
        const current = document.getElementById('bornado-auth-firebase-recaptcha');
        if (!current || !current.parentNode) {
            return current;
        }

        const replacement = document.createElement('div');
        replacement.id = 'bornado-auth-firebase-recaptcha';
        replacement.className = current.className;
        current.parentNode.replaceChild(replacement, current);

        return replacement;
    }

    async function signOutFirebase() {
        if (state.firebase.auth && state.firebase.auth.currentUser) {
            try {
                await state.firebase.auth.signOut();
            } catch (error) {}
        }
    }

    function shouldInterceptAuthLink(link) {
        if (!link || !link.href) {
            return false;
        }

        return matchUrlPath(link.href, config.signInUrl) || matchUrlPath(link.href, config.signUpUrl);
    }

    function matchUrlPath(candidate, reference) {
        if (!candidate || !reference) {
            return false;
        }

        const candidateUrl = safeUrl(candidate);
        const referenceUrl = safeUrl(reference);

        return candidateUrl.origin === referenceUrl.origin && normalizePath(candidateUrl.pathname) === normalizePath(referenceUrl.pathname);
    }

    function normalizePath(pathname) {
        return String(pathname || '/').replace(/\/+$/, '') || '/';
    }

    function safeUrl(url) {
        try {
            return new URL(url, window.location.origin);
        } catch (error) {
            return new URL(window.location.href);
        }
    }

    function resolveRedirectUrl(fallbackHref) {
        const currentUrl = window.location.href;

        if (!fallbackHref) {
            return currentUrl;
        }

        const parsed = safeUrl(fallbackHref);
        const redirect = parsed.searchParams.get('u');

        return redirect || currentUrl;
    }

    function redirectAfterSuccess(flowType) {
        const isRegisterFlow = flowType === 'register';
        const target = isRegisterFlow
            ? (config.registerRedirectUrl || config.profileUrl || state.redirectUrl || window.location.href)
            : (state.redirectUrl || config.afterLoginUrl || config.profileUrl || window.location.href);

        if (bootstrapModal) {
            bootstrapModal.hide();
        } else {
            modalElement.classList.remove('show');
            modalElement.style.display = 'none';
        }

        window.setTimeout(function () {
            window.location.href = target;
        }, 160);
    }

    function postAjax(data, expectJson = true) {
        return new Promise(function (resolve, reject) {
            $.ajax({
                url: config.ajaxUrl,
                method: 'POST',
                data: data,
                dataType: expectJson ? 'json' : 'text'
            }).done(function (response) {
                if (expectJson && response && response.success === false) {
                    reject(response);
                    return;
                }

                resolve(response);
            }).fail(function (xhr) {
                if (xhr.responseJSON) {
                    reject(xhr.responseJSON);
                    return;
                }

                reject(xhr.responseText || getI18n('networkError'));
            });
        });
    }

    function extractMessage(error) {
        if (!error) {
            return getI18n('genericError');
        }

        if (error.code) {
            const firebaseMessage = getFirebaseErrorMessage(error.code);
            if (firebaseMessage) {
                return firebaseMessage;
            }
        }

        if (typeof error === 'string') {
            return translateServerMessage(error);
        }

        if (error.message) {
            return translateServerMessage(error.message);
        }

        if (error.data && error.data.message) {
            return translateServerMessage(error.data.message);
        }

        if (error.responseJSON && error.responseJSON.data && error.responseJSON.data.message) {
            return translateServerMessage(error.responseJSON.data.message);
        }

        if (error.code && error.message) {
            return translateServerMessage(error.message);
        }

        return getI18n('genericError');
    }

    function isValidPhone(value) {
        return /^\+\d{8,16}$/.test(value);
    }

    function isValidEmail(value) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(value || '').trim());
    }

    function getI18n(key) {
        return config.i18n && config.i18n[key] ? config.i18n[key] : key;
    }

    function updateModalTitle() {
        if (!elements.modalTitle) {
            return;
        }

        elements.modalTitle.textContent = state.mode === 'register'
            ? getI18n('registerTitle')
            : getI18n('loginTitle');
    }

    function translateServerMessage(message) {
        const rawMessage = String(message || '').trim();
        if (!rawMessage) {
            return getI18n('genericError');
        }

        const exactMap = {
            'INVALID_ID_TOKEN': 'اعتبار نشست تایید نامعتبر است. دوباره کد بگیر.',
            'TOKEN_EXPIRED': 'زمان تایید به پایان رسیده است. دوباره کد بگیر.',
            'USER_DISABLED': 'این حساب غیرفعال شده است.',
            'Invalid email or password.': 'ایمیل یا رمز عبور نادرست است.',
            'The email address is not correct.': 'ایمیل واردشده صحیح نیست.',
            'This username is already registered.': 'این نام کاربری قبلا ثبت شده است.',
            'This email address is already registered.': 'این ایمیل قبلا ثبت شده است.',
            'Cannot create a user with an empty nicename.': 'نام کاربری قابل ساخت نبود. لطفا دوباره تلاش کنید.',
            'Could not save password reset key to database.': 'ارسال لینک بازیابی انجام نشد. دوباره تلاش کنید.'
        };

        if (exactMap[rawMessage]) {
            return exactMap[rawMessage];
        }

        const containsMap = [
            ['nicename', 'نام کاربری قابل ساخت نبود. لطفا دوباره تلاش کنید.'],
            ['captcha', 'تایید امنیتی ناموفق بود. دوباره تلاش کن.'],
            ['security', 'اعتبار امنیتی درخواست نامعتبر است. صفحه را تازه‌سازی کن و دوباره تلاش کن.'],
            ['phone number', 'شماره موبایل معتبر نیست یا قبلا ثبت شده است.'],
            ['email address', 'ایمیل واردشده معتبر نیست یا قبلا استفاده شده است.'],
            ['user name', 'این نام کاربری قبلا ثبت شده است.'],
            ['expired', 'زمان این درخواست به پایان رسیده است. دوباره تلاش کن.'],
            ['too many attempts', 'تلاش‌های زیادی انجام شده. کمی بعد دوباره امتحان کن.'],
            ['too many requests', 'تلاش‌های زیادی انجام شده. کمی بعد دوباره امتحان کن.']
        ];

        const lowerMessage = rawMessage.toLowerCase();
        for (let i = 0; i < containsMap.length; i += 1) {
            if (lowerMessage.indexOf(containsMap[i][0]) !== -1) {
                return containsMap[i][1];
            }
        }

        return rawMessage;
    }

    function getFirebaseErrorMessage(code) {
        const messages = {
            'auth/invalid-phone-number': getI18n('invalidPhone'),
            'auth/missing-phone-number': getI18n('invalidPhone'),
            'auth/invalid-verification-code': getI18n('wrongOtp'),
            'auth/code-expired': 'کد تایید منقضی شده است. دوباره کد بگیر.',
            'auth/session-expired': 'نشست تایید منقضی شده است. دوباره کد بگیر.',
            'auth/too-many-requests': 'تلاش‌های زیادی انجام شده. کمی بعد دوباره امتحان کن.',
            'auth/quota-exceeded': 'سهمیه ارسال کد تکمیل شده است. کمی بعد دوباره امتحان کن.',
            'auth/captcha-check-failed': 'تایید امنیتی ناموفق بود. دوباره تلاش کن.',
            'auth/network-request-failed': getI18n('networkError')
        };

        return messages[code] || '';
    }
}(jQuery));
