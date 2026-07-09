(function ($) {
    const config = window.bornadoAuthModal || {};
    const rootElement = document.getElementById('bornado-auth-modal') || document.getElementById('bornado-auth-inline');
    const isInline = rootElement && rootElement.id === 'bornado-auth-inline';

    if (!rootElement || !config.ajaxUrl || !config.phoneEnabled) {
        return;
    }

    const bootstrapModal = window.bootstrap && window.bootstrap.Modal
        && !isInline
        ? new window.bootstrap.Modal(rootElement)
        : null;
    const defaultMode = rootElement.dataset && rootElement.dataset.defaultMode === 'register' ? 'register' : 'login';

    const state = {
        intent: defaultMode,
        currentView: 'phone-entry',
        redirectUrl: getDefaultRedirectTarget(),
        continueTokenHandled: false,
        continueToken: '',
        continueFlowSource: '',
        claimAdId: 0,
        claimPhoneNumber: '',
        phoneNumber: '',
        phoneDialCode: '',
        existingUser: false,
        otpFlow: null,
        pendingRegister: null,
        resendCountdown: 0,
        resendInterval: null,
        sendingCode: false,
        verifyingCode: false,
        firebase: {
            assetsReady: false,
            appReady: false,
            loadPromise: null,
            auth: null,
            verifier: null
        }
    };

    const elements = {
        notice: rootElement.querySelector('#bornado-auth-notice'),
        modalTitle: rootElement.querySelector('#bornado-auth-modal-title'),
        modalSubtitle: rootElement.querySelector('#bornado-auth-modal-subtitle'),
        views: Array.from(rootElement.querySelectorAll('.bornado-auth-view')),
        otpCode: rootElement.querySelector('#bornado-auth-otp-code'),
        resendButton: rootElement.querySelector('#bornado-auth-resend-code'),
        resendTimer: rootElement.querySelector('#bornado-auth-resend-timer'),
        loginWithOtp: rootElement.querySelector('#bornado-auth-login-with-otp'),
        backToPassword: rootElement.querySelector('#bornado-auth-back-to-password'),
        resetButtons: Array.from(rootElement.querySelectorAll('[data-auth-reset]'))
    };

    const forms = {
        phoneEntry: rootElement.querySelector('#bornado-auth-phone-entry-form'),
        passwordLogin: rootElement.querySelector('#bornado-auth-password-login-form'),
        otp: rootElement.querySelector('#bornado-auth-otp-form'),
        setup: rootElement.querySelector('#bornado-auth-setup-form')
    };

    const phoneCountries = Array.isArray(config.phoneCountries) ? config.phoneCountries : [];
    const defaultPhoneCountry = config.defaultPhoneCountry && typeof config.defaultPhoneCountry === 'object'
        ? config.defaultPhoneCountry
        : null;

    init();

    function init() {
        bindTriggers();
        bindForms();
        bindModalLifecycle();
        enhancePhoneField();
        updateHeading();
        maybeStartContinueTokenFlow();
    }

    function bindTriggers() {
        document.addEventListener('click', function (event) {
            const trigger = event.target.closest('[data-bornado-auth-open]');
            if (trigger) {
                event.preventDefault();
                const continueToken = String(trigger.dataset.continueToken || '').trim();
                if (continueToken) {
                    closeParentClaimModal(trigger);
                    window.setTimeout(function () {
                        startContinueTokenFlow(continueToken, resolveRedirectUrl(trigger.getAttribute('href')));
                    }, 180);
                } else {
                    openModal({
                        mode: trigger.dataset.mode || 'login',
                        redirectUrl: resolveRedirectUrl(trigger.getAttribute('href'))
                    });
                }
                return;
            }

            const link = event.target.closest('a[href]');
            if (!link || !shouldInterceptAuthLink(link)) {
                return;
            }

            event.preventDefault();

            openModal({
                mode: matchUrlPath(link.href, config.signUpUrl) ? 'register' : 'login',
                redirectUrl: resolveRedirectUrl(link.href)
            });
        });
    }

    function bindForms() {
        if (forms.phoneEntry) {
            forms.phoneEntry.addEventListener('submit', function (event) {
                event.preventDefault();
                submitPhoneEntry();
            });
        }

        if (forms.passwordLogin) {
            forms.passwordLogin.addEventListener('submit', function (event) {
                event.preventDefault();
                submitPasswordLogin();
            });
        }

        if (forms.otp) {
            forms.otp.addEventListener('submit', function (event) {
                event.preventDefault();
                verifyPhoneCode();
            });
        }

        if (forms.setup) {
            forms.setup.addEventListener('submit', function (event) {
                event.preventDefault();
                submitSetupAccount();
            });
        }

        elements.resetButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                if (isBusy()) {
                    return;
                }
                resetJourney(true);
                switchView('phone-entry');
                showNotice('info', '');
                focusPhoneEntry();
            });
        });

        if (elements.loginWithOtp) {
            elements.loginWithOtp.addEventListener('click', function () {
                if (!state.phoneNumber || isBusy()) {
                    return;
                }
                startOtpFlow({
                    mode: 'login',
                    phoneNumber: state.phoneNumber,
                    phoneDialCode: state.phoneDialCode,
                    remember: getRememberLoginValue()
                }, false);
            });
        }

        if (elements.backToPassword) {
            elements.backToPassword.addEventListener('click', function () {
                if (isBusy()) {
                    return;
                }
                switchView('password-login');
                const passwordInput = forms.passwordLogin
                    ? forms.passwordLogin.querySelector('input[name="password"]')
                    : null;
                if (passwordInput) {
                    passwordInput.focus();
                }
            });
        }

        if (elements.resendButton) {
            elements.resendButton.addEventListener('click', function () {
                if (!state.otpFlow || state.resendCountdown > 0 || isBusy()) {
                    return;
                }
                startOtpFlow(state.otpFlow, true);
            });
        }
    }

    function bindModalLifecycle() {
        if (!bootstrapModal) {
            return;
        }

        rootElement.addEventListener('hidden.bs.modal', function () {
            resetJourney(true);
            switchView('phone-entry');
            showNotice('info', '');
        });
    }

    function openModal(options) {
        resetJourney(true);
        state.intent = options.mode === 'register' ? 'register' : 'login';
        state.redirectUrl = resolveSafeRedirectTarget(options.redirectUrl, getDefaultRedirectTarget());
        switchView('phone-entry');
        showNotice('info', '');

        if (bootstrapModal) {
            bootstrapModal.show();
        } else {
            if (isInline && typeof rootElement.scrollIntoView === 'function') {
                rootElement.scrollIntoView({ behavior: 'smooth', block: 'start' });
            } else {
                rootElement.classList.add('show');
                rootElement.style.display = 'block';
            }
        }

        window.setTimeout(focusPhoneEntry, 60);
    }

    async function maybeStartContinueTokenFlow() {
        const parsed = safeUrl(window.location.href);
        const continueToken = parsed.searchParams.get('bornado_continue_token');

        if (!continueToken || state.continueTokenHandled) {
            return;
        }

        parsed.searchParams.delete('bornado_continue_token');
        if (window.history && window.history.replaceState) {
            window.history.replaceState({}, document.title, parsed.toString());
        }

        await startContinueTokenFlow(continueToken, config.profileUrl || window.location.href);
    }

    async function startContinueTokenFlow(continueToken, fallbackRedirectUrl) {
        if (!continueToken || state.continueTokenHandled) {
            return;
        }

        state.continueTokenHandled = true;

        openModal({
            mode: 'login',
            redirectUrl: fallbackRedirectUrl || config.profileUrl || window.location.href
        });
        state.continueToken = String(continueToken || '').trim();

        switchView('otp');
        toggleSubmitState(forms.otp, true);
        showNotice('info', getI18n('loading'));

        try {
            const response = await postAjax({
                action: 'bornado_auth_resolve_continue_token',
                security: config.continueTokenNonce,
                continue_token: continueToken
            });
            const data = response && response.data ? response.data : {};
            const phoneNumber = String(data.phone_number || '').trim();
            const nextStep = String(data.next_step || '').trim();
            const existingUser = Boolean(data.existing_user);
            const flowSource = normalizeContinueFlowSource(data.flow_source, data.claim_ad_id);
            const isClaimFlow = flowSource === 'claim';

            if (!phoneNumber) {
                throw new Error(getI18n('genericError'));
            }

            state.redirectUrl = resolveSafeRedirectTarget(
                data.redirect_url,
                config.profileUrl || getDefaultRedirectTarget()
            );
            state.continueFlowSource = flowSource;
            state.claimAdId = parseInt(data.claim_ad_id, 10) || 0;
            state.intent = data.mode === 'register' ? 'register' : 'login';
            state.claimPhoneNumber = phoneNumber;
            state.phoneNumber = phoneNumber;
            state.phoneDialCode = '';
            state.existingUser = existingUser || state.intent === 'login';
            primePhoneInput(phoneNumber);
            if (isClaimFlow) {
                showNoticeHtml('info', buildContinueFlowMessage(phoneNumber, state.existingUser));
            } else {
                showNotice('info', '');
            }

            if (isClaimFlow && ('password' === nextStep || state.existingUser)) {
                switchView('password-login');
                focusPasswordInput();
                return;
            }

            await startOtpFlow({
                mode: state.intent,
                phoneNumber: phoneNumber,
                phoneDialCode: '',
                remember: data.remember || '1'
            }, false);
        } catch (error) {
            if (shouldFallbackToDefaultLogin(error)) {
                openModal({
                    mode: 'login',
                    redirectUrl: getDefaultRedirectTarget()
                });
                return;
            }

            switchView('phone-entry');
            showNotice('error', extractMessage(error));
        } finally {
            toggleSubmitState(forms.otp, false);
        }
    }

    async function submitPhoneEntry() {
        if (!forms.phoneEntry || isBusy()) {
            return;
        }

        const phoneInput = forms.phoneEntry.querySelector('input[name="phone_number"]');
        syncPhoneCountrySelection(forms.phoneEntry, phoneInput ? phoneInput.value : '', true);
        const phoneDialCode = getPhoneDialCode(forms.phoneEntry);
        const phoneNumber = normalizePhone(phoneInput ? phoneInput.value : '', phoneDialCode);

        if (!isValidPhone(phoneNumber)) {
            showNotice('error', getI18n('invalidPhone'));
            if (phoneInput) {
                phoneInput.focus();
            }
            return;
        }

        toggleSubmitState(forms.phoneEntry, true);
        showNotice('info', getI18n('loading'));

        try {
            const response = await postAjax({
                action: 'bornado_auth_phone_preflight',
                security: config.phonePreflightNonce,
                phone_number: phoneNumber,
                phone_dial_code: phoneDialCode
            });
            const data = response && response.data ? response.data : {};

            state.phoneNumber = String(data.phone_number || phoneNumber).trim();
            state.phoneDialCode = phoneDialCode;
            state.existingUser = Boolean(data.existing_user);
            state.pendingRegister = null;
            state.otpFlow = null;
            primePhoneInput(state.phoneNumber);

            if (state.existingUser) {
                state.intent = 'login';
                switchView('password-login');
                focusPasswordInput();
                showNotice('info', '');
                return;
            }

            state.intent = 'register';
            await startOtpFlow({
                mode: 'register',
                phoneNumber: state.phoneNumber,
                phoneDialCode: state.phoneDialCode,
                remember: '1'
            }, false);
        } catch (error) {
            showNotice('error', extractMessage(error));
        } finally {
            toggleSubmitState(forms.phoneEntry, false);
        }
    }

    async function submitPasswordLogin() {
        if (!forms.passwordLogin || !state.phoneNumber || isBusy()) {
            return;
        }

        const passwordInput = forms.passwordLogin.querySelector('input[name="password"]');
        const password = passwordInput ? String(passwordInput.value || '') : '';

        if (!password.trim()) {
            showNotice('error', getI18n('passwordRequired'));
            if (passwordInput) {
                passwordInput.focus();
            }
            return;
        }

        toggleSubmitState(forms.passwordLogin, true);
        showNotice('info', getI18n('loading'));

        try {
            const response = await postAjax({
                action: 'bornado_auth_phone_password_login',
                security: config.phonePasswordLoginNonce,
                phone_number: state.phoneNumber,
                phone_dial_code: state.phoneDialCode || '',
                password: password,
                remember: getRememberLoginValue()
            });

            showNotice('success', response.data && response.data.message ? response.data.message : getI18n('phoneLoginSuccess'));
            redirectAfterSuccess('login');
        } catch (error) {
            showOtpShortcutIfPasswordError(extractMessage(error));
        } finally {
            toggleSubmitState(forms.passwordLogin, false);
        }
    }

    async function startOtpFlow(flow, isResend) {
        if (!flow || !flow.phoneNumber || isBusy()) {
            return;
        }

        state.sendingCode = true;
        updateResendUi();
        showNotice('info', getI18n('loading'));

        try {
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
                mode: flow.mode === 'register' ? 'register' : 'login',
                phoneNumber: flow.phoneNumber,
                phoneDialCode: flow.phoneDialCode || '',
                remember: flow.remember || '0',
                confirmationResult: confirmationResult
            };
            state.pendingRegister = null;

            if (elements.otpCode) {
                elements.otpCode.value = '';
            }

            switchView('otp');
            startResendCountdown();
            showNotice('success', isResend ? getI18n('verificationSent') : getI18n('verificationSent'));
            if (elements.otpCode) {
                elements.otpCode.focus();
            }
        } catch (error) {
            showNotice('error', extractMessage(error));
        } finally {
            state.sendingCode = false;
            updateResendUi();
        }
    }

    async function verifyPhoneCode() {
        if (!forms.otp || !state.otpFlow || !state.otpFlow.confirmationResult || isBusy()) {
            showNotice('error', getI18n('genericError'));
            return;
        }

        const code = String((elements.otpCode && elements.otpCode.value) || '').trim();
        if (!/^\d{6}$/.test(code)) {
            showNotice('error', code ? getI18n('otpLength') : getI18n('otpRequired'));
            if (elements.otpCode) {
                elements.otpCode.focus();
            }
            return;
        }

        state.verifyingCode = true;
        toggleSubmitState(forms.otp, true);
        updateResendUi();
        showNotice('info', getI18n('loading'));

        try {
            const firebaseResponse = await state.otpFlow.confirmationResult.confirm(code);
            const idToken = await firebaseResponse.user.getIdToken();

            if (state.otpFlow.mode === 'login') {
                const response = await postAjax({
                    action: 'bornado_auth_firebase_login',
                    security: config.firebaseLoginNonce,
                    phone_number: state.otpFlow.phoneNumber,
                    phone_dial_code: state.otpFlow.phoneDialCode || '',
                    claim_ad_id: state.claimAdId || 0,
                    continue_token: state.continueToken || '',
                    id_token: idToken,
                    remember: state.otpFlow.remember || '0'
                });

                showNotice('success', response.data && response.data.message ? response.data.message : getI18n('phoneLoginSuccess'));
                await signOutFirebase();
                redirectAfterSuccess('login');
                return;
            }

            state.pendingRegister = {
                idToken: idToken,
                phoneNumber: state.otpFlow.phoneNumber,
                phoneDialCode: state.otpFlow.phoneDialCode || '',
                remember: '1'
            };
            state.otpFlow = null;
            stopResendCountdown();
            await signOutFirebase();
            await resetFirebaseVerifier();
            switchView('setup-account');
            showNotice('success', getI18n('phoneVerified'));
            focusSetupPassword();
        } catch (error) {
            showNotice('error', extractMessage(error));
            if (elements.otpCode) {
                elements.otpCode.focus();
                elements.otpCode.select();
            }
        } finally {
            state.verifyingCode = false;
            toggleSubmitState(forms.otp, false);
            updateResendUi();
        }
    }

    async function submitSetupAccount() {
        if (!forms.setup || !state.pendingRegister || isBusy()) {
            return;
        }

        const nameInput = forms.setup.querySelector('input[name="display_name"]');
        const passwordInput = forms.setup.querySelector('input[name="password"]');
        const confirmInput = forms.setup.querySelector('input[name="password_confirm"]');
        const termsInput = rootElement.querySelector('#bornado-auth-setup-terms');
        const displayName = String((nameInput && nameInput.value) || '').trim();
        const password = String((passwordInput && passwordInput.value) || '');
        const passwordConfirm = String((confirmInput && confirmInput.value) || '');

        if (!password.trim()) {
            showNotice('error', getI18n('passwordRequired'));
            if (passwordInput) {
                passwordInput.focus();
            }
            return;
        }

        if (password.length < 6) {
            showNotice('error', getI18n('passwordTooShort'));
            if (passwordInput) {
                passwordInput.focus();
            }
            return;
        }

        if (password !== passwordConfirm) {
            showNotice('error', getI18n('passwordMismatch'));
            if (confirmInput) {
                confirmInput.focus();
            }
            return;
        }

        if (termsInput && !termsInput.checked) {
            showNotice('error', getI18n('termsRequired'));
            termsInput.focus();
            return;
        }

        toggleSubmitState(forms.setup, true);
        showNotice('info', getI18n('loading'));

        try {
            const response = await postAjax({
                action: 'bornado_auth_firebase_register',
                security: config.firebaseRegisterNonce,
                phone_number: state.pendingRegister.phoneNumber,
                phone_dial_code: state.pendingRegister.phoneDialCode || '',
                claim_ad_id: state.claimAdId || 0,
                continue_token: state.continueToken || '',
                id_token: state.pendingRegister.idToken,
                password: password,
                display_name: displayName,
                remember: state.pendingRegister.remember || '1'
            });

            showNotice('success', response.data && response.data.message ? response.data.message : getI18n('phoneRegisterSuccess'));
            await signOutFirebase();
            redirectAfterSuccess('register');
        } catch (error) {
            showNotice('error', extractMessage(error));
        } finally {
            toggleSubmitState(forms.setup, false);
        }
    }

    function switchView(viewName) {
        state.currentView = viewName;

        elements.views.forEach(function (view) {
            view.classList.toggle('is-active', view.dataset.view === viewName);
        });

        updateHeading();
    }

    function updateHeading() {
        if (!elements.modalTitle || !elements.modalSubtitle) {
            return;
        }

        let title = getI18n('defaultTitle');
        let subtitle = getI18n('defaultSubtitle');

        if (state.currentView === 'password-login') {
            title = getI18n('passwordTitle');
            subtitle = getI18n('passwordSubtitle');
        } else if (state.currentView === 'otp') {
            title = state.otpFlow && state.otpFlow.mode === 'login'
                ? getI18n('otpFallbackLabel')
                : getI18n('otpTitle');
            subtitle = getI18n('otpSubtitle');
        } else if (state.currentView === 'setup-account') {
            title = getI18n('setupTitle');
            subtitle = getI18n('setupSubtitle');
        }

        elements.modalTitle.textContent = title;
        if (subtitle.indexOf('bornado-inline-phone') > -1) {
            elements.modalSubtitle.innerHTML = subtitle;
        } else {
            elements.modalSubtitle.textContent = subtitle;
        }
        elements.modalSubtitle.hidden = !subtitle;

        if (elements.backToPassword) {
            elements.backToPassword.hidden = !(state.otpFlow && state.otpFlow.mode === 'login' && state.existingUser);
        }
    }

    function focusPhoneEntry() {
        const input = forms.phoneEntry ? forms.phoneEntry.querySelector('input[name="phone_number"]') : null;
        if (input) {
            input.focus();
        }
    }

    function focusPasswordInput() {
        const input = forms.passwordLogin ? forms.passwordLogin.querySelector('input[name="password"]') : null;
        if (input) {
            input.focus();
        }
    }

    function focusSetupPassword() {
        const input = forms.setup ? forms.setup.querySelector('input[name="password"]') : null;
        if (input) {
            input.focus();
        }
    }

    function syncPasswordLoginUsername(phoneNumber) {
        const input = forms.passwordLogin ? forms.passwordLogin.querySelector('input[name="username"]') : null;
        if (input) {
            input.value = String(phoneNumber || '').trim();
        }
    }

    function resetJourney(clearInput) {
        state.currentView = 'phone-entry';
        state.continueToken = '';
        state.continueFlowSource = '';
        state.claimPhoneNumber = '';
        state.phoneNumber = '';
        state.phoneDialCode = '';
        state.existingUser = false;
        state.continueTokenHandled = false;
        state.claimAdId = 0;
        state.otpFlow = null;
        state.pendingRegister = null;
        state.sendingCode = false;
        state.verifyingCode = false;
        stopResendCountdown();

        Object.keys(forms).forEach(function (key) {
            if (forms[key]) {
                if (clearInput) {
                    forms[key].reset();
                }
                toggleSubmitState(forms[key], false);
            }
        });

        signOutFirebase();
        resetFirebaseVerifier();
        updateResendUi();
        syncPasswordLoginUsername('');
    }

    function closeParentClaimModal(element) {
        const claimModal = element && element.closest ? element.closest('.bornad-claim-modal') : null;

        if (!claimModal) {
            return;
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            const modalInstance = window.bootstrap.Modal.getInstance(claimModal) || window.bootstrap.Modal.getOrCreateInstance(claimModal);
            if (modalInstance) {
                modalInstance.hide();
                return;
            }
        }

        claimModal.classList.remove('show');
        claimModal.style.display = 'none';
        claimModal.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('modal-open');
        document.querySelectorAll('.modal-backdrop').forEach(function (backdrop) {
            backdrop.remove();
        });
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

    function stopResendCountdown() {
        clearInterval(state.resendInterval);
        state.resendInterval = null;
        state.resendCountdown = 0;
        updateResendUi();
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
        } else if (state.resendCountdown > 0) {
            elements.resendTimer.textContent = getI18n('resendIn') + ' ' + state.resendCountdown + ' ' + getI18n('seconds');
        } else {
            elements.resendTimer.textContent = '';
            elements.resendTimer.hidden = true;
        }
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

    function showNoticeHtml(type, html) {
        if (!elements.notice) {
            return;
        }

        if (!html) {
            showNotice('info', '');
            return;
        }

        elements.notice.hidden = false;
        elements.notice.className = 'bornado-auth-modal__notice is-' + type;
        elements.notice.innerHTML = html;
    }

    function showOtpShortcutIfPasswordError(message) {
        if (String(message || '').trim() !== getI18n('passwordErrorWithOtp')) {
            showNotice('error', message);
            return;
        }

        showNoticeHtml(
            'error',
            escapeHtml(message) + ' <button type="button" class="bornado-auth-link bornado-auth-notice__link" data-notice-otp="1">' + escapeHtml(getI18n('otpFallbackLabel')) + '</button>'
        );

        const shortcut = elements.notice ? elements.notice.querySelector('[data-notice-otp="1"]') : null;
        if (shortcut) {
            shortcut.addEventListener('click', function () {
                if (!state.phoneNumber || isBusy()) {
                    return;
                }
                startOtpFlow({
                    mode: 'login',
                    phoneNumber: state.phoneNumber,
                    phoneDialCode: state.phoneDialCode,
                    remember: getRememberLoginValue()
                }, false);
            }, { once: true });
        }
    }

    function isBusy() {
        return Boolean(state.sendingCode || state.verifyingCode);
    }

    function getRememberLoginValue() {
        const checkbox = forms.passwordLogin
            ? forms.passwordLogin.querySelector('input[name="remember"]')
            : null;

        return checkbox && checkbox.checked ? '1' : '0';
    }

    function buildContinueFlowMessage(phoneNumber, existingUser) {
        if (!phoneNumber) {
            return '';
        }

        return formatI18n(
            existingUser ? getI18n('claimLoginSubtitle') : getI18n('claimRegisterSubtitle'),
            phoneNumber
        );
    }

    function normalizeContinueFlowSource(flowSource, claimAdId) {
        const normalized = String(flowSource || '').trim().toLowerCase();

        if (normalized) {
            return normalized;
        }

        return parseInt(claimAdId, 10) > 0 ? 'claim' : 'notification';
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

    function hasExplicitDialCode(value) {
        const cleaned = String(value || '').trim().replace(/[^\d+]/g, '');
        return cleaned.indexOf('+') === 0 || cleaned.indexOf('00') === 0;
    }

    function getResolvedDefaultPhoneCountry() {
        const runtimeCountry = window.BornadoPhoneCountryPickerResolvedCountry
            && typeof window.BornadoPhoneCountryPickerResolvedCountry === 'object'
            ? window.BornadoPhoneCountryPickerResolvedCountry
            : null;

        if (runtimeCountry && runtimeCountry.dialCode) {
            return runtimeCountry;
        }

        const browserCountry = resolveBrowserSuggestedCountry();
        if (browserCountry && browserCountry.dialCode) {
            return browserCountry;
        }

        return defaultPhoneCountry && defaultPhoneCountry.dialCode ? defaultPhoneCountry : null;
    }

    function getCountryByCountryCode(countryCode) {
        const normalized = String(countryCode || '').trim().toUpperCase();
        let match = null;

        if (!normalized) {
            return null;
        }

        phoneCountries.some(function (country) {
            if (String(country && country.countryCode ? country.countryCode : '').trim().toUpperCase() === normalized) {
                match = country;
                return true;
            }

            return false;
        });

        return match;
    }

    function countryCodeFromLocale(locale) {
        const normalized = String(locale || '').trim().replace(/_/g, '-');
        const parts = normalized.split('-').filter(Boolean);
        const languageMap = {
            fa: 'IR',
            ar: 'AE',
            en: 'GB'
        };

        if (parts.length > 1 && /^[A-Za-z]{2}$/.test(parts[parts.length - 1])) {
            return String(parts[parts.length - 1]).toUpperCase();
        }

        if (parts.length && languageMap[parts[0].toLowerCase()]) {
            return languageMap[parts[0].toLowerCase()];
        }

        return '';
    }

    function countryCodeFromTimezone(timezone) {
        const timezoneMap = {
            'Europe/London': 'GB',
            'Asia/Tehran': 'IR',
            'Asia/Dubai': 'AE',
            'Asia/Baku': 'AZ',
            'Europe/Berlin': 'DE',
            'Europe/Paris': 'FR',
            'Europe/Amsterdam': 'NL',
            'Europe/Stockholm': 'SE',
            'Europe/Oslo': 'NO',
            'Europe/Copenhagen': 'DK',
            'Europe/Brussels': 'BE',
            'Europe/Vienna': 'AT',
            'Europe/Zurich': 'CH',
            'America/Toronto': 'CA',
            'America/Vancouver': 'CA',
            'America/New_York': 'US',
            'America/Los_Angeles': 'US',
            'Australia/Sydney': 'AU'
        };

        return timezoneMap[String(timezone || '').trim()] || '';
    }

    function resolveBrowserSuggestedCountry() {
        const locales = [];
        let timezone = '';
        let country;

        if (document.documentElement && document.documentElement.lang) {
            locales.push(String(document.documentElement.lang));
        }

        if (Array.isArray(navigator.languages)) {
            navigator.languages.forEach(function (locale) {
                if (locale) {
                    locales.push(String(locale));
                }
            });
        }

        if (navigator.language) {
            locales.push(String(navigator.language));
        }

        for (let index = 0; index < locales.length; index += 1) {
            country = getCountryByCountryCode(countryCodeFromLocale(locales[index]));
            if (country) {
                return country;
            }
        }

        try {
            timezone = Intl.DateTimeFormat().resolvedOptions().timeZone || '';
        } catch (_error) {
            timezone = '';
        }

        return getCountryByCountryCode(countryCodeFromTimezone(timezone));
    }

    function digitsOnly(value) {
        return String(value || '').replace(/[^\d]/g, '');
    }

    function inferCountryFromPhone(value) {
        if (!hasExplicitDialCode(value)) {
            return null;
        }

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

    function updateDialCodeSelect(select, dialCode) {
        const normalizedDialCode = sanitizeDialCode(dialCode);

        if (!select || !normalizedDialCode) {
            return '';
        }

        if (sanitizeDialCode(select.value) === normalizedDialCode) {
            return normalizedDialCode;
        }

        select.value = normalizedDialCode;
        select.dispatchEvent(new Event('change', { bubbles: true }));

        return normalizedDialCode;
    }

    function getPickerDialCode(select) {
        const root = select && select.closest ? select.closest('.bpcp') : null;
        return root && root.dataset && root.dataset.currentDialCode
            ? sanitizeDialCode(root.dataset.currentDialCode)
            : '';
    }

    function rememberPhoneSyncState(input, mode, dialCode, localDigits) {
        if (!input || !input.dataset) {
            return;
        }

        input.dataset.phoneSyncMode = String(mode || '');
        input.dataset.phoneSyncDialCode = sanitizeDialCode(dialCode || '');
        input.dataset.phoneSyncLocalDigits = String(localDigits || '');
    }

    function resolveLocalDigitsForSync(input, dialCode) {
        const raw = input ? String(input.value || '').trim() : '';
        const normalizedDialCode = sanitizeDialCode(dialCode || '');
        const dialDigits = digitsOnly(normalizedDialCode);
        const cleaned = raw.replace(/[^\d+]/g, '');
        const rawDigits = digitsOnly(cleaned);
        const storedLocalDigits = input && input.dataset ? String(input.dataset.phoneSyncLocalDigits || '') : '';
        const storedDialDigits = input && input.dataset ? digitsOnly(input.dataset.phoneSyncDialCode || '') : '';

        if (storedLocalDigits) {
            return storedLocalDigits;
        }

        if (!rawDigits) {
            return '';
        }

        if (cleaned.charAt(0) === '+' && storedDialDigits && rawDigits.indexOf(storedDialDigits) === 0) {
            return rawDigits.slice(storedDialDigits.length);
        }

        if (cleaned.charAt(0) === '+' && dialDigits && rawDigits.indexOf(dialDigits) === 0) {
            return rawDigits.slice(dialDigits.length);
        }

        return rawDigits.replace(/^0+/, '');
    }

    function rewritePhoneForSelectedDial(input, dialCode) {
        const normalizedDialCode = sanitizeDialCode(dialCode);
        const dialDigits = digitsOnly(normalizedDialCode);
        const localDigits = resolveLocalDigitsForSync(input, normalizedDialCode);

        if (!input || !normalizedDialCode || !dialDigits || !localDigits) {
            return false;
        }

        input.value = '+' + dialDigits + localDigits;
        rememberPhoneSyncState(input, 'auto', normalizedDialCode, localDigits);
        return true;
    }

    function syncPhoneCountrySelection(form, phoneValue, applyDefaultFallback) {
        const select = form ? form.querySelector('select[name="phone_dial_code"]') : null;
        const inferredCountry = inferCountryFromPhone(phoneValue);
        const resolvedDefaultPhoneCountry = getResolvedDefaultPhoneCountry();

        if (select && inferredCountry && inferredCountry.dialCode) {
            return updateDialCodeSelect(select, inferredCountry.dialCode);
        }

        if (select && applyDefaultFallback && !sanitizeDialCode(select.value) && resolvedDefaultPhoneCountry && resolvedDefaultPhoneCountry.dialCode) {
            return updateDialCodeSelect(select, resolvedDefaultPhoneCountry.dialCode);
        }

        return select ? sanitizeDialCode(select.value) : '';
    }

    function getPhoneDialCode(form) {
        const resolvedDefaultPhoneCountry = getResolvedDefaultPhoneCountry();
        if (!form) {
            return resolvedDefaultPhoneCountry && resolvedDefaultPhoneCountry.dialCode ? resolvedDefaultPhoneCountry.dialCode : '';
        }

        const select = form.querySelector('select[name="phone_dial_code"]');
        const pickerDialCode = getPickerDialCode(select);
        return pickerDialCode
            ? pickerDialCode
            : (select && select.value
                ? select.value
                : (resolvedDefaultPhoneCountry && resolvedDefaultPhoneCountry.dialCode ? resolvedDefaultPhoneCountry.dialCode : ''));
    }

    function formatCountryOptionLabel(country) {
        const name = String(
            country && (country.displayNameFa || country.name || country.displayNameEn)
                ? (country.displayNameFa || country.name || country.displayNameEn)
                : ''
        ).trim();
        const dialCode = String(country && country.dialCode ? country.dialCode : '').trim();

        if (!dialCode) {
            return name;
        }

        return name + ' (\u2066' + dialCode + '\u2069)';
    }

    function decorateCountryOption(option, country) {
        if (!option || !country) {
            return;
        }

        option.dataset.termId = String(country.termId || '');
        option.dataset.countryCode = String(country.countryCode || '');
        option.dataset.displayNameFa = String(country.displayNameFa || country.name || '');
        option.dataset.displayNameEn = String(country.displayNameEn || '');
        option.dataset.searchTokens = String(country.searchTokens || '');
    }

    function primePhoneInput(phoneNumber) {
        const input = forms.phoneEntry ? forms.phoneEntry.querySelector('input[name="phone_number"]') : null;
        const select = forms.phoneEntry ? forms.phoneEntry.querySelector('select[name="phone_dial_code"]') : null;
        const country = inferCountryFromPhone(phoneNumber);

        if (input) {
            input.value = phoneNumber;
        }

        if (country && select && country.dialCode) {
            updateDialCodeSelect(select, country.dialCode);
        } else if (select && getResolvedDefaultPhoneCountry() && getResolvedDefaultPhoneCountry().dialCode) {
            updateDialCodeSelect(select, getResolvedDefaultPhoneCountry().dialCode);
        }

        syncPasswordLoginUsername(phoneNumber);
    }

    function enhancePhoneField() {
        if (!forms.phoneEntry || !phoneCountries.length) {
            return;
        }

        const input = forms.phoneEntry.querySelector('input[name="phone_number"]');
        const field = input ? input.closest('.bornado-auth-field') : null;
        const label = field ? field.querySelector('label[for="' + input.id + '"]') : null;
        let row;
        let select;

        if (!input || !field) {
            return;
        }

        select = field.querySelector('select[name="phone_dial_code"]');
        if (!select) {
            select = document.createElement('select');
            select.name = 'phone_dial_code';
            select.className = 'bornado-auth-country-select';
            select.setAttribute('aria-label', getI18n('countryLabel'));

            phoneCountries.forEach(function (country) {
                const option = document.createElement('option');
                option.value = String(country.dialCode || '');
                option.textContent = formatCountryOptionLabel(country);
                decorateCountryOption(option, country);
                select.appendChild(option);
            });
        }

        row = field.querySelector('.bornado-auth-phone-row');
        if (!row) {
            row = document.createElement('div');
            row.className = 'bornado-auth-phone-row';
            if (label && label.nextSibling) {
                field.insertBefore(row, label.nextSibling);
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

        syncPhoneCountrySelection(forms.phoneEntry, input.value, true);

        forms.phoneEntry.addEventListener('bpcp:change', function (event) {
            const dialCode = event && event.detail && event.detail.dialCode ? event.detail.dialCode : getPhoneDialCode(forms.phoneEntry);

            if (!input || !String(input.value || '').trim()) {
                return;
            }

            if (!hasExplicitDialCode(input.value) || String(input.dataset.phoneSyncMode || '') === 'auto') {
                rewritePhoneForSelectedDial(input, dialCode);
            }
        });

        select.addEventListener('change', function () {
            if (!input || !String(input.value || '').trim()) {
                return;
            }

            if (!hasExplicitDialCode(input.value) || String(input.dataset.phoneSyncMode || '') === 'auto') {
                rewritePhoneForSelectedDial(input, select.value);
            }
        });

        input.addEventListener('input', function () {
            if (hasExplicitDialCode(input.value)) {
                rememberPhoneSyncState(input, 'explicit', getPhoneDialCode(forms.phoneEntry), '');
                syncPhoneCountrySelection(forms.phoneEntry, input.value, false);
                return;
            }

            rememberPhoneSyncState(
                input,
                'local',
                getPhoneDialCode(forms.phoneEntry),
                digitsOnly(input.value).replace(/^0+/, '')
            );
        });

        input.addEventListener('blur', function () {
            const wasExplicit = hasExplicitDialCode(input.value);
            syncPhoneCountrySelection(forms.phoneEntry, input.value, true);
            const normalized = normalizePhone(input.value, getPhoneDialCode(forms.phoneEntry));
            if (normalized) {
                input.value = normalized;
                rememberPhoneSyncState(
                    input,
                    wasExplicit ? 'explicit' : 'auto',
                    getPhoneDialCode(forms.phoneEntry),
                    resolveLocalDigitsForSync(input, getPhoneDialCode(forms.phoneEntry))
                );
            }
        });
    }

    async function ensureFirebaseAssets() {
        const firebaseConfig = config.firebase && typeof config.firebase === 'object'
            ? config.firebase
            : {};
        const assetConfig = firebaseConfig.assets && typeof firebaseConfig.assets === 'object'
            ? firebaseConfig.assets
            : {};

        if (window.firebase && window.firebase.auth) {
            state.firebase.assetsReady = true;
            return;
        }

        if (!firebaseConfig.enabled || !assetConfig.app || !assetConfig.auth) {
            throw new Error(getI18n('genericError'));
        }

        if (state.firebase.loadPromise) {
            return state.firebase.loadPromise;
        }

        state.firebase.loadPromise = (async function () {
            await loadExternalScript(assetConfig.app);
            await loadExternalScript(assetConfig.auth);

            if (!window.firebase || !window.firebase.auth) {
                throw new Error(getI18n('genericError'));
            }

            state.firebase.assetsReady = true;
        })();

        try {
            await state.firebase.loadPromise;
        } finally {
            state.firebase.loadPromise = null;
        }
    }

    function loadExternalScript(src) {
        return new Promise(function (resolve, reject) {
            if (!src) {
                resolve();
                return;
            }

            const existing = Array.from(document.getElementsByTagName('script')).find(function (node) {
                return node.src === src;
            });

            if (existing) {
                const scriptIsReady = existing.dataset.bornadoLoaded === '1'
                    || existing.readyState === 'complete'
                    || existing.readyState === 'loaded'
                    || (src.indexOf('firebase-app') > -1 && window.firebase)
                    || (src.indexOf('firebase-auth') > -1 && window.firebase && window.firebase.auth);

                if (scriptIsReady) {
                    existing.dataset.bornadoLoaded = '1';
                    resolve();
                    return;
                }

                existing.addEventListener('load', function handleLoad() {
                    existing.dataset.bornadoLoaded = '1';
                    resolve();
                }, { once: true });
                existing.addEventListener('error', function () {
                    reject(new Error(getI18n('networkError')));
                }, { once: true });
                return;
            }

            const script = document.createElement('script');
            script.src = src;
            script.async = true;
            script.dataset.bornadoAuthAsset = '1';
            script.addEventListener('load', function () {
                script.dataset.bornadoLoaded = '1';
                resolve();
            }, { once: true });
            script.addEventListener('error', function () {
                reject(new Error(getI18n('networkError')));
            }, { once: true });
            document.head.appendChild(script);
        });
    }

    async function ensureFirebase() {
        await ensureFirebaseAssets();

        if (!window.firebase || !window.firebase.auth) {
            throw new Error(getI18n('genericError'));
        }

        if (!state.firebase.appReady) {
            const firebaseOptions = {
                apiKey: config.firebase && config.firebase.apiKey ? config.firebase.apiKey : '',
                projectId: config.firebase && config.firebase.projectId ? config.firebase.projectId : '',
                messagingSenderId: config.firebase && config.firebase.messagingSenderId ? config.firebase.messagingSenderId : '',
                appId: config.firebase && config.firebase.appId ? config.firebase.appId : ''
            };

            if (!window.firebase.apps || !window.firebase.apps.length) {
                window.firebase.initializeApp(firebaseOptions);
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
        const current = rootElement.querySelector('#bornado-auth-firebase-recaptcha');
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

    function getSiteOrigin() {
        const siteUrl = config.siteUrl || window.location.origin;
        return safeUrl(siteUrl).origin;
    }

    function isAllowedRedirectTarget(parsedUrl) {
        return Boolean(
            parsedUrl
            && /^(https?:)$/.test(parsedUrl.protocol)
            && parsedUrl.origin === getSiteOrigin()
        );
    }

    function normalizeSafeRedirectFallback(fallbackUrl) {
        const candidates = [
            fallbackUrl,
            config.afterLoginUrl,
            config.profileUrl,
            window.location.href,
            config.siteUrl,
            window.location.origin
        ];

        for (let index = 0; index < candidates.length; index += 1) {
            const candidate = candidates[index];
            if (!candidate) {
                continue;
            }

            const parsed = safeUrl(candidate);
            if (isAllowedRedirectTarget(parsed)) {
                return parsed;
            }
        }

        return safeUrl(window.location.origin);
    }

    function resolveSafeRedirectTarget(candidateUrl, fallbackUrl) {
        const fallback = normalizeSafeRedirectFallback(fallbackUrl);

        if (!candidateUrl) {
            return fallback.toString();
        }

        try {
            const parsedCandidate = new URL(candidateUrl, fallback.toString());
            if (!isAllowedRedirectTarget(parsedCandidate)) {
                return fallback.toString();
            }

            return parsedCandidate.toString();
        } catch (error) {
            return fallback.toString();
        }
    }

    function getRedirectQueryValue(parsedUrl) {
        if (!parsedUrl || !parsedUrl.searchParams) {
            return '';
        }

        return parsedUrl.searchParams.get('redirect_to') || parsedUrl.searchParams.get('u') || '';
    }

    function resolveRedirectUrl(fallbackHref) {
        if (!fallbackHref) {
            return getDefaultRedirectTarget();
        }

        const parsed = safeUrl(fallbackHref);
        return resolveSafeRedirectTarget(getRedirectQueryValue(parsed), getDefaultRedirectTarget());
    }

    function getDefaultRedirectTarget() {
        return normalizeSafeRedirectFallback(config.afterLoginUrl || config.profileUrl || window.location.href).toString();
    }

    function decorateRedirectTarget(targetUrl) {
        const decorated = safeUrl(resolveSafeRedirectTarget(targetUrl, getDefaultRedirectTarget()));

        // Force the first post-login visit to bypass stale guest page caches.
        if (config.postAdUrl && matchUrlPath(decorated.toString(), config.postAdUrl)) {
            decorated.searchParams.set('bornado_auth', String(Date.now()));
        }

        return decorated.toString();
    }

    function redirectAfterSuccess(flowType) {
        const target = flowType === 'register'
            ? (config.registerRedirectUrl || config.profileUrl || state.redirectUrl || window.location.href)
            : (state.redirectUrl || config.afterLoginUrl || config.profileUrl || window.location.href);

        if (bootstrapModal) {
            bootstrapModal.hide();
        } else if (!isInline) {
            rootElement.classList.remove('show');
            rootElement.style.display = 'none';
        }

        window.setTimeout(function () {
            window.location.href = decorateRedirectTarget(target);
        }, 160);
    }

    function postAjax(data, expectJson) {
        const wantsJson = expectJson !== false;

        return new Promise(function (resolve, reject) {
            $.ajax({
                url: config.ajaxUrl,
                method: 'POST',
                data: data,
                dataType: wantsJson ? 'json' : 'text'
            }).done(function (response) {
                if (wantsJson && response && response.success === false) {
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

        return getI18n('genericError');
    }

    function extractServerErrorCode(error) {
        if (!error) {
            return '';
        }

        if (typeof error.code === 'string' && error.code.trim()) {
            return error.code.trim();
        }

        if (error.data && typeof error.data.code === 'string' && error.data.code.trim()) {
            return error.data.code.trim();
        }

        return '';
    }

    function shouldFallbackToDefaultLogin(error) {
        const code = extractServerErrorCode(error);

        return code === 'expired_link' || code === 'invalid_token' || code === 'invalid_signature';
    }

    function isValidPhone(value) {
        return /^\+\d{8,16}$/.test(String(value || '').trim());
    }

    function getI18n(key) {
        if (config.i18n && Object.prototype.hasOwnProperty.call(config.i18n, key)) {
            return config.i18n[key];
        }

        return key;
    }

    function formatI18n(template, value) {
        const phoneMarkup = wrapInlinePhoneForDisplay(value);

        return String(template || '').replace('%s', phoneMarkup);
    }

    function wrapInlinePhoneForDisplay(value) {
        const phone = String(value || '').trim();

        if (!phone) {
            return '';
        }

        if (typeof window.bornadoWrapInlinePhone === 'function') {
            return window.bornadoWrapInlinePhone(phone);
        }

        return '<bdi dir="ltr" class="bornado-inline-phone">' + escapeHtml(phone) + '</bdi>';
    }

    function translateServerMessage(message) {
        const rawMessage = String(message || '').trim();
        if (!rawMessage) {
            return getI18n('genericError');
        }

        const exactMap = {
            INVALID_ID_TOKEN: 'اعتبار نشست تایید نامعتبر است. دوباره کد بگیر.',
            TOKEN_EXPIRED: 'زمان تایید به پایان رسیده است. دوباره کد بگیر.',
            USER_DISABLED: 'این حساب غیرفعال شده است.',
            'Incorrect password.': 'رمز عبور صحیح نیست.',
            'Phone number mismatch.': 'شماره تاییدشده با شماره درخواستی یکسان نیست.'
        };

        if (exactMap[rawMessage]) {
            return exactMap[rawMessage];
        }

        const containsMap = [
            ['incorrect password', 'رمز عبور صحیح نیست.'],
            ['password', 'رمز عبور صحیح نیست.'],
            ['phone number', 'شماره موبایل معتبر نیست یا قبلا ثبت شده است.'],
            ['expired', 'زمان این درخواست به پایان رسیده است. دوباره تلاش کن.'],
            ['security', 'اعتبار امنیتی درخواست نامعتبر است. صفحه را تازه‌سازی کن و دوباره تلاش کن.'],
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

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }
}(jQuery));
