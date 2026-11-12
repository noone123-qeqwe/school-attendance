(function () {
    'use strict';

    function initialize() {
        const root = document.getElementById('cameraFaceEnrollment');
        const start = document.getElementById('cameraFaceStartBtn');
        if (!root || !start) return;

        const video = document.getElementById('cameraFaceVideo');
        const canvas = document.getElementById('cameraFaceCanvas');
        const wrap = document.getElementById('cameraFacePreviewWrap');
        const capture = document.getElementById('cameraFaceCaptureBtn');
        const save = document.getElementById('cameraFaceSaveBtn');
        const retake = document.getElementById('cameraFaceRetakeBtn');
        const cancel = document.getElementById('cameraFaceCancelBtn');
        const status = document.getElementById('cameraFaceStatus');
        const instruction = document.getElementById('cameraFacePoseInstruction');
        const guide = document.getElementById('cameraFaceGuide');
        const guideLabel = guide.querySelector('span');
        const done = document.getElementById('cameraFaceDone');
        const lighting = window.CameraLighting?.mount(document.getElementById('cameraFaceLight'));
        const poses = [
            ['front', 'Look straight at the camera. Keep your face centered and both eyes visible.'],
            ['left', 'Turn your head slightly to your left, then hold still for the automatic scan.'],
            ['right', 'Turn slightly to your right—the opposite direction—then hold still for the automatic scan.'],
        ];

        let stream = null;
        let detector = null;
        let requestId = 0;
        let scanTimer = null;
        let scanning = false;
        let scanInFlight = false;
        let views = {};
        let saving = false;
        let stableCount = 0;
        let lastSample = null;
        let firstTurnSign = 0;
        let lastStatus = '';
        let captureFeedbackTimer = null;

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
            lastSample = null;
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
        function stopScanner() {
            scanning = false;
            if (scanTimer !== null) window.clearTimeout(scanTimer);
            scanTimer = null;
            scanInFlight = false;
            resetStability();
        }
        function stopCamera() {
            stopScanner();
            lighting?.stop();
            if (stream) stream.getTracks().forEach(track => track.stop());
            stream = null;
            detector = null;
            video.srcObject = null;
        }
        function progress() {
            const index = Object.keys(views).length;
            root.querySelectorAll('[data-face-pose]').forEach((item, position) => {
                item.dataset.complete = position < index ? 'true' : 'false';
                if (position === index) item.setAttribute('aria-current', 'step');
                else item.removeAttribute('aria-current');
            });
            instruction.textContent = poses[index]?.[1] || 'All three views are captured. Register them securely, or retake the sequence.';
            if (poses[index]) capture.textContent = 'Capture ' + poses[index][0] + ' view';
            updateGuide(poses[index] ? 'searching' : 'complete', index === 0 ? 'Center your face' : 'Follow the position');
        }
        function reset() {
            stopCamera();
            if (captureFeedbackTimer !== null) window.clearTimeout(captureFeedbackTimer);
            captureFeedbackTimer = null;
            views = {};
            saving = false;
            firstTurnSign = 0;
            lastStatus = '';
            wrap.style.display = 'none';
            wrap.classList.remove('face-camera-enter', 'face-capture-feedback');
            video.style.display = 'block';
            canvas.style.display = 'none';
            [capture, retake, save, cancel].forEach(button => { visible(button, false); button.disabled = false; });
            start.disabled = false;
            start.removeAttribute('aria-busy');
            start.innerHTML = '<i class="bi bi-camera" aria-hidden="true"></i> Start face scan';
            visible(start, true);
            if (done) visible(done, false);
            progress();
        }
        function manualCaptureMode(message) {
            stopScanner();
            detector = null;
            capture.disabled = false;
            capture.removeAttribute('aria-busy');
            visible(capture, Boolean(stream));
            updateGuide('manual', 'Center your face');
            setStatus(message);
            if (stream) capture.focus({ preventScroll: true });
        }
        function queueScan(delay = 240) {
            if (!scanning || scanTimer !== null || !stream || document.hidden) return;
            scanTimer = window.setTimeout(scanFrame, delay);
        }
        function noseOffset(face) {
            const landmark = (face.landmarks || []).find(item => String(item.type || '').toLowerCase() === 'nose');
            const point = landmark?.locations?.[0] || landmark?.location || landmark;
            const box = face.boundingBox;
            if (!point || !Number.isFinite(point.x) || !box?.width) return null;
            return (point.x - (box.x + box.width / 2)) / (box.width / 2);
        }
        function isStable(sample) {
            if (!lastSample || Math.abs(sample.x - lastSample.x) > .025 ||
                Math.abs(sample.y - lastSample.y) > .03 || Math.abs(sample.size - lastSample.size) > .04 ||
                Math.abs(sample.offset - lastSample.offset) > .045) {
                lastSample = sample;
                stableCount = 1;
                guide.style.setProperty('--face-stability-progress', String(stableCount / 4));
                return false;
            }
            lastSample = sample;
            stableCount++;
            guide.style.setProperty('--face-stability-progress', String(Math.min(stableCount / 4, 1)));
            return stableCount >= 4;
        }
        async function captureView(index, attempt, automatic) {
            if (!poses[index] || !stream || attempt !== requestId) return false;
            capture.disabled = true;
            capture.setAttribute('aria-busy', 'true');
            capture.textContent = automatic ? 'Capturing ' + poses[index][0] + ' view…' : 'Capturing…';
            updateGuide('capturing', 'Capturing view ' + (index + 1) + ' of 3');
            lighting?.lock(true);
            try {
                if (lighting) await lighting.settle();
                if (attempt !== requestId || !stream) return false;
                if (!video.videoWidth || !video.videoHeight) throw new Error('The camera is not ready. Wait a moment and try again.');
                const scale = Math.min(1, 1280 / Math.max(video.videoWidth, video.videoHeight));
                canvas.width = Math.round(video.videoWidth * scale);
                canvas.height = Math.round(video.videoHeight * scale);
                const context = canvas.getContext('2d', { alpha: false });
                if (!context) throw new Error('This camera frame could not be read. Please try again.');
                context.drawImage(video, 0, 0, canvas.width, canvas.height);
                const blob = await new Promise(resolve => canvas.toBlob(resolve, 'image/jpeg', 0.88));
                if (attempt !== requestId) return false;
                if (!blob) throw new Error('This frame could not be captured. Please try again.');

                showCaptureFeedback();
                if (poses[index][0] === 'left' && lastSample) firstTurnSign = Math.sign(lastSample.offset);
                views[poses[index][0]] = blob;
                progress();
                if (index < 2) {
                    setStatus(automatic
                        ? poses[index][0][0].toUpperCase() + poses[index][0].slice(1) + ' view captured. ' + poses[index + 1][1]
                        : 'Capture ' + (index + 2) + ' of 3: ' + poses[index + 1][0] + ' view.');
                    if (automatic) queueScan(300);
                } else {
                    stopCamera();
                    video.style.display = 'none';
                    canvas.style.cssText = 'display:block;width:100%;height:100%;object-fit:cover;transform:scaleX(-1);';
                    visible(capture, false);
                    visible(retake, true);
                    visible(save, true);
                    setStatus('Three views captured. Review the sequence, then register them securely. The server will check face quality and identity.');
                    save.focus({ preventScroll: true });
                }
                return true;
            } catch (error) {
                if (attempt === requestId) {
                    resetStability();
                    setStatus(error.message || 'This frame could not be captured. Please try again.', '#f87171');
                }
                return false;
            } finally {
                lighting?.lock(false);
                capture.disabled = false;
                capture.removeAttribute('aria-busy');
                if (poses[index] && stream) capture.textContent = 'Capture ' + poses[index][0] + ' view';
                if (automatic && attempt === requestId && stream && scanning && !document.hidden) queueScan(240);
            }
        }
        function guideForPose(index, offset) {
            if (index === 0) return Math.abs(offset) <= .12;
            if (Math.abs(offset) < .16) return false;
            if (index === 1) return true;
            return firstTurnSign !== 0 && Math.sign(offset) === -firstTurnSign;
        }
        async function scanFrame() {
            scanTimer = null;
            if (!scanning || scanInFlight || !stream || document.hidden) return;
            scanInFlight = true;
            const attempt = requestId;
            try {
                const faces = await detector.detect(video);
                if (!scanning || attempt !== requestId || !stream) return;
                if (faces.length !== 1) {
                    resetStability();
                    updateGuide('searching', faces.length ? 'Keep one face in frame' : 'Move into the frame');
                    setStatus(faces.length ? 'More than one face is visible. Keep only your face in the frame.' : 'Move your face into the frame to begin the automatic scan.');
                    return;
                }
                const face = faces[0];
                const box = face.boundingBox;
                const offset = noseOffset(face);
                if (!box || !Number.isFinite(box.width) || !Number.isFinite(box.height) || offset === null) {
                    manualCaptureMode('Automatic pose scanning is not supported by this camera. Capture each view when the on-screen guide is ready.');
                    return;
                }
                const x = (box.x + box.width / 2) / video.videoWidth;
                const y = (box.y + box.height / 2) / video.videoHeight;
                const size = box.width / video.videoWidth;
                if (size < .18 || size > .72 || x < .3 || x > .7 || y < .16 || y > .84) {
                    resetStability();
                    updateGuide('searching', size < .18 ? 'Move a little closer' : size > .72 ? 'Move a little back' : 'Center your face');
                    setStatus(size < .18 ? 'Move a little closer and keep your full face inside the guide.' : size > .72
                        ? 'Move a little back so your full face fits inside the guide.'
                        : 'Center your face in the guide and keep both eyes visible.');
                    return;
                }
                const index = Object.keys(views).length;
                const sample = { x, y, size, offset };
                if (!guideForPose(index, offset)) {
                    resetStability();
                    updateGuide('searching', index === 0 ? 'Look straight at camera' : index === 1 ? 'Turn slightly to your left' : 'Turn the other way');
                    setStatus(index === 0 ? 'Look straight at the camera and hold still.' : index === 1
                        ? 'Turn your head slightly to your left. Keep your face centered.'
                        : 'Turn slightly to the opposite side. Keep your face centered.');
                    return;
                }
                updateGuide('ready', 'Hold still · scanning');
                setStatus('Face position detected. Hold still while this view is captured automatically.');
                if (isStable(sample)) {
                    resetStability();
                    await captureView(index, attempt, true);
                }
            } catch (_) {
                if (attempt === requestId) manualCaptureMode('Automatic face scanning stopped in this browser. You can still capture each view manually.');
            } finally {
                scanInFlight = false;
                if (scanning && stream && !document.hidden) queueScan(240);
            }
        }

        start.addEventListener('click', async () => {
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
                video.srcObject = opened;
                await video.play();
                if (attempt !== requestId) return;
                lighting?.start(opened);
                wrap.style.display = 'block';
                wrap.classList.add('face-camera-enter');
                visible(start, false);
                visible(cancel, true);
                try {
                    const FaceDetector = window.FaceDetector;
                    detector = typeof FaceDetector === 'function' ? new FaceDetector({ fastMode: true, maxDetectedFaces: 2 }) : null;
                } catch (_) { detector = null; }
                if (!detector) {
                    manualCaptureMode('Automatic pose scanning is not available in this browser. Capture each view manually; face captures stay on this device until you register them.');
                    return;
                }
                scanning = true;
                visible(capture, false);
                setStatus('Automatic scan is ready. Hold each position steady; your face captures stay on this device until you register them.');
                updateGuide('searching', 'Center your face');
                instruction.focus({ preventScroll: true });
                queueScan(250);
            } catch (error) {
                if (attempt !== requestId) return;
                reset();
                setStatus(error?.name === 'NotAllowedError'
                    ? 'Camera permission was denied. Allow camera access and retry.'
                    : 'The camera could not be opened. Check that it is available and retry.', '#f87171');
                start.focus({ preventScroll: true });
            }
        });

        capture.addEventListener('click', () => {
            if (scanning) return;
            captureView(Object.keys(views).length, requestId, false);
        });
        save.addEventListener('click', async () => {
            if (saving || !poses.every(([pose]) => views[pose])) return;
            const attempt = requestId;
            saving = true;
            [save, retake, cancel].forEach(button => { button.disabled = true; });
            save.setAttribute('aria-busy', 'true');
            save.textContent = 'Checking and saving…';
            setStatus('Checking all three face captures and saving securely…');
            const form = new FormData();
            poses.forEach(([pose]) => form.append('face_images[' + pose + ']', views[pose], pose + '.jpg'));
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
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initialize, { once: true });
    else initialize();
})();
