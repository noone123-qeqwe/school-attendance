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
        const done = document.getElementById('cameraFaceDone');
        const lighting = window.CameraLighting?.mount(document.getElementById('cameraFaceLight'));
        const poses = [
            ['front', 'Look straight at the camera. Keep your head level and both eyes visible.'],
            ['left', 'Turn your head slightly to your left. Keep both eyes visible and hold still.'],
            ['right', 'Now turn slightly to your right. Keep both eyes visible and hold still.'],
        ];
        let stream = null;
        let requestId = 0;
        let views = {};
        let saving = false;

        function setStatus(message, color = 'var(--text-muted)') {
            status.textContent = message;
            status.style.color = color;
        }
        function visible(element, show) {
            element.hidden = !show;
            element.style.display = show ? '' : 'none';
        }
        function stopCamera() {
            lighting?.stop();
            if (stream) stream.getTracks().forEach(track => track.stop());
            stream = null;
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
        }
        function reset() {
            stopCamera();
            views = {};
            saving = false;
            wrap.style.display = 'none';
            video.style.display = 'block';
            canvas.style.display = 'none';
            [capture, retake, save, cancel].forEach(button => { visible(button, false); button.disabled = false; });
            start.disabled = false;
            start.removeAttribute('aria-busy');
            start.innerHTML = '<i class="bi bi-camera" aria-hidden="true"></i> Open camera';
            visible(start, true);
            if (done) visible(done, false);
            progress();
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
                visible(start, false);
                visible(capture, true);
                visible(cancel, true);
                setStatus('Capture 1 of 3: front view. Use even lighting and keep only your face in the frame.');
            } catch (error) {
                if (attempt !== requestId) return;
                reset();
                setStatus(error?.name === 'NotAllowedError'
                    ? 'Camera permission was denied. Allow camera access and retry.'
                    : 'The camera could not be opened. Check that it is available and retry.', '#f87171');
            }
        });

        capture.addEventListener('click', async () => {
            const index = Object.keys(views).length;
            if (!poses[index] || capture.disabled || !stream) return;
            if (!video.videoWidth || !video.videoHeight) {
                setStatus('The camera is not ready. Wait a moment and try again.', '#f87171');
                return;
            }
            const attempt = requestId;
            capture.disabled = true;
            capture.setAttribute('aria-busy', 'true');
            capture.textContent = 'Adjusting light…';
            lighting?.lock(true);
            if (lighting) await lighting.settle();
            if (attempt !== requestId || !stream) return;
            const scale = Math.min(1, 1280 / Math.max(video.videoWidth, video.videoHeight));
            canvas.width = Math.round(video.videoWidth * scale);
            canvas.height = Math.round(video.videoHeight * scale);
            try {
                canvas.getContext('2d', { alpha: false }).drawImage(video, 0, 0, canvas.width, canvas.height);
            } catch (_) {
                capture.disabled = false;
                capture.removeAttribute('aria-busy');
                capture.textContent = 'Capture ' + poses[index][0] + ' view';
                lighting?.lock(false);
                setStatus('This camera frame could not be read. Please try again.', '#f87171');
                return;
            }
            canvas.toBlob(blob => {
                if (attempt !== requestId) return;
                capture.disabled = false;
                capture.removeAttribute('aria-busy');
                capture.textContent = 'Capture ' + poses[index][0] + ' view';
                lighting?.lock(false);
                if (!blob) {
                    setStatus('This frame could not be captured. Please try again.', '#f87171');
                    return;
                }
                views[poses[index][0]] = blob;
                progress();
                if (index < 2) {
                    setStatus('Capture ' + (index + 2) + ' of 3: ' + poses[index + 1][0] + ' view.');
                } else {
                    stopCamera();
                    video.style.display = 'none';
                    canvas.style.cssText = 'display:block;width:100%;height:100%;object-fit:cover;transform:scaleX(-1);';
                    visible(capture, false);
                    visible(retake, true);
                    visible(save, true);
                    setStatus('Three views captured. The server will check face quality, head positions, and that all captures show the same person.');
                }
            }, 'image/jpeg', 0.88);
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
            }
        });
        retake.addEventListener('click', () => { requestId++; reset(); start.click(); });
        cancel.addEventListener('click', () => { requestId++; reset(); setStatus('Capture canceled.'); });
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
