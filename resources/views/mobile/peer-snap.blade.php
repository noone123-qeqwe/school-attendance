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
        <button id="peerStart" type="button" class="peer-primary">Continue to camera <i class="bi bi-arrow-right"></i></button>
    </section>

    <section class="peer-card peer-camera-card" id="peerCameraCard" hidden>
        <div class="peer-camera-top"><span class="peer-live-dot"></span><span>Live camera</span><span id="peerCountdown" class="peer-countdown"></span></div>
        <div class="peer-viewport">
            <video id="peerVideo" autoplay muted playsinline aria-label="Live front camera preview"></video>
            <div class="peer-oval" aria-hidden="true"></div>
        </div>
        <p class="peer-camera-note" id="peerGuidance">Position one face inside the oval. Look directly at the camera.</p>
        <div class="peer-challenge" id="peerChallenge" hidden></div>
        <button id="peerCapture" type="button" class="peer-primary" disabled>Begin live challenge</button>
        <button id="peerCancel" type="button" class="peer-secondary">Cancel and release camera</button>
    </section>

    <section id="peerResult" class="peer-card peer-result" role="status" aria-live="polite" hidden></section>
    <p class="peer-privacy"><i class="bi bi-shield-lock"></i> Camera frames are sent for verification and are not saved in your attendance account.</p>
</main>

<style>
.peer-page{max-width:760px;margin:0 auto;padding:24px 16px 110px;color:#f3e7cd}.peer-heading{margin-bottom:22px}.peer-eyebrow{display:inline-block;font-size:.69rem;font-weight:800;letter-spacing:.16em;color:#cfa46f;margin-bottom:9px}.peer-heading h1{font-size:clamp(1.7rem,5vw,2.5rem);font-weight:800;line-height:1.1;margin-bottom:10px}.peer-heading p{color:#bda994;line-height:1.55;max-width:62ch}.peer-card{background:#26201d;border:1px solid #493a2d;border-radius:22px;padding:22px;margin-bottom:16px;box-shadow:0 14px 35px #0003}.peer-step{display:flex;gap:12px;align-items:center;margin-bottom:14px}.peer-step>span{width:32px;height:32px;display:grid;place-items:center;border-radius:50%;background:#cfa46f;color:#20130c;font-weight:800;flex:none}.peer-step strong{display:block;font-size:1rem}.peer-step small{display:block;color:#a9988a;margin-top:2px}.peer-step-second{margin-top:26px}.peer-card label{display:block;font-size:.78rem;font-weight:800;color:#e4cab0;margin-bottom:8px}.peer-card select,.peer-card input{width:100%;min-height:48px;background:#171310;color:#f3e7cd;border:1px solid #65503e;border-radius:12px;padding:12px 14px;font-size:1rem}.peer-card select:focus,.peer-card input:focus{outline:2px solid #cfa46f;outline-offset:2px}.peer-primary,.peer-secondary{min-height:49px;width:100%;border:0;border-radius:12px;font-weight:800;padding:12px 16px}.peer-primary{margin-top:24px;background:#cfa46f;color:#20130c}.peer-primary:disabled{opacity:.45}.peer-secondary{margin-top:10px;background:transparent;color:#e3c8ab;border:1px solid #65503e}.peer-camera-top{display:flex;align-items:center;gap:8px;font-size:.76rem;text-transform:uppercase;letter-spacing:.1em;font-weight:800;margin-bottom:12px}.peer-live-dot{width:8px;height:8px;border-radius:50%;background:#ef735a;box-shadow:0 0 0 5px #ef735a22}.peer-countdown{margin-left:auto;color:#f4c575}.peer-viewport{position:relative;overflow:hidden;border-radius:18px;background:#090909;aspect-ratio:4/5;max-height:520px}.peer-viewport video{width:100%;height:100%;object-fit:cover;transform:scaleX(-1)}.peer-oval{position:absolute;inset:15% 18%;border:3px solid #f5d9aa;border-radius:50%;box-shadow:0 0 0 999px #0005;pointer-events:none}.peer-camera-note{text-align:center;color:#dec4a5;line-height:1.45;margin:16px 0 0}.peer-challenge{margin-top:16px;border:1px solid #b88e55;background:#5a3e1c88;border-radius:12px;padding:14px;text-align:center;font-size:1.08rem;font-weight:800}.peer-result h2{font-size:1.3rem;font-weight:800;margin-bottom:6px}.peer-result p{margin:0;color:#d5c0aa}.peer-result.success{border-color:#4b9d66}.peer-result.error{border-color:#ce705d}.peer-privacy{display:flex;gap:8px;align-items:flex-start;font-size:.79rem;line-height:1.5;color:#9f9083;padding:0 5px}@media(min-width:700px){.peer-page{padding-top:38px}.peer-card{padding:30px}.peer-viewport{aspect-ratio:4/3}}
</style>
@endsection

@push('scripts')
<script @cspNonce>
(() => {
    const $ = id => document.getElementById(id);
    const sessionSelect = $('peerSession'), studentInput = $('peerStudent');
    const formCard = $('peerFormCard'), cameraCard = $('peerCameraCard'), result = $('peerResult');
    const video = $('peerVideo'), guidance = $('peerGuidance'), captureButton = $('peerCapture');
    const challengeBox = $('peerChallenge'), countdown = $('peerCountdown');
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
    let state = 'IDLE', stream = null, ticket = null, timer = null, detector = null, flowId = 0;
    const isCurrent = id => id === flowId && !document.hidden;
    function setState(next) { if (!transitions[state]?.includes(next)) throw new Error('Invalid verification state'); state = next; }
    function stopCamera() { if (stream) stream.getTracks().forEach(track => track.stop()); stream = null; video.srcObject = null; clearInterval(timer); timer = null; }
    function showResult(title, message, type) {
        result.className = `peer-card peer-result ${type}`;
        result.replaceChildren();
        const h = document.createElement('h2'), p = document.createElement('p');
        h.textContent = title; p.textContent = message; result.append(h, p); result.hidden = false;
    }
    function reset() { flowId++; stopCamera(); ticket = null; cameraCard.hidden = true; formCard.hidden = false; $('peerStart').disabled = false; captureButton.disabled = false; challengeBox.hidden = true; countdown.textContent = ''; if (state !== 'IDLE') setState('IDLE'); }
    function fail(message, status) { if (state === 'IDLE') return; if (state !== 'FAILED' && state !== 'LOCKED' && state !== 'EXPIRED') setState(status === 429 ? 'LOCKED' : status === 410 ? 'EXPIRED' : 'FAILED'); reset(); showResult(status === 429 ? 'Temporarily locked' : status === 410 ? 'Session expired' : 'Verification could not finish', message, 'error'); }
    async function jsonResponse(response) {
        const body = await response.json().catch(() => ({}));
        if (!response.ok) { const error = new Error(body.message || 'Request failed. Please try again.'); error.status = response.status; throw error; }
        return body;
    }
    async function loadSessions() {
        try {
            const body = await jsonResponse(await fetch(sessionsUrl, {headers:{Accept:'application/json'}, credentials:'same-origin'}));
            sessionSelect.replaceChildren();
            if (!body.available) { sessionSelect.add(new Option('Peer verification is not available yet', '')); $('peerStart').disabled = true; return; }
            if (!body.sessions.length) { sessionSelect.add(new Option('No eligible active class sessions', '')); $('peerStart').disabled = true; return; }
            sessionSelect.add(new Option('Choose a session', ''));
            body.sessions.forEach(s => sessionSelect.add(new Option(`${s.subject_code} · ${s.subject_name || 'Class'} · ${s.remaining_vouches} remaining`, s.id)));
        } catch { sessionSelect.replaceChildren(new Option('Could not load sessions', '')); $('peerStart').disabled = true; }
    }
    async function start() {
        if (state !== 'IDLE' || !sessionSelect.value || !studentInput.value.trim()) return;
        const id = ++flowId;
        result.hidden = true; setState('IDENTIFYING'); $('peerStart').disabled = true;
        try {
            const newTicket = await jsonResponse(await fetch(startUrl, {method:'POST', credentials:'same-origin', headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,Accept:'application/json'}, body:JSON.stringify({session_id:Number(sessionSelect.value),student_number:studentInput.value.trim()})}));
            if (!isCurrent(id)) return;
            ticket = newTicket;
            setState('CAMERA_STARTING');
            if (!navigator.mediaDevices?.getUserMedia) throw new Error('This browser does not support a live camera. Use an updated browser over HTTPS.');
            const newStream = await navigator.mediaDevices.getUserMedia({video:{facingMode:'user'},audio:false});
            if (!isCurrent(id)) { newStream.getTracks().forEach(track => track.stop()); return; }
            stream = newStream;
            formCard.hidden = true; cameraCard.hidden = false;
            video.srcObject = stream; await video.play();
            if (!isCurrent(id)) return;
            try { detector = 'FaceDetector' in window ? new FaceDetector({fastMode:true,maxDetectedFaces:3}) : null; }
            catch { detector = null; }
            challengeBox.textContent = {blink_twice:'Blink twice while looking at the camera',turn_left:'Turn your head slightly left, then face forward',turn_right:'Turn your head slightly right, then face forward'}[ticket.challenge] || 'Follow the on-screen challenge';
            challengeBox.hidden = false; captureButton.disabled = false; setState('FACE_SEARCH');
            timer = setInterval(() => { if (!isCurrent(id) || !ticket) return; const secs = Math.max(0, Math.ceil((Date.parse(ticket.expires_at)-Date.now())/1000)); countdown.textContent = `${secs}s left`; if (!secs && ['CAMERA_STARTING','FACE_SEARCH','LIVENESS_CHECK','MATCHING'].includes(state)) fail('Start a new verification session.',410); },500);
        } catch (error) { if (!isCurrent(id)) return; const message = error.name === 'NotAllowedError' ? 'Camera permission was denied. Allow camera access and try again.' : error.name === 'NotFoundError' ? 'No camera is available on this device.' : error.message; fail(message,error.status); }
        finally { if (isCurrent(id)) $('peerStart').disabled = false; }
    }
    async function captureFrame() {
        if (!video.videoWidth || !video.videoHeight) throw new Error('Camera is still starting. Wait a moment and try again.');
        const canvas = document.createElement('canvas'); canvas.width = 480; canvas.height = Math.round(480 * video.videoHeight / video.videoWidth);
        const ctx = canvas.getContext('2d'); ctx.drawImage(video,0,0,canvas.width,canvas.height);
        const blob = await new Promise(resolve => canvas.toBlob(resolve,'image/jpeg',.72));
        if (!blob) throw new Error('Camera frame could not be captured.');
        if (blob.size > 350 * 1024) throw new Error('Camera image is too large. Move closer and retry.');
        return blob;
    }
    async function capture() {
        if (state !== 'FACE_SEARCH' || !ticket) return;
        const id = flowId;
        captureButton.disabled = true;
        try {
            if (detector) {
                const faces = await detector.detect(video);
                if (!isCurrent(id)) return;
                if (faces.length !== 1) throw new Error(faces.length ? 'Multiple faces detected. Keep only one person in view.' : 'No face detected. Move into the oval.');
                const box = faces[0].boundingBox;
                const ratio = box.width / video.videoWidth;
                if (ratio < .17) throw new Error('Face is too far away. Move closer.');
                if (ratio > .75) throw new Error('Face is too close. Move back.');
                if (box.x < 0 || box.x + box.width > video.videoWidth) throw new Error('Keep your whole face inside the frame.');
            }
            setState('LIVENESS_CHECK'); guidance.textContent = 'Perform the challenge now. Hold the phone still while five live frames are captured.';
            const data = new FormData(); data.append('verification_id',ticket.verification_id); data.append('nonce',ticket.nonce);
            for (let i=0;i<5;i++) { if (!isCurrent(id)) return; guidance.textContent = `Live challenge running · frame ${i+1} of 5`; const frame = await captureFrame(); if (!isCurrent(id)) return; data.append(`frames[${i}]`,frame,`frame-${i}.jpg`); if (i<4) await new Promise(resolve => setTimeout(resolve,650)); }
            if (!isCurrent(id)) return;
            setState('MATCHING'); guidance.textContent = 'Verifying identity securely…';
            const body = await jsonResponse(await fetch(confirmUrl,{method:'POST',credentials:'same-origin',headers:{'X-CSRF-TOKEN':csrf,Accept:'application/json'},body:data}));
            if (!isCurrent(id)) return;
            setState('SUCCESS'); reset(); showResult('Identity verified', `Attendance recorded for ${body.student_name}.`, 'success'); studentInput.value = ''; await loadSessions();
        } catch (error) { if (!isCurrent(id)) return; if (state === 'FACE_SEARCH' && !error.status) { guidance.textContent = error.message; captureButton.disabled = false; return; } fail(error.message || 'Network or verification failure. Please try again.',error.status); }
    }
    $('peerStart').addEventListener('click',start);
    captureButton.addEventListener('click',capture);
    $('peerCancel').addEventListener('click',() => { reset(); result.hidden = true; });
    window.addEventListener('pagehide',reset);
    document.addEventListener('visibilitychange',() => { if (document.hidden && state !== 'IDLE') { reset(); showResult('Verification paused', 'The camera was released when you left this page. Start again to continue.', 'error'); } });
    loadSessions();
})();
</script>
@endpush
