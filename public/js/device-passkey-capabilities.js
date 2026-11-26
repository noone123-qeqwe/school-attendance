(function () {
    'use strict';

    var BROWSER_PASSKEYS_STORAGE_KEY = 'attendance_browser_passkeys';

    var STATES = Object.freeze({
        CHECKING: 'checking',
        READY: 'ready',
        AWAITING_DEVICE: 'awaiting_device',
        VERIFYING_SERVER: 'verifying_server',
        REGISTRATION_SUCCESS: 'registration_success',
        VERIFICATION_SUCCESS: 'verification_success',
        FAILED: 'failed',
        CANCELLED: 'cancelled',
        UNSUPPORTED: 'unsupported',
        NO_CREDENTIAL: 'no_credential',
        NETWORK_ERROR: 'network_error',
        DUPLICATE: 'duplicate',
        EXPIRED: 'expired'
    });

    function normalizePasskeyId(id) {
        return String(id || '').replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/, '');
    }

    function decodeBase64Url(value) {
        var normalized = String(value || '').replace(/-/g, '+').replace(/_/g, '/');
        var pad = normalized.length % 4;
        if (pad) normalized += '='.repeat(4 - pad);
        var binary = atob(normalized);
        return Uint8Array.from(binary, function (char) { return char.charCodeAt(0); });
    }

    function encodeBase64Url(value) {
        if (typeof value === 'string') return normalizePasskeyId(value);
        var bytes = value instanceof Uint8Array ? value : new Uint8Array(value);
        var binary = '';
        for (var i = 0; i < bytes.length; i++) {
            binary += String.fromCharCode(bytes[i]);
        }
        return normalizePasskeyId(btoa(binary));
    }

    function hasAndroidPasskeyBridge() {
        var bridge = window.AttendancePasskey;
        return Boolean(
            window.isSecureContext
            && bridge
            && typeof bridge.postMessage === 'function'
            && typeof bridge.addEventListener === 'function'
        );
    }

    function detectPlatform() {
        var ua = String((typeof navigator !== 'undefined' && navigator.userAgent) || '');
        var lower = ua.toLowerCase();
        var nativeApp = Boolean(window.AndroidDeviceBridge?.isNativeApp?.());

        var os = 'unknown';
        if (nativeApp || lower.indexOf('android') !== -1) os = 'android';
        else if (lower.indexOf('iphone') !== -1 || lower.indexOf('ipad') !== -1 || lower.indexOf('ipod') !== -1) os = 'ios';
        else if (lower.indexOf('macintosh') !== -1 || lower.indexOf('mac os x') !== -1) os = 'macos';
        else if (lower.indexOf('windows') !== -1) os = 'windows';
        else if (lower.indexOf('cros') !== -1) os = 'chromeos';
        else if (lower.indexOf('linux') !== -1) os = 'linux';

        var browser = 'unknown';
        if (nativeApp) browser = 'android-app';
        else if (lower.indexOf('edg/') !== -1 || lower.indexOf('edga/') !== -1 || lower.indexOf('edgios/') !== -1) browser = 'edge';
        else if (lower.indexOf('firefox') !== -1 || lower.indexOf('fxios') !== -1) browser = 'firefox';
        else if (lower.indexOf('chrome') !== -1 || lower.indexOf('crios') !== -1) browser = 'chrome';
        else if (lower.indexOf('safari') !== -1) browser = 'safari';

        var osLabelMap = {
            android: nativeApp ? 'Android App' : 'Android Device',
            ios: lower.indexOf('ipad') !== -1 ? 'iPad' : 'iPhone',
            macos: 'Mac',
            windows: 'Windows PC',
            chromeos: 'Chromebook',
            linux: 'Linux Device',
            unknown: 'This Device'
        };
        var browserLabelMap = {
            'android-app': 'Smart Attendance App',
            edge: 'Edge',
            firefox: 'Firefox',
            chrome: 'Chrome',
            safari: 'Safari',
            unknown: 'Browser'
        };

        var defaultDeviceName = nativeApp
            ? 'Android App'
            : (browser !== 'unknown' && os !== 'unknown'
                ? browserLabelMap[browser] + ' on ' + osLabelMap[os]
                : osLabelMap[os]);

        return {
            os: os,
            browser: browser,
            nativeApp: nativeApp,
            osLabel: osLabelMap[os],
            browserLabel: browserLabelMap[browser],
            defaultDeviceName: defaultDeviceName
        };
    }

    function requestAndroidPasskey(type, publicKeyOptions, signal) {
        if (!hasAndroidPasskeyBridge()) {
            return null;
        }
        var bridge = window.AttendancePasskey;

        return new Promise(function (resolve, reject) {
            if (signal && signal.aborted) {
                reject(new DOMException('Passkey request aborted.', 'AbortError'));
                return;
            }

            var id = 'apk_' + type + '_' + Date.now() + '_' + Math.random().toString(36).slice(2, 10);
            var timeoutMs = Math.min(Math.max(Number(publicKeyOptions && publicKeyOptions.timeout) || 60000, 15000), 120000) + 5000;
            var timer = setTimeout(function () {
                cleanup();
                try { bridge.postMessage(JSON.stringify({ id: id, type: 'cancel' })); } catch (_) {}
                reject(new DOMException('Passkey request timed out.', 'TimeoutError'));
            }, timeoutMs);

            function cleanup() {
                clearTimeout(timer);
                bridge.removeEventListener('message', onMessage);
                if (signal) signal.removeEventListener('abort', onAbort);
            }

            function onAbort() {
                cleanup();
                try {
                    bridge.postMessage(JSON.stringify({ id: id, type: 'cancel' }));
                } catch (_) {
                    // Ignore bridge teardown errors.
                }
                reject(new DOMException('Passkey request aborted.', 'AbortError'));
            }

            function onMessage(event) {
                var payload;
                try {
                    payload = typeof event.data === 'string' ? JSON.parse(event.data) : event.data;
                } catch (_) {
                    return;
                }
                if (!payload || payload.id !== id) return;
                cleanup();

                var credential = payload.credential || payload.assertion;
                if (payload.error || !credential) {
                    var errName = payload.error || 'UnknownError';
                    reject(new DOMException('Android biometric request failed.', errName));
                    return;
                }
                resolve(credential);
            }

            bridge.addEventListener('message', onMessage);
            if (signal) signal.addEventListener('abort', onAbort, { once: true });

            try {
                bridge.postMessage(JSON.stringify({
                    id: id,
                    type: type,
                    publicKey: publicKeyOptions
                }));
            } catch (err) {
                cleanup();
                reject(err);
            }
        });
    }

    function getRememberedBrowserPasskeys() {
        try {
            var raw = window.localStorage && window.localStorage.getItem(BROWSER_PASSKEYS_STORAGE_KEY);
            if (!raw) return [];
            var parsed = JSON.parse(raw);
            return Array.isArray(parsed) ? parsed.filter(Boolean) : [];
        } catch (_) {
            return [];
        }
    }

    function rememberBrowserPasskey(credentialId) {
        var clean = normalizePasskeyId(credentialId);
        if (!clean) return;
        try {
            if (!window.localStorage) return;
            var current = getRememberedBrowserPasskeys().filter(function (id) {
                return id !== clean;
            });
            current.unshift(clean);
            window.localStorage.setItem(BROWSER_PASSKEYS_STORAGE_KEY, JSON.stringify(current.slice(0, 20)));
        } catch (_) {
            // Ignore private-mode storage restrictions.
        }
    }

    function forgetBrowserPasskey(credentialId) {
        var clean = normalizePasskeyId(credentialId);
        if (!clean) return;
        try {
            if (!window.localStorage) return;
            var remaining = getRememberedBrowserPasskeys().filter(function (id) {
                return id !== clean;
            });
            window.localStorage.setItem(BROWSER_PASSKEYS_STORAGE_KEY, JSON.stringify(remaining));
        } catch (_) {
            // Ignore private-mode storage restrictions.
        }
    }

    function hasRememberedBrowserPasskey(allowCredentials) {
        var stored = getRememberedBrowserPasskeys();
        if (stored.length === 0) return false;
        if (!Array.isArray(allowCredentials) || allowCredentials.length === 0) return true;
        return allowCredentials.some(function (cred) {
            return cred && cred.id && stored.indexOf(normalizePasskeyId(cred.id)) !== -1;
        });
    }

    function usesAndroid(operation, options) {
        options = options || {};
        if (!hasAndroidPasskeyBridge()) return false;
        var native = window.AndroidDeviceBridge;
        if (operation === 'create') {
            return !native?.isNativeApp?.() || typeof native.hasLocalPasskey === 'function';
        }
        var allowed = options.allowCredentials || [];
        if (typeof navigator !== 'undefined' && typeof navigator.credentials?.get === 'function' && hasRememberedBrowserPasskey(allowed)) {
            return Boolean(native?.matchesLocalPasskey?.(JSON.stringify(allowed)));
        }
        return true;
    }

    function needsRegistrationChoice(options) {
        var allowed = (options && options.allowCredentials) || [];
        if (hasRememberedBrowserPasskey(allowed) && typeof navigator !== 'undefined' && typeof navigator.credentials?.get === 'function') {
            return false;
        }
        if (!usesAndroid('get', options)) return true;
        return window.AndroidDeviceBridge?.matchesLocalPasskey?.(JSON.stringify(allowed)) === false;
    }

    function needsTap(operation, options, allowSavedPasskey) {
        if (operation === 'get' && !allowSavedPasskey && needsRegistrationChoice(options)) return false;
        return !usesAndroid(operation, options) && typeof navigator !== 'undefined' && navigator.userActivation?.isActive === false;
    }

    function localCredentials(descriptors, forBrowser) {
        return (descriptors || []).map(function (item) {
            return {
                type: 'public-key',
                id: forBrowser ? decodeBase64Url(item.id) : item.id,
                transports: ['internal']
            };
        });
    }

    async function registerCredential(options, signal) {
        var android = usesAndroid('create');
        var publicKey = {
            rp: options.rp,
            user: Object.assign({}, options.user, {
                id: android ? options.user.id : decodeBase64Url(options.user.id)
            }),
            challenge: android ? options.challenge : decodeBase64Url(options.challenge),
            pubKeyCredParams: options.pubKeyCredParams || [
                { type: 'public-key', alg: -7 },
                { type: 'public-key', alg: -257 }
            ],
            excludeCredentials: localCredentials(options.excludeCredentials, !android),
            attestation: 'none',
            hints: ['client-device'],
            timeout: options.timeout || 60000,
            authenticatorSelection: {
                authenticatorAttachment: 'platform',
                userVerification: 'required',
                residentKey: android ? 'required' : 'preferred',
                requireResidentKey: android
            }
        };

        var credential;
        if (android) {
            if (publicKey.excludeCredentials.length
                && window.AndroidDeviceBridge?.matchesLocalPasskey?.(JSON.stringify(publicKey.excludeCredentials))) {
                throw new DOMException('This device is already registered.', 'InvalidStateError');
            }
            credential = await requestAndroidPasskey('create', publicKey, signal);
        } else {
            if (typeof navigator === 'undefined' || typeof navigator.credentials?.create !== 'function') {
                throw new DOMException('WebAuthn registration is not supported in this browser.', 'NotSupportedError');
            }
            try {
                credential = await navigator.credentials.create({ publicKey: publicKey, signal: signal });
            } catch (error) {
                if (signal?.aborted || !['NotSupportedError', 'ConstraintError'].includes(error.name)) {
                    throw error;
                }
                // Fallback for older platform authenticators that reject residentKey: 'preferred'.
                publicKey.authenticatorSelection.residentKey = 'discouraged';
                publicKey.authenticatorSelection.requireResidentKey = false;
                credential = await navigator.credentials.create({ publicKey: publicKey, signal: signal });
            }
        }

        if (!credential) {
            throw new DOMException('Registration was cancelled.', 'NotAllowedError');
        }

        var id = encodeBase64Url(credential.rawId || credential.id);
        var response = credential.response || {};
        var transports = response.transports || (typeof response.getTransports === 'function' ? response.getTransports() : []) || [];
        var attachment = credential.authenticatorAttachment || 'platform';

        if (!android) {
            rememberBrowserPasskey(id);
        }

        return {
            credential_id: id,
            transports: transports,
            authenticatorAttachment: attachment,
            credential: {
                id: id,
                rawId: id,
                type: 'public-key',
                authenticatorAttachment: attachment,
                transports: transports,
                response: {
                    attestationObject: encodeBase64Url(response.attestationObject),
                    clientDataJSON: encodeBase64Url(response.clientDataJSON)
                }
            }
        };
    }

    async function verifyCredential(options, signal, allowSavedPasskey) {
        if (!options || !Array.isArray(options.allowCredentials) || options.allowCredentials.length === 0) {
            throw new DOMException('No registered credential was found for this account.', 'NotFoundError');
        }

        var publicKey = {
            rpId: options.rpId,
            challenge: options.challenge,
            allowCredentials: localCredentials(options.allowCredentials, false),
            hints: ['client-device'],
            userVerification: 'required',
            timeout: options.timeout || 60000
        };

        if (!allowSavedPasskey && needsRegistrationChoice(options)) {
            var error = new DOMException('No matching registration was recognized on this device.', 'NotFoundError');
            error.canCheckSavedPasskey = true;
            throw error;
        }

        var assertion;
        if (usesAndroid('get', options)) {
            try {
                assertion = await requestAndroidPasskey('get', publicKey, signal);
            } catch (err) {
                if (err.name !== 'NotSupportedError'
                    || window.AndroidDeviceBridge?.isNativeApp?.()
                    || typeof navigator === 'undefined'
                    || !navigator.credentials?.get) {
                    throw err;
                }
            }
        }

        if (!assertion) {
            if (typeof navigator === 'undefined' || typeof navigator.credentials?.get !== 'function') {
                throw new DOMException('WebAuthn verification is not supported in this browser.', 'NotSupportedError');
            }
            publicKey.challenge = decodeBase64Url(options.challenge);
            publicKey.allowCredentials = localCredentials(options.allowCredentials, true);
            assertion = await navigator.credentials.get({ publicKey: publicKey, signal: signal });
            if (!assertion) {
                throw new DOMException('Verification was cancelled.', 'NotAllowedError');
            }
            rememberBrowserPasskey(encodeBase64Url(assertion.rawId || assertion.id));
        }

        var response = assertion.response || {};
        var assertionId = encodeBase64Url(assertion.rawId || assertion.id);

        return {
            id: assertionId,
            rawId: assertionId,
            type: 'public-key',
            response: {
                clientDataJSON: encodeBase64Url(response.clientDataJSON),
                authenticatorData: encodeBase64Url(response.authenticatorData),
                signature: encodeBase64Url(response.signature),
                userHandle: response.userHandle ? encodeBase64Url(response.userHandle) : null
            }
        };
    }

    function buildCapabilityDetail(base) {
        var platform = detectPlatform();
        var supportsFingerprintSpecificUi = Boolean(base.androidBridge === true);

        var authenticatorLabel = supportsFingerprintSpecificUi
            ? 'Native device biometric sensor'
            : 'Built-in platform authenticator (biometrics or device screen lock)';

        var methodLabel = supportsFingerprintSpecificUi
            ? 'Fingerprint / Biometrics'
            : 'Device Biometrics / Passkey';

        var disclaimer = supportsFingerprintSpecificUi
            ? 'Handled securely by Android’s native biometric prompt. Biometric data never leaves this device.'
            : 'Your operating system chooses the available unlock method on this device (such as fingerprint, facial recognition, or screen lock/PIN). Only a cryptographic public key is stored.';

        return Object.assign({}, base, {
            platform: platform,
            supportsFingerprintSpecificUi: supportsFingerprintSpecificUi,
            authenticatorLabel: authenticatorLabel,
            methodLabel: methodLabel,
            biometricType: supportsFingerprintSpecificUi ? 'fingerprint' : 'device_lock',
            defaultDeviceName: platform.defaultDeviceName,
            disclaimer: disclaimer
        });
    }

    async function check(operation) {
        var unavailable = function (reason) {
            return buildCapabilityDetail({
                available: false,
                platformAvailable: null,
                androidBridge: false,
                reason: reason
            });
        };

        if (!window.isSecureContext) {
            return unavailable('insecure');
        }

        var native = window.AndroidDeviceBridge;
        if (native?.isNativeApp?.()) {
            if (operation === 'create' && typeof native.hasLocalPasskey !== 'function' && !navigator.credentials?.create) {
                return unavailable('update-app');
            }
            if (hasAndroidPasskeyBridge() && native.isBiometricAvailable?.() === false) {
                return unavailable('sensor-unavailable');
            }
        }

        if (usesAndroid(operation) && native?.isBiometricAvailable?.() !== false) {
            return buildCapabilityDetail({
                available: true,
                platformAvailable: true,
                androidBridge: true,
                reason: null
            });
        }

        var credential = window.PublicKeyCredential;
        if (!credential || typeof navigator === 'undefined' || typeof navigator.credentials?.[operation] !== 'function') {
            return unavailable('unsupported');
        }

        var platformAvailable = null;
        if (typeof credential.isUserVerifyingPlatformAuthenticatorAvailable === 'function') {
            try {
                var result = await credential.isUserVerifyingPlatformAuthenticatorAvailable();
                if (typeof result === 'boolean') platformAvailable = result;
            } catch (_) {
                // Some browsers expose the probe without being able to run it.
            }
        }

        if (platformAvailable === null && typeof credential.getClientCapabilities === 'function') {
            try {
                var caps = await credential.getClientCapabilities();
                if (typeof caps?.userVerifyingPlatformAuthenticator === 'boolean') {
                    platformAvailable = caps.userVerifyingPlatformAuthenticator;
                }
            } catch (_) {
                // An inconclusive probe still permits a platform-only request.
            }
        }

        var available = platformAvailable !== false;
        return buildCapabilityDetail({
            available: available,
            platformAvailable: platformAvailable,
            androidBridge: false,
            reason: available ? null : 'unavailable'
        });
    }

    function message(result, operation) {
        var settingUp = operation === 'create';
        var messages = {
            insecure: 'Device biometric authentication requires a secure (HTTPS) connection.',
            unsupported: 'This browser does not support WebAuthn platform authenticators.',
            'update-app': 'Update the Smart Attendance Android app to register biometric credentials on this device.',
            'sensor-unavailable': 'No active biometric sensor or screen lock was detected on this Android device. Enroll a biometric or screen lock in Android Settings, then try again.',
            unavailable: settingUp
                ? 'No compatible platform authenticator was detected on this device. Enable biometric unlock or a device screen lock/PIN in your operating system settings, or use another sign-in method.'
                : 'No compatible platform authenticator is available in this browser.',
            unknown: 'This browser could not confirm platform authenticator availability.'
        };
        var explanation = messages[result && result.reason] || messages.unknown;
        return settingUp ? explanation : explanation + ' Sign in with your password instead.';
    }

    function classifyError(error, context) {
        context = context || 'verify';
        var isRegister = context === 'register';
        var name = (error && error.name) || '';
        var code = (error && error.code) || '';
        var status = Number((error && error.status) || 0);
        var rawMsg = (error && error.message) || '';

        if (typeof navigator !== 'undefined' && navigator.onLine === false) {
            return {
                state: STATES.NETWORK_ERROR,
                title: 'You appear to be offline',
                message: 'Check your internet connection and try again.',
                retryable: true,
                suggestPassword: !isRegister
            };
        }

        if (status === 419 || name === 'SessionExpiredError') {
            return {
                state: STATES.EXPIRED,
                title: 'Session expired',
                message: 'Your security session expired. Refresh this page and try again.',
                retryable: true,
                suggestPassword: !isRegister
            };
        }

        if (status === 429 || code === 'ACCOUNT_LOCKED' || code === 'RATE_LIMITED' || name === 'RateLimitError') {
            return {
                state: STATES.FAILED,
                title: 'Too many attempts',
                message: rawMsg || 'Too many authentication attempts. Please wait a moment before trying again.',
                retryable: false,
                suggestPassword: !isRegister
            };
        }

        if (code === 'CONSENT_REQUIRED') {
            return {
                state: STATES.FAILED,
                title: 'Consent required',
                message: rawMsg || 'Please confirm your consent before registering this device.',
                retryable: true,
                suggestPassword: false
            };
        }

        if (status === 409 || name === 'InvalidStateError' || code === 'CREDENTIAL_ALREADY_REGISTERED') {
            return {
                state: STATES.DUPLICATE,
                title: 'Device already registered',
                message: isRegister
                    ? 'A credential from this device is already linked to your account. Revoke the existing entry below if you want to re-register.'
                    : 'This credential state is invalid for sign-in. Try re-registering this device in Settings.',
                retryable: false,
                suggestPassword: !isRegister
            };
        }

        if (code === 'PASSKEY_CHALLENGE_EXPIRED' || code === 'CHALLENGE_EXPIRED' || name === 'TimeoutError') {
            return {
                state: STATES.EXPIRED,
                title: isRegister ? 'Registration timed out' : 'Verification expired',
                message: rawMsg || 'The authentication challenge expired before your device completed verification. Start again when ready.',
                retryable: true,
                suggestPassword: !isRegister
            };
        }

        if (code === 'CREDENTIAL_REVOKED') {
            return {
                state: STATES.NO_CREDENTIAL,
                title: 'Credential revoked',
                message: rawMsg || 'This device credential was revoked. Register this device again or sign in with your password.',
                retryable: false,
                suggestPassword: !isRegister,
                suggestSetup: true
            };
        }

        if (name === 'AbortError') {
            return {
                state: STATES.CANCELLED,
                title: isRegister ? 'Registration cancelled' : 'Verification cancelled',
                message: 'You cancelled the device authentication prompt. You can try again at any time.',
                retryable: true,
                suggestPassword: !isRegister
            };
        }

        if (name === 'NotAllowedError') {
            return {
                state: STATES.CANCELLED,
                title: isRegister ? 'Device prompt closed or declined' : 'Verification cancelled or declined',
                message: isRegister
                    ? 'The device authentication prompt was dismissed, declined, or timed out before registration finished.'
                    : 'The device authentication prompt was dismissed, declined, or timed out before verification finished.',
                retryable: true,
                suggestPassword: !isRegister
            };
        }

        if (name === 'NotFoundError' || code === 'BIOMETRIC_UNAVAILABLE') {
            return {
                state: STATES.NO_CREDENTIAL,
                title: 'No matching credential on this device',
                message: rawMsg || 'No matching registered credential was found for this account on this device.',
                retryable: false,
                suggestPassword: !isRegister,
                suggestSetup: true
            };
        }

        if (name === 'NotSupportedError' || name === 'NotReadableError' || name === 'SecurityError') {
            return {
                state: STATES.UNSUPPORTED,
                title: 'Device authentication unavailable',
                message: name === 'SecurityError'
                    ? 'This site origin or HTTPS configuration does not permit WebAuthn authentication here.'
                    : 'Your browser or device could not start platform authentication. Verify your OS biometric/screen-lock settings or use an alternative sign-in method.',
                retryable: false,
                suggestPassword: !isRegister
            };
        }

        if (status >= 500 || error instanceof TypeError || name === 'TypeError') {
            return {
                state: STATES.NETWORK_ERROR,
                title: 'Connection or server error',
                message: rawMsg || 'Could not reach the authentication server. Check your connection and try again.',
                retryable: true,
                suggestPassword: !isRegister
            };
        }

        return {
            state: STATES.FAILED,
            title: isRegister ? 'Registration failed' : 'Verification failed',
            message: rawMsg || (isRegister
                ? 'Device registration could not be verified by the server. Please try again.'
                : 'Device verification could not be completed. Try again or use your password.'),
            retryable: true,
            suggestPassword: !isRegister
        };
    }

    function confirmOnClick(button, request, signal) {
        return new Promise(function (resolve, reject) {
            if (signal?.aborted) return resolve(null);
            var cancel = function () {
                button.removeEventListener('click', confirm, true);
                resolve(null);
            };
            var confirm = function (event) {
                event.preventDefault();
                event.stopImmediatePropagation();
                signal?.removeEventListener('abort', cancel);
                button.disabled = true;
                try {
                    Promise.resolve(request()).then(resolve, reject);
                } catch (error) {
                    reject(error);
                }
            };
            button.addEventListener('click', confirm, { once: true, capture: true });
            signal?.addEventListener('abort', cancel, { once: true });
            button.disabled = false;
            button.focus({ preventScroll: true });
        });
    }

    window.DevicePasskeys = {
        STATES: STATES,
        detectPlatform: detectPlatform,
        check: check,
        message: message,
        classifyError: classifyError,
        confirmOnClick: confirmOnClick,
        needsTap: needsTap,
        registerCredential: registerCredential,
        verifyCredential: verifyCredential,
        registerFingerprint: registerCredential,
        verifyFingerprint: verifyCredential,
        rememberBrowserPasskey: rememberBrowserPasskey,
        forgetBrowserPasskey: forgetBrowserPasskey,
        normalizePasskeyId: normalizePasskeyId
    };
})();
