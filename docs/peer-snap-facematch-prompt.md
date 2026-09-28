# Engineering Prompt: Peer-Snap FaceMatch Attendance System

```markdown
You are an expert Principal Full-Stack Engineer and Computer Vision Specialist. 

Your task is to design and implement a secure, zero-hardware alternative attendance workflow called "Peer-Snap FaceMatch" for a mobile-first school attendance platform.

---

### 1. Context & The Problem
In our school attendance system, students normally authenticate using their own mobile phones (via dynamic rotating QR codes, GPS geofencing, and WebAuthn passkeys). 

**Scenario:** A student arrives at school and is physically present in the classroom, but has forgotten, lost, or has a dead battery on their mobile phone. 
**The Flaw to Avoid:** Conventional backup methods (like entering a Student ID + 4-digit PIN) are fundamentally insecure because an absent student staying home in their dorm can simply text their credentials to a friend in class to get marked present ("buddy punching").

---

### 2. The Objective
Implement **"Peer-Snap FaceMatch"**: A decentralized biometric fallback that temporarily converts an in-classroom classmate’s smartphone into an AI-powered biometric verification station. It must verify the physical presence of the phoneless student in real time with ZERO manual effort required from the teacher, while completely eliminating proxy check-in fraud.

---

### 3. Core Functional Requirements & Workflow

#### Step 1: Voucher Eligibility & Anti-Syndicate Gate
* A host student (**Student 2**, who has their phone) opens the class attendance page.
* The button **"Check in for a Classmate"** is unlocked ONLY if:
  1. Student 2 has already verified their own attendance for the active session.
  2. Student 2 is physically located within the session's geofence / classroom Wi-Fi subnet.
  3. Student 2 has not exceeded the strict rate limit of **maximum 2 vouches per session** (prevents unauthorized check-in syndicates).

#### Step 2: Identification & Biometric Vector Retrieval
* Student 2 taps "Check in for a Classmate" and hands their phone to the phoneless student (**Student 1**).
* Student 1 inputs their **Student ID Number** (e.g., `2024-0012`).
* The client web app sends an API call: `GET /api/attendance/students/{id}/face-descriptor`.
* The server responds with Student 1's pre-enrolled **mathematical facial embedding vector** (a 128-d or 512-d float array).
* *Privacy Constraint:* The server NEVER sends raw photos of Student 1 to Student 2's device; only the mathematical descriptor vector is downloaded into volatile browser memory.

#### Step 3: Hardware Camera Lock & Face Framing
* The app initiates direct camera access using `navigator.mediaDevices.getUserMedia({ video: { facingMode: "user" } })`.
* File/gallery uploads (`<input type="file">`) are strictly disabled; only live hardware video streams are accepted.
* The UI renders an oval target guide with live instructions (*"Align face inside the oval"*, *"Hold steady"*).
* Screen brightness automatically boosts to 100% with a white bezel overlay to provide soft fill-light in dark classrooms.

#### Step 4: Anti-Spoof Liveness Detection
* Before extracting facial features, the on-device AI model (e.g., MediaPipe Face Landmarker or TensorFlow.js running via WebAssembly) executes client-side anti-spoof checks:
  1. **Passive Liveness:** Verifies 3D facial mesh contour curvature and natural micro-movements (blinking/micro-head tremor).
  2. **Screen-Replay Defense:** Analyzes frame texture frequency to detect digital screen moiré patterns or monitor glare (blocking attempts to hold up an iPad/phone displaying a Facebook photo of the absent student).

#### Step 5: Automated AI Face Matching
* Once a live face is detected inside the oval, the model extracts the live facial embedding vector.
* Computes the Cosine Similarity against the enrolled database template:
  $$\text{Cosine Similarity} = \frac{A \cdot B}{\|A\| \|B\|}$$
* **Evaluation Criteria:**
  * **Match Score $\ge 0.85$ (85%):** Instant verification. Screen turns green: *"Identity Verified! Welcome, Maria."*
  * **Match Score $< 0.85$:** Instant rejection. Screen turns red: *"Face does not match Student ID record."*
  * **Rate Limit:** 3 failed attempts locks the Student ID from peer verification for 30 minutes to prevent brute-force impersonation.

#### Step 6: Backend Recording & Real-Time Sync
* The app issues a signed POST request: `POST /api/attendance/peer-biometric/confirm` with payload:
  `{ student_id, voucher_student_id, session_id, match_score, liveness_passed: true }`
* The backend writes to the attendance database:
  `status = 'present'`, `verification_channel = 'peer_biometric'`, `is_provisional = false`.
* Broadcasts an event over WebSockets (Laravel Reverb / Pusher):
  The Teacher Attendance Dashboard updates automatically in real time without pausing the lecture:
  `[✓ Present] Maria Santos — Peer Biometric (Vouched by Alex Cruz, 94.2% match)`

---

### 4. Anti-Fraud & Security Matrix
Ensure your solution structurally defends against these specific attack scenarios:
1. **The "Text My ID" Attack (Dorm Room Cheating):**
   * Absent student texts their ID to a friend in class.
   * *Defense:* The friend enters the ID, but the camera turns on and demands Student 1's live face. The absent student cannot transport their physical face through text.
2. **The "Scanning Own Face" Attack:**
   * Host student enters Student 1's ID but points the camera at themselves.
   * *Defense:* AI compares the live face vector against Student 1's enrolled master template. Match score will be ~20% $\rightarrow$ immediate failure.
3. **The "Photo-of-a-Photo" Attack:**
   * Host student holds up another phone showing an Instagram photo of the absent student.
   * *Defense:* Liveness detection fails due to lack of 3D depth, absence of blinking, and screen moiré pattern detection.
4. **Voucher Syndicate Farming:**
   * One student acts as an unauthorized check-in kiosk for 10 friends.
   * *Defense:* Hard cap of 2 vouches per host phone per session.

---

### 5. Technical Stack & Implementation Requirements
* **Backend:** PHP / Laravel with clean service-repository pattern, form requests, and robust validation.
* **Frontend:** Vanilla JS / Alpine.js / PWA using HTML5 Canvas and `getUserMedia`.
* **Computer Vision Engine:** Lightweight on-device WebAssembly solution (`face-api.js` or Google MediaPipe Face Detection + Face Landmarker).
* **Real-Time Layer:** Laravel WebSockets / Reverb / Echo.
* **Database (PostgreSQL / MySQL / SQLite):**
  * Migration for `peer_vouch_requests` (session_id, requester_id, voucher_id, match_score, status, timestamps).
  * Update `attendances` table with `verification_channel` enum (`'mobile_qr'`, `'peer_biometric'`, `'teacher_manual'`).
* **Privacy & GDPR/FERPA Compliance:**
  * Zero permanent storage of raw webcam photos. Only mathematical vector embeddings are stored.
  * Any ephemeral diagnostic image taken during flagged failures must be encrypted and scheduled for automatic permanent deletion within 24 hours via an Artisan scheduled worker.

---

### 6. Deliverables Expected
1. **Architecture & Component Overview:** Flowchart and component interaction description.
2. **Database Migrations:** Clean Laravel migration files.
3. **Frontend Client-Side Script:** WebRTC camera initialization, oval overlay, MediaPipe/face-api integration, and match evaluation.
4. **Backend Controller & Service:** `PeerBiometricAttendanceController` with request validation, vector distance calculation, rate-limiting, and event broadcasting.
5. **Teacher Dashboard Blade / Live Component:** WebSocket listener that updates attendance in real-time.
```
