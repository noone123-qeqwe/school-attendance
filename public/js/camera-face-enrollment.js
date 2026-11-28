(function () {
    'use strict';

    function initialize() {
        const root = document.getElementById('cameraFaceEnrollment');
        const start = document.getElementById('cameraFaceStartBtn');
        if (!root || !start) return;

        const video = document.getElementById('cameraFaceVideo');
        const canvas = document.getElementById('cameraFaceCanvas');
        const wrap = document.getElementById('cameraFacePreviewWrap');
        const stage = document.getElementById('cameraFaceStage');
        const save = document.getElementById('cameraFaceSaveBtn');
        const retake = document.getElementById('cameraFaceRetakeBtn');
        const cancel = document.getElementById('cameraFaceCancelBtn');
        const status = document.getElementById('cameraFaceStatus');
        const instruction = document.getElementById('cameraFacePoseInstruction');
        const guide = document.getElementById('cameraFaceGuide');
        const guideLabel = guide.querySelector('span');
        const done = document.getElementById('cameraFaceDone');
        const consentCheckbox = document.getElementById('cameraFaceConsentCheckbox');
        const lighting = window.CameraLighting?.mount(document.getElementById('cameraFaceLight'));
        const poses = [
            ['front', 'Look straight at the camera. Keep your face centered and both eyes visible.', 'Look straight ahead'],
            ['left', 'Turn your head slightly to your left, then hold still.', 'Turn slightly left'],
            ['right', 'Turn your head slightly to your right, then hold still.', 'Turn slightly right'],
        ];

        let stream = null;
        let detector = null;
        let guidance = null;
        let context = null;
        let requestId = 0;
        let scanTimer = null;
        let scanInFlight = false;
        let previousVideoTime = -1;
        let views = {};
        let saving = false;
        let stableCount = 0;
        let stableSample = null;
        let front = null;
        let lastStatus = '';
        let captureFeedbackTimer = null;

        function syncFaceConsentState() {
            if (!consentCheckbox) return;
            const locked = !consentCheckbox.checked;
            start.disabled = locked;
            if (locked && !lastStatus) {
                status.textContent = 'Confirm biometric consent to start your face scan.';
                status.style.color = 'var(--text-muted)';
            } else if (!locked && status.textContent === 'Confirm biometric consent to start your face scan.') {
                status.textContent = '';
            }
        }
        if (consentCheckbox) {
            consentCheckbox.addEventListener('change', syncFaceConsentState);
        }

        function setStatus(message, color = 'var(--text-muted)') {
            if (lastStatus === message) return;
            lastStatus = message;
            status.textContent = message;
            status.style.color = color;
            status.classList.remove('face-status-pop');
            void status.offsetWidth;
            status.classList.add('face-status-pop');
        }
        function visible(element, show) {
            element.hidden = !show;
            element.style.display = show ? '' : 'none';
        }
        function updateGuide(state, label) {
            guide.dataset.state = state;
            if (guideLabel.textContent !== label) {
                guideLabel.textContent = label;
                guideLabel.classList.remove('face-guide-label-pop');
                void guideLabel.offsetWidth;
                guideLabel.classList.add('face-guide-label-pop');
            }
        }
        function resetStability() {
            stableCount = 0;
            stableSample = null;
            guide.style.setProperty('--face-stability-progress', '0');
        }
        function showCaptureFeedback() {
            if (captureFeedbackTimer !== null) window.clearTimeout(captureFeedbackTimer);
            wrap.classList.remove('face-capture-feedback');
            void wrap.offsetWidth;
            wrap.classList.add('face-capture-feedback');
            captureFeedbackTimer = window.setTimeout(() => {
                wrap.classList.remove('face-capture-feedback');
                captureFeedbackTimer = null;
            }, 400);
        }
        function stopCamera() {
            if (scanTimer !== null) window.clearTimeout(scanTimer);
            scanTimer = null;
            scanInFlight = false;
            previousVideoTime = -1;
            resetStability();
            lighting?.stop();
            if (stream) stream.getTracks().forEach(track => track.stop());
            stream = null;
            video.srcObject = null;
            try { detector?.close(); } catch (_) { /* The detector may already have failed. */ }
            detector = null;
            context = null;
        }
        function progress() {
            const index = Object.keys(views).length;
            root.querySelectorAll('[data-face-pose]').forEach((item, position) => {
                item.dataset.complete = position < index ? 'true' : 'false';
                if (position === index) item.setAttribute('aria-current', 'step');
                else item.removeAttribute('aria-current');
            });
            instruction.textContent = poses[index]?.[1] || 'All three views are captured. Register your face or retake the sequence.';
            updateGuide(poses[index] ? 'searching' : 'complete', poses[index]?.[2] || 'Scan complete');
        }
        function reset() {
            stopCamera();
            if (captureFeedbackTimer !== null) window.clearTimeout(captureFeedbackTimer);
            captureFeedbackTimer = null;
            views = {};
            saving = false;
            front = null;
            lastStatus = '';
            stage.classList.remove('is-active');
            wrap.style.display = 'none';
            wrap.classList.remove('face-camera-enter', 'face-capture-feedback');
            video.style.display = 'block';
            canvas.style.display = 'none';
            canvas.width = 0;
            [retake, save, cancel].forEach(button => { visible(button, false); button.disabled = false; });
            save.removeAttribute('aria-busy');
            save.textContent = 'Register face';
            start.disabled = Boolean(consentCheckbox && !consentCheckbox.checked);
            start.removeAttribute('aria-busy');
            start.innerHTML = '<i class="bi bi-camera" aria-hidden="true"></i> Start face scan';
            visible(start, true);
            if (done) visible(done, false);
            progress();
            syncFaceConsentState();
        }
        function failScan(attempt, message) {
            if (attempt !== requestId) return;
            requestId++;
            reset();
            setStatus(message, '#f87171');
            start.focus({ preventScroll: true });
        }
        function queueScan(delay = 240) {
            if (!detector || scanInFlight || scanTimer !== null || document.hidden) return;
            scanTimer = window.setTimeout(scanFrame, delay);
        }
        function isStable(sample) {
            if (!stableSample || Math.hypot(sample.cx - stableSample.cx, sample.cy - stableSample.cy) > 0.035
                || Math.abs(sample.yaw - stableSample.yaw) > 0.05 || Math.abs(sample.pitch - stableSample.pitch) > 0.05) {
                stableSample = sample;
                stableCount = 0;
            }
            stableCount++;
            guide.style.setProperty('--face-stability-progress', String(stableCount / 4));
            return stableCount >= 4;
        }
        async function captureView(index, sample, attempt) {
            resetStability();
            updateGuide('capturing', 'Capturing view ' + (index + 1) + ' of 3');
            // Encode the same frame that passed the face and pose checks.
            const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.88));
            if (attempt !== requestId || document.hidden) return;
            if (!blob) {
                setStatus('This frame could not be captured. Hold still to try again.', '#f87171');
                return;
            }

            showCaptureFeedback();
            if (index === 0) front = sample;
            views[poses[index][0]] = blob;
            progress();
            if (poses[index + 1]) {
                setStatus('View ' + (index + 1) + ' captured. ' + poses[index + 1][1]);
                return;
            }

            stopCamera();
            video.style.display = 'none';
            canvas.style.cssText = 'display:block;width:100%;height:100%;object-fit:cover;transform:scaleX(-1);';
            visible(retake, true);
            visible(save, true);
            setStatus('Three views captured. Select Register face to check and save them.');
            save.focus({ preventScroll: true });
        }
        async function scanFrame() {
            scanTimer = null;
            if (!detector || scanInFlight || document.hidden) return;
            scanInFlight = true;
            const attempt = requestId;
            try {
                if (video.readyState < 2 || video.currentTime === previousVideoTime) return;
                previousVideoTime = video.currentTime;
                guidance.drawPreview(video, canvas, context);
                const { detections } = detector.detectForVideo(canvas, performance.now());
                const index = Object.keys(views).length;
                const [pose, poseInstruction, poseLabel] = poses[index];
                const sample = detections.length === 1 ? guidance.inspectFace(canvas, context, detections[0]) : {
                    message: detections.length ? 'Only one person should be in the frame.' : 'Center your face in the oval.',
                };
                if (sample.message || !guidance.matchesPose(sample, pose, front)) {
                    resetStability();
                    updateGuide('searching', sample.message ? 'Adjust your position' : poseLabel);
                    setStatus(sample.message || poseInstruction);
                    return;
                }
                updateGuide('ready', 'Hold still · scanning');
                setStatus('Face position detected. Hold still while this view is captured automatically.');
                if (isStable(sample)) await captureView(index, sample, attempt);
            } catch (_) {
                failScan(attempt, 'Automatic face capture stopped. Start a new scan to try again.');
            } finally {
                if (attempt === requestId) {
                    scanInFlight = false;
                    queueScan();
                }
            }
        }

        start.addEventListener('click', async () => {
            const consentBox = document.getElementById('cameraFaceConsentCheckbox');
            if (consentBox && !consentBox.checked) {
                setStatus('Please check the biometric attendance consent box above before starting your face scan.', '#fbbf24');
                consentBox.focus({ preventScroll: true });
                return;
            }
            const attempt = ++requestId;
            reset();
            if (!navigator.mediaDevices?.getUserMedia) {
                setStatus('Camera access needs a supported browser and a secure HTTPS connection.', '#f87171');
                return;
            }
            start.disabled = true;
            start.setAttribute('aria-busy', 'true');
            start.textContent = 'Opening camera…';
            setStatus('Requesting camera access…');
            try {
                const opened = await navigator.mediaDevices.getUserMedia({
                    audio: false,
                    video: { facingMode: { ideal: 'user' }, width: { ideal: 1280 }, height: { ideal: 960 } },
                });
                if (attempt !== requestId) {
                    opened.getTracks().forEach(track => track.stop());
                    return;
                }
                stream = opened;
                opened.getVideoTracks()[0]?.addEventListener('ended', () => {
                    if (stream === opened) failScan(attempt, 'The camera disconnected. Reconnect it and start a new scan.');
                }, { once: true });
                video.srcObject = opened;
                await video.play();
                if (attempt !== requestId) return;
                lighting?.start(opened);
                stage.classList.add('is-active');
                wrap.style.display = 'block';
                wrap.classList.add('face-camera-enter');
                visible(start, false);
                visible(cancel, true);
            } catch (error) {
                failScan(attempt, error?.name === 'NotAllowedError'
                    ? 'Camera permission was denied. Allow camera access and retry.'
                    : 'The camera could not be opened. Check that it is available and retry.');
                return;
            }

            setStatus('Loading automatic face capture…');
            instruction.focus({ preventScroll: true });
            try {
                const module = await import(root.dataset.guidanceUrl);
                if (attempt !== requestId) return;
                const canvasContext = canvas.getContext('2d', { alpha: false, willReadFrequently: true });
                if (!canvasContext) throw new Error('Camera capture is unavailable.');
                const loadedDetector = await module.createDetector();
                if (attempt !== requestId) {
                    loadedDetector.close();
                    return;
                }
                guidance = module;
                context = canvasContext;
                detector = loadedDetector;
                setStatus('Automatic scan is ready. Hold each position steady.');
                queueScan(100);
            } catch (_) {
                failScan(attempt, 'Automatic face capture could not load. Start a new scan to retry.');
            }
        });
        save.addEventListener('click', async () => {
            if (saving || !poses.every(([pose]) => views[pose])) return;
            const consentBox = document.getElementById('cameraFaceConsentCheckbox');
            if (consentBox && !consentBox.checked) {
                setStatus('Please confirm biometric attendance consent before saving your face profile.', '#fbbf24');
                consentBox.focus({ preventScroll: true });
                return;
            }
            const attempt = requestId;
            saving = true;
            [save, retake, cancel].forEach(button => { button.disabled = true; });
            save.setAttribute('aria-busy', 'true');
            save.textContent = 'Checking and saving…';
            setStatus('Checking all three face captures and saving securely…');
            const form = new FormData();
            poses.forEach(([pose]) => form.append('face_images[' + pose + ']', views[pose], pose + '.jpg'));
            if (!consentBox || consentBox.checked) form.append('consent_accepted', '1');
            try {
                const response = await fetch(root.dataset.enrollmentUrl, {
                    method: 'POST', credentials: 'same-origin', body: form,
                    headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '' },
                });
                const result = await response.json();
                if (attempt !== requestId) return;
                if (!response.ok || !result.success) throw new Error(result.message || 'The captures did not pass the face checks. Please retake them.');
                reset();
                setStatus(result.message || 'Your three face views were registered securely.', '#34d399');
                if (done) {
                    visible(start, false);
                    visible(done, true);
                    done.focus();
                }
            } catch (error) {
                if (attempt !== requestId) return;
                saving = false;
                [save, retake, cancel].forEach(button => { button.disabled = false; });
                save.removeAttribute('aria-busy');
                save.textContent = 'Register face';
                setStatus(error.message || 'Could not reach the server. Retry or retake the captures.', '#f87171');
                save.focus({ preventScroll: true });
            }
        });
        retake.addEventListener('click', () => { requestId++; reset(); start.click(); });
        cancel.addEventListener('click', () => {
            requestId++;
            reset();
            setStatus('Capture canceled.');
            start.focus({ preventScroll: true });
        });
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                if (scanTimer !== null) window.clearTimeout(scanTimer);
                scanTimer = null;
                resetStability();
            } else {
                queueScan(100);
            }
        });
        window.addEventListener('pagehide', () => {
            const captureInProgress = Boolean(stream) || start.disabled || Object.keys(views).length > 0;
            requestId++;
            reset();
            if (captureInProgress) setStatus('Capture closed. Open the camera to begin again.');
        });
        progress();
        syncFaceConsentState();
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
})();
