@extends('layouts.mobile-app')

@section('title', 'Check in for a Classmate')

@section('content')
<main class="peer-page">
    <header class="peer-heading">
        <span class="peer-eyebrow">PHONE-LESS ATTENDANCE</span>
        <h1>Check in for a Classmate</h1>
        <p>Help a classmate who is here but cannot use their own phone. You must already be checked in and inside the classroom.</p>
    </header>

    <section class="peer-card" id="peerFormCard">
        <div class="peer-step"><span>1</span><div><strong>Choose your class</strong><small>Only eligible live sessions appear here.</small></div></div>
        <label for="peerSession">Active attendance session</label>
        <select id="peerSession" required><option value="">Loading sessions…</option></select>
        <div class="peer-step peer-step-second"><span>2</span><div><strong>Pass the phone to your classmate</strong><small>They enter their own student number privately.</small></div></div>
        <label for="peerStudent">Student number</label>
        <input id="peerStudent" type="text" autocomplete="off" autocapitalize="characters" maxlength="40" placeholder="Enter student number" required>
        <button id="peerStart" type="button" class="peer-primary" disabled>Continue to camera <i class="bi bi-arrow-right"></i></button>
        <p id="peerFormMessage" class="peer-form-message" role="status" aria-live="polite">Loading available sessions…</p>
        <button id="peerRetrySessions" type="button" class="peer-secondary" hidden>Refresh available sessions</button>
    </section>

    <section class="peer-card peer-camera-card" id="peerCameraCard" hidden>
        <div class="peer-camera-top"><span class="peer-live-dot"></span><span>Live camera</span><span id="peerCountdown" class="peer-countdown"></span></div>
        <div class="peer-viewport">
            <video id="peerVideo" autoplay muted playsinline aria-label="Live front camera preview"></video>
            <div class="peer-oval" aria-hidden="true"></div>
        </div>
        <p class="peer-camera-note" id="peerGuidance" role="status" aria-live="polite">Position one face inside the oval. Look directly at the camera.</p>
        @include('partials.camera-light-controls', ['cameraLightId' => 'peerFaceLight'])
        <div class="peer-challenge" id="peerChallenge" hidden></div>
        <button id="peerCapture" type="button" class="peer-primary" disabled>Begin live challenge</button>
        <button id="peerCancel" type="button" class="peer-secondary">Cancel and release camera</button>
    </section>

    <section id="peerResult" class="peer-card peer-result" role="status" aria-live="polite" tabindex="-1" hidden></section>
    <p class="peer-privacy"><i class="bi bi-shield-lock"></i> Live verification frames are processed transiently against the classmate's encrypted enrollment and are not saved to either student's attendance account.</p>
</main>

<style>
.peer-page{max-width:720px;margin:0 auto;padding:12px 2px 104px;color:#FAF8F5}
.peer-heading{margin-bottom:18px}
.peer-eyebrow{display:inline-block;font-size:.7rem;font-weight:700;letter-spacing:.12em;color:#D4AF37;margin-bottom:6px;text-transform:uppercase}
.peer-heading h1{font-size:clamp(1.42rem,4.6vw,2rem);font-weight:700;letter-spacing:-.025em;line-height:1.16;margin-bottom:6px;color:#FAF8F5}
.peer-heading p{color:#D6CCC6;font-size:.88rem;line-height:1.52;max-width:60ch;margin:0}
.peer-card{background:#1E1515;border:1px solid rgba(255, 255, 255,.11);border-radius:14px;padding:18px;margin-bottom:14px;min-width:0;overflow-wrap:anywhere}
.peer-step{display:flex;gap:12px;align-items:center;margin-bottom:14px}
.peer-step>span{width:30px;height:30px;display:grid;place-items:center;border-radius:8px;background:rgba(122, 26, 26, 0.28);border:1px solid rgba(212, 175, 55,.3);color:#FAF8F5;font-weight:700;font-size:.84rem;flex:none}
.peer-step strong{display:block;font-size:.96rem;color:#FAF8F5}
.peer-step small{display:block;color:#A49890;margin-top:2px;font-size:.78rem}
.peer-step-second{margin-top:20px;padding-top:16px;border-top:1px solid rgba(255, 255, 255,.075)}
.peer-card label{display:block;font-size:.74rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;color:#D6CCC6;margin-bottom:7px}
.peer-card select,.peer-card input{width:100%;min-height:48px;background:#141010;color:#FAF8F5;border:1px solid rgba(255, 255, 255,.14);border-radius:10px;padding:11px 14px;font-size:16px}
.peer-card select:focus,.peer-card input:focus,.peer-card button:focus-visible,.peer-result:focus-visible{outline:2px solid #D4AF37;outline-offset:2px}
.peer-primary,.peer-secondary{min-height:48px;width:100%;border:1px solid transparent;border-radius:10px;font-weight:700;padding:12px 16px;font-size:.9rem;cursor:pointer;display:inline-flex;align-items:center;justify-content:center;gap:8px;transition:background .15s ease,border-color .15s ease}
.peer-primary{margin-top:18px;background:#FAF8F5;border-color:#FAF8F5;color:#800000}
.peer-primary:hover:not(:disabled){background:#FFFFFF;color:#800000}
.peer-primary:disabled{opacity:.42;cursor:not-allowed}
.peer-secondary{margin-top:10px;background:#261B1B;color:#FAF8F5;border-color:rgba(255, 255, 255,.16)}
.peer-secondary:hover:not(:disabled){background:#2F2222;border-color:rgba(255, 255, 255,.28)}
.peer-form-message{min-height:1.5em;margin:12px 0 0;color:#D6CCC6;font-size:.82rem;line-height:1.45}
.peer-camera-top{display:flex;align-items:center;gap:8px;font-size:.74rem;text-transform:uppercase;letter-spacing:.09em;font-weight:700;margin-bottom:12px;color:#D6CCC6}
.peer-live-dot{width:8px;height:8px;border-radius:50%;background:#34D399;box-shadow:0 0 0 4px rgba(52,211,153,.2)}
.peer-countdown{margin-left:auto;color:#D4AF37;font-variant-numeric:tabular-nums}
.peer-viewport{position:relative;overflow:hidden;border-radius:14px;background:#080305;border:1px solid rgba(255, 255, 255,.12);aspect-ratio:4/5;max-height:46dvh}
.peer-viewport video{width:100%;height:100%;object-fit:cover;transform:scaleX(-1)}
.peer-oval{position:absolute;inset:15% 18%;border:2.5px solid #FAF8F5;border-radius:50%;box-shadow:0 0 0 999px rgba(11,4,6,.52);pointer-events:none}
.peer-camera-note{text-align:center;color:#D6CCC6;font-size:.86rem;line-height:1.45;margin:14px 0 0}
.peer-challenge{margin-top:14px;border:1px solid rgba(212, 175, 55,.36);background:rgba(122, 26, 26, 0.32);border-radius:10px;padding:12px 14px;text-align:center;font-size:.98rem;font-weight:700;color:#FAF8F5}
.peer-result h2{font-size:1.15rem;font-weight:700;margin-bottom:6px;color:#FAF8F5}
.peer-result p{margin:0;color:#D6CCC6;font-size:.86rem;line-height:1.5}
.peer-result.success{border-color:rgba(52,211,153,.4);border-left:3px solid #34D399}
.peer-result.error{border-color:rgba(251,113,133,.4);border-left:3px solid #FB7185}
.peer-privacy{display:flex;gap:8px;align-items:flex-start;font-size:.78rem;line-height:1.5;color:#A49890;padding:0 4px}
@media(min-width:700px){.peer-page{padding-top:24px}.peer-card{padding:24px}.peer-viewport{aspect-ratio:4/3;max-height:480px}}
@media(max-width:380px){.peer-page{padding-inline:0}.peer-card{padding:15px}.peer-heading h1{font-size:1.38rem}}
@media(max-height:600px) and (orientation:landscape){.peer-viewport{aspect-ratio:4/3;max-height:52dvh}.peer-page{padding-top:10px}}
</style>
@endsection

@push('scripts')
<script @cspNonce>
(() => {
    const $ = id => document.getElementById(id);
    const sessionSelect = $('peerSession'), studentInput = $('peerStudent');
    const formCard = $('peerFormCard'), cameraCard = $('peerCameraCard'), result = $('peerResult');
    const video = $('peerVideo'), guidance = $('peerGuidance'), captureButton = $('peerCapture');
    const lighting = window.CameraLighting?.mount($('peerFaceLight'));
    const challengeBox = $('peerChallenge'), countdown = $('peerCountdown');
    const startButton = $('peerStart'), retrySessionsButton = $('peerRetrySessions'), formMessage = $('peerFormMessage');
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    const startUrl = @json(route('peer-snap.start'));
    const confirmUrl = @json(route('peer-snap.confirm'));
    const sessionsUrl = @json(route('peer-snap.sessions'));
    const transitions = {
        IDLE:['IDENTIFYING'], IDENTIFYING:['CAMERA_STARTING','FAILED','LOCKED','EXPIRED','IDLE'],
        CAMERA_STARTING:['FACE_SEARCH','FAILED','EXPIRED','IDLE'], FACE_SEARCH:['LIVENESS_CHECK','FAILED','EXPIRED','IDLE'],
        LIVENESS_CHECK:['MATCHING','FAILED','EXPIRED','IDLE'], MATCHING:['SUCCESS','FAILED','LOCKED','EXPIRED','IDLE'],
        SUCCESS:['IDLE'], FAILED:['IDLE'], LOCKED:['IDLE'], EXPIRED:['IDLE']
    };
    const defaultGuidance = 'Position one face inside the oval. Look directly at the camera.';
    const pendingKey = 'peer-snap-pending-confirmation';
    let state = 'IDLE', stream = null, ticket = null, timer = null, detector = null, flowId = 0, sessionLoadId = 0;
    const isCurrent = id => id === flowId;
    function setState(next) { if (!transitions[state]?.includes(next)) throw new Error('Invalid verification state'); state = next; }
    function markPending(value) { try { if (value) sessionStorage.setItem(pendingKey, '1'); else sessionStorage.removeItem(pendingKey); } catch (_) { /* Storage may be unavailable in private browsing. */ } }
    function wasPending() { try { return sessionStorage.getItem(pendingKey) === '1'; } catch (_) { return false; } }
    function updateStartAvailability() { startButton.disabled = state !== 'IDLE' || !sessionSelect.value || !studentInput.value.trim(); }
    function stopCamera() {
        lighting?.stop();
        const active = stream;
        stream = null;
        video.pause?.();
        video.srcObject = null;
        if (active) active.getTracks().forEach(track => track.stop());
    }
    function showResult(title, message, type) {
        result.className = `peer-card peer-result ${type}`;
        result.replaceChildren();
        const h = document.createElement('h2'), p = document.createElement('p');
        h.textContent = title; p.textContent = message; result.append(h, p); result.hidden = false;
        result.focus?.();
    }
    function reset() {
        flowId++;
        stopCamera();
        clearInterval(timer); timer = null;
        ticket = null; detector = null;
        cameraCard.hidden = true; formCard.hidden = false;
        sessionSelect.disabled = false; studentInput.disabled = false;
        captureButton.disabled = false; challengeBox.hidden = true; challengeBox.textContent = '';
        countdown.textContent = ''; guidance.textContent = defaultGuidance;
        if (state !== 'IDLE') setState('IDLE');
        updateStartAvailability();
    }
    function fail(message, status) {
        if (state === 'IDLE') return;
        if (state !== 'FAILED' && state !== 'LOCKED' && state !== 'EXPIRED') setState(status === 429 ? 'LOCKED' : status === 410 ? 'EXPIRED' : 'FAILED');
        reset();
        formMessage.textContent = 'You can retry this classmate or ask your instructor for a manual attendance check.';
        showResult(status === 429 ? 'Temporarily locked' : status === 410 ? 'Session expired' : 'Verification could not finish', message, 'error');
    }
    function cameraError(error) {
        if (['NotAllowedError', 'PermissionDeniedError', 'SecurityError'].includes(error.name)) return 'Camera access was denied. Allow it in browser settings and retry, or ask your instructor for a manual attendance check.';
        if (['NotFoundError', 'DevicesNotFoundError'].includes(error.name)) return 'No camera is available. Ask your instructor for a manual attendance check.';
        if (['NotReadableError', 'TrackStartError', 'AbortError'].includes(error.name)) return 'The camera could not start or stopped working. Close other camera apps, then retry.';
        if (['OverconstrainedError', 'ConstraintNotSatisfiedError'].includes(error.name)) return 'This camera cannot use the requested settings. Try another browser or ask your instructor for help.';
        return error.message || 'The camera could not start. Retry or ask your instructor for help.';
    }
    async function jsonResponse(response) {
        const body = await response.json().catch(() => ({}));
        if (!response.ok) {
            const serverMessage = typeof body.message === 'string' ? body.message : '';
            const error = new Error(serverMessage || 'Request failed. Please try again.');
            error.status = response.status;
            error.errorType = body.error_type || body.error || null;
            error.serverMessage = serverMessage;
            throw error;
        }
        return body;
    }
    async function loadSessions() {
        const id = ++sessionLoadId, selected = sessionSelect.value;
        retrySessionsButton.hidden = true; retrySessionsButton.disabled = true;
        sessionSelect.disabled = true; startButton.disabled = true;
        formMessage.textContent = 'Checking available sessions…';
        try {
            const body = await jsonResponse(await fetch(sessionsUrl, {headers:{Accept:'application/json'}, credentials:'same-origin'}));
            if (id !== sessionLoadId) return;
            sessionSelect.replaceChildren();
            if (!body.available) { sessionSelect.add(new Option('Peer verification is not available yet', '')); formMessage.textContent = 'Ask your instructor for a manual attendance check.'; retrySessionsButton.hidden = false; return; }
            if (!Array.isArray(body.sessions) || !body.sessions.length) { sessionSelect.add(new Option('No eligible active class sessions', '')); formMessage.textContent = 'No class is available right now. Refresh or ask your instructor for help.'; retrySessionsButton.hidden = false; return; }
            sessionSelect.add(new Option('Choose a session', ''));
            body.sessions.forEach(s => sessionSelect.add(new Option(`${s.subject_code} · ${s.subject_name || 'Class'} · ${s.remaining_vouches} remaining`, s.id)));
            if (body.sessions.some(s => String(s.id) === selected)) sessionSelect.value = selected;
            formMessage.textContent = 'Have the classmate enter their own student number before starting.';
        } catch (error) {
            if (id !== sessionLoadId) return;
            let message = 'Could not connect to load classes. Check your connection, then refresh the list.';
            if (error.errorType === 'device_mismatch') {
                message = error.serverMessage || 'Use your registered device to continue, or ask your instructor to reset the device binding.';
            } else if (error.status === 401) {
                message = 'Your sign-in has expired. Sign in again, then return to this page.';
            } else if (error.status === 403) {
                message = error.serverMessage || 'Peer verification is unavailable for this account or device. Ask your instructor for help.';
            } else if (error.status === 429) {
                message = 'Too many refresh requests. Wait a moment, then try again.';
            } else if (error.status >= 500) {
                message = 'The server could not load classes. Please try again shortly or ask your instructor for help.';
            } else if (error.status) {
                message = error.message;
            }
            sessionSelect.replaceChildren(new Option(message, ''));
            formMessage.textContent = message;
            retrySessionsButton.hidden = error.status === 401 || error.errorType === 'device_mismatch';
        } finally {
            if (id === sessionLoadId) { sessionSelect.disabled = state !== 'IDLE'; retrySessionsButton.disabled = false; updateStartAvailability(); }
        }
    }
    async function start() {
        if (state !== 'IDLE' || !sessionSelect.value || !studentInput.value.trim()) return;
        if (window.isSecureContext === false || !navigator.mediaDevices?.getUserMedia) {
            showResult('Camera unavailable', 'Use an updated browser over HTTPS, or ask your instructor for a manual attendance check.', 'error');
            return;
        }
        const id = ++flowId;
        const sessionId = Number(sessionSelect.value), studentNumber = studentInput.value.trim();
        markPending(false);
        result.hidden = true; setState('IDENTIFYING'); updateStartAvailability();
        sessionSelect.disabled = true; studentInput.disabled = true;
        formMessage.textContent = 'Checking classmate eligibility…';
        try {
            const newTicket = await jsonResponse(await fetch(startUrl, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,Accept:'application/json'}, body:JSON.stringify({session_id:sessionId,student_number:studentNumber})}));
            if (!isCurrent(id)) return;
            if (!newTicket.verification_id || !newTicket.nonce || !Number.isFinite(Date.parse(newTicket.expires_at))) throw new Error('The verification session could not start. Please try again.');
            ticket = newTicket;
            setState('CAMERA_STARTING');
            formMessage.textContent = 'Allow camera access when your browser asks.';
            timer = setInterval(() => {
                if (!isCurrent(id) || !ticket) return;
                const seconds = Math.max(0, Math.ceil((Date.parse(ticket.expires_at) - Date.now()) / 1000));
                countdown.textContent = seconds ? `${seconds}s left` : 'Finalizing…';
                if (!seconds && ['CAMERA_STARTING', 'FACE_SEARCH', 'LIVENESS_CHECK'].includes(state)) fail('The verification window ended. Start again.', 410);
            }, 500);
            const newStream = await navigator.mediaDevices.getUserMedia({video:{facingMode:'user'},audio:false});
            if (!isCurrent(id)) { newStream.getTracks().forEach(track => track.stop()); return; }
            stream = newStream;
            const videoTrack = newStream.getVideoTracks?.()[0] || newStream.getTracks()[0];
            if (!videoTrack || videoTrack.readyState === 'ended') throw new Error('The camera stopped before the preview opened. Try again.');
            videoTrack.addEventListener?.('ended', () => {
                if (stream === newStream && isCurrent(id)) fail('The camera stopped. Reconnect it and start again.', null);
            }, {once:true});
            formCard.hidden = true; cameraCard.hidden = false;
            video.srcObject = stream; await video.play();
            if (!isCurrent(id)) return;
            lighting?.start(newStream);
            try { detector = 'FaceDetector' in window ? new FaceDetector({fastMode:true,maxDetectedFaces:3}) : null; }
            catch { detector = null; }
            challengeBox.textContent = {blink_twice:'Blink twice while looking at the camera',turn_left:'Turn your head slightly left, then face forward',turn_right:'Turn your head slightly right, then face forward'}[ticket.challenge] || 'Follow the on-screen challenge';
            challengeBox.hidden = false; captureButton.disabled = false; setState('FACE_SEARCH');
            guidance.textContent = defaultGuidance;
        } catch (error) { if (isCurrent(id)) fail(error.status ? error.message : cameraError(error), error.status); }
        finally { if (isCurrent(id)) updateStartAvailability(); }
    }
    async function captureFrame(canvas, context) {
        if (!video.videoWidth || !video.videoHeight) throw new Error('Camera is still starting. Wait a moment and try again.');
        const height = Math.round(480 * video.videoHeight / video.videoWidth);
        if (canvas.width !== 480 || canvas.height !== height) { canvas.width = 480; canvas.height = height; }
        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        const blob = await new Promise(resolve => canvas.toBlob(resolve,'image/jpeg',.72));
        if (!blob) throw new Error('Camera frame could not be captured.');
        if (blob.size > 350 * 1024) throw new Error('Camera image is too large. Improve lighting and try again.');
        return blob;
    }
    async function capture() {
        if (state !== 'FACE_SEARCH' || !ticket) return;
        const id = flowId;
        captureButton.disabled = true;
        lighting?.lock(true);
        const canvas = document.createElement('canvas'), context = canvas.getContext?.('2d');
        try {
            if (lighting) await lighting.settle();
            if (!isCurrent(id)) return;
            if (!context || typeof canvas.toBlob !== 'function') { const error = new Error('This browser cannot capture camera frames. Try an updated browser or ask your instructor for help.'); error.fatal = true; throw error; }
            if (detector) {
                let faces = null;
                try { faces = await detector.detect(video); } catch (_) { detector = null; }
                if (!isCurrent(id)) return;
                if (faces) {
                    if (faces.length !== 1) throw new Error(faces.length ? 'Multiple faces detected. Keep only one person in view.' : 'No face detected. Move into the oval.');
                    const box = faces[0].boundingBox;
                    const ratio = box.width / video.videoWidth;
                    if (ratio < .17) throw new Error('Face is too far away. Move closer.');
                    if (ratio > .75) throw new Error('Face is too close. Move back.');
                    if (box.x < 0 || box.x + box.width > video.videoWidth) throw new Error('Keep your whole face inside the frame.');
                }
            }
            if (Date.parse(ticket.expires_at) <= Date.now()) { const error = new Error('The verification window ended. Start again.'); error.status = 410; throw error; }
            setState('LIVENESS_CHECK'); guidance.textContent = 'Perform the challenge now. Hold the phone still while five live frames are captured.';
            const data = new FormData(); data.append('verification_id',ticket.verification_id); data.append('nonce',ticket.nonce);
            await new Promise(resolve => setTimeout(resolve, 600));
            for (let i=0;i<5;i++) { if (!isCurrent(id)) return; guidance.textContent = `Live challenge running · frame ${i+1} of 5`; const frame = await captureFrame(canvas, context); if (!isCurrent(id)) return; data.append(`frames[${i}]`,frame,`frame-${i}.jpg`); if (i<4) await new Promise(resolve => setTimeout(resolve,650)); }
            if (!isCurrent(id)) return;
            setState('MATCHING'); guidance.textContent = 'Verifying identity securely…';
            stopCamera();
            markPending(true);
            const response = await fetch(confirmUrl,{method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':csrf,Accept:'application/json'},body:data});
            if (!response.ok && response.status < 500) markPending(false);
            const body = await jsonResponse(response);
            if (body.status !== 'Present' || !Number.isSafeInteger(Number(body.attendance_id))) throw new Error('The server did not confirm attendance. Ask your instructor to check the result.');
            markPending(false);
            if (!isCurrent(id)) return;
            setState('SUCCESS'); studentInput.value = ''; reset();
            showResult('Identity verified', `Attendance recorded for ${body.student_name}. The phone is ready for the next classmate.`, 'success');
            await loadSessions();
        } catch (error) {
            if (!isCurrent(id)) return;
            if (state === 'FACE_SEARCH' && !error.status && !error.fatal) { guidance.textContent = error.message; captureButton.disabled = false; return; }
            const uncertain = state === 'MATCHING' && (!error.status || error.status >= 500);
            fail(uncertain ? 'The final result could not be confirmed. Ask your instructor to check attendance before trying this classmate again.' : (error.message || 'Verification failed. Please try again.'), error.status);
        } finally { lighting?.lock(false); canvas.width = 0; canvas.height = 0; }
    }
    $('peerStart').addEventListener('click',start);
    retrySessionsButton.addEventListener('click',loadSessions);
    sessionSelect.addEventListener('change',updateStartAvailability);
    studentInput.addEventListener('input',updateStartAvailability);
    captureButton.addEventListener('click',capture);
    $('peerCancel').addEventListener('click',() => { reset(); result.hidden = true; });
    let interruptedOnPagehide = false;
    window.addEventListener('pagehide',() => { interruptedOnPagehide = state === 'MATCHING'; reset(); });
    window.addEventListener('pageshow',() => {
        if (!interruptedOnPagehide) return;
        interruptedOnPagehide = false; markPending(false);
        showResult('Check the previous result', 'The page was left after a check-in was submitted. Ask your instructor to check attendance before trying that classmate again.', 'error');
        loadSessions();
    });
    document.addEventListener('visibilitychange',() => { if (document.hidden && state !== 'IDLE' && state !== 'MATCHING') { reset(); showResult('Verification paused', 'The camera was released when you left this page. Start again to continue.', 'error'); } });
    window.addEventListener('online',() => { if (state === 'IDLE') loadSessions(); });
    if (wasPending()) { markPending(false); showResult('Check the previous result', 'A previous check-in was interrupted after submission. Ask your instructor to check attendance before trying that classmate again.', 'error'); }
    loadSessions();
})();
</script>
@endpush
