(function () {
    'use strict';

    async function check(operation) {
        const unavailable = reason => ({ available: false, platformAvailable: null, reason });
        if (!window.isSecureContext) return unavailable('insecure');

        const credential = window.PublicKeyCredential;
        if (!credential || typeof navigator.credentials?.[operation] !== 'function') {
            return unavailable('unsupported');
        }

        let platformAvailable = null;
        if (typeof credential.isUserVerifyingPlatformAuthenticatorAvailable === 'function') {
            try {
                const result = await credential.isUserVerifyingPlatformAuthenticatorAvailable();
                if (typeof result === 'boolean') platformAvailable = result;
            } catch (_) {
                // Some browsers expose the probe without being able to run it.
            }
        }

        let capabilities = {};
        if (platformAvailable !== true && (operation === 'get' || platformAvailable === null)
            && typeof credential.getClientCapabilities === 'function') {
            try {
                capabilities = await credential.getClientCapabilities() || {};
                if (platformAvailable === null && typeof capabilities.userVerifyingPlatformAuthenticator === 'boolean') {
                    platformAvailable = capabilities.userVerifyingPlatformAuthenticator;
                }
            } catch (_) {
                // Keep missing capability information distinct from a negative result.
            }
        }

        // A platform authenticator may use a screen lock and does not identify a sensor type.
        const alternatePasskey = operation === 'get'
            && (capabilities.passkeyPlatformAuthenticator === true || capabilities.hybridTransport === true);
        const available = platformAvailable === true || alternatePasskey;

        return {
            available,
            platformAvailable,
            reason: available ? null : (platformAvailable === false ? 'unavailable' : 'unknown')
        };
    }

    function message(result, operation) {
        const settingUp = operation === 'create';
        const messages = {
            insecure: 'Passkeys require a secure connection.',
            unsupported: 'This browser doesn’t support passkeys.',
            unavailable: settingUp
                ? 'This browser couldn’t find a device sign-in method for passkey setup. Check your device’s screen lock settings.'
                : 'No passkey option is available in this browser.',
            unknown: 'This browser couldn’t confirm passkey availability.'
        };
        const explanation = messages[result.reason] || messages.unknown;
        return settingUp ? explanation : explanation + ' Sign in with your password.';
    }

    function confirmOnClick(button, request, signal) {
        return new Promise((resolve, reject) => {
            if (signal?.aborted) return resolve(null);
            const cancel = () => {
                button.removeEventListener('click', confirm, true);
                resolve(null);
            };
            const confirm = event => {
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

    window.DevicePasskeys = { check, message, confirmOnClick };
})();
