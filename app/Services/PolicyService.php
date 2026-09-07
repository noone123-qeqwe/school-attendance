<?php

namespace App\Services;

use App\Models\Setting;
use Carbon\Carbon;

class PolicyService
{
    const DEFAULT_PRIVACY_VERSION = '1.0';
    const DEFAULT_PRIVACY_EFFECTIVE_DATE = '2024-09-01';

    const DEFAULT_TERMS_VERSION = '1.0';
    const DEFAULT_TERMS_EFFECTIVE_DATE = '2024-09-01';

    /**
     * Get Privacy Policy details
     */
    public function getPrivacyPolicy(): array
    {
        $version = Setting::get('privacy_policy_version', self::DEFAULT_PRIVACY_VERSION);
        $effectiveDate = Setting::get('privacy_policy_effective_date', self::DEFAULT_PRIVACY_EFFECTIVE_DATE);
        $updatedAt = Setting::get('privacy_policy_updated_at', self::DEFAULT_PRIVACY_EFFECTIVE_DATE);
        $customContent = Setting::get('privacy_policy_content');

        return [
            'title'          => 'Smart Classroom Attendance System — Privacy Notice',
            'version'        => $version,
            'effective_date' => Carbon::parse($effectiveDate)->format('F j, Y'),
            'updated_at'     => Carbon::parse($updatedAt)->format('F j, Y'),
            'raw_effective'  => $effectiveDate,
            'raw_updated'    => $updatedAt,
            'content'        => $customContent ?: $this->getDefaultPrivacyContent(),
            'is_custom'      => !empty($customContent),
        ];
    }

    /**
     * Get Terms & Conditions details
     */
    public function getTermsAndConditions(): array
    {
        $version = Setting::get('terms_version', self::DEFAULT_TERMS_VERSION);
        $effectiveDate = Setting::get('terms_effective_date', self::DEFAULT_TERMS_EFFECTIVE_DATE);
        $updatedAt = Setting::get('terms_updated_at', self::DEFAULT_TERMS_EFFECTIVE_DATE);
        $customContent = Setting::get('terms_content');

        return [
            'title'          => 'Smart Classroom Attendance System — Terms & Conditions',
            'version'        => $version,
            'effective_date' => Carbon::parse($effectiveDate)->format('F j, Y'),
            'updated_at'     => Carbon::parse($updatedAt)->format('F j, Y'),
            'raw_effective'  => $effectiveDate,
            'raw_updated'    => $updatedAt,
            'content'        => $customContent ?: $this->getDefaultTermsContent(),
            'is_custom'      => !empty($customContent),
        ];
    }

    /**
     * Update Privacy Policy in settings
     */
    public function updatePrivacyPolicy(string $content, string $version, string $effectiveDate): void
    {
        Setting::updateOrCreate(['key' => 'privacy_policy_content'], ['value' => $content]);
        Setting::updateOrCreate(['key' => 'privacy_policy_version'], ['value' => $version]);
        Setting::updateOrCreate(['key' => 'privacy_policy_effective_date'], ['value' => $effectiveDate]);
        Setting::updateOrCreate(['key' => 'privacy_policy_updated_at'], ['value' => Carbon::now()->toDateString()]);
    }

    /**
     * Update Terms & Conditions in settings
     */
    public function updateTermsAndConditions(string $content, string $version, string $effectiveDate): void
    {
        Setting::updateOrCreate(['key' => 'terms_content'], ['value' => $content]);
        Setting::updateOrCreate(['key' => 'terms_version'], ['value' => $version]);
        Setting::updateOrCreate(['key' => 'terms_effective_date'], ['value' => $effectiveDate]);
        Setting::updateOrCreate(['key' => 'terms_updated_at'], ['value' => Carbon::now()->toDateString()]);
    }

    /**
     * Reset policies to defaults
     */
    public function resetToDefaults(string $type = 'all'): void
    {
        if ($type === 'privacy' || $type === 'all') {
            Setting::whereIn('key', [
                'privacy_policy_content',
                'privacy_policy_version',
                'privacy_policy_effective_date',
                'privacy_policy_updated_at'
            ])->delete();
        }

        if ($type === 'terms' || $type === 'all') {
            Setting::whereIn('key', [
                'terms_content',
                'terms_version',
                'terms_effective_date',
                'terms_updated_at'
            ])->delete();
        }
    }

    /**
     * Curated, system-specific default Privacy Notice
     */
    public function getDefaultPrivacyContent(): string
    {
        return <<<'HTML'
<div class="policy-section" id="introduction">
    <h3>1. Introduction</h3>
    <p>Welcome to the <strong>Smart Classroom Attendance System</strong> ("the System"). This system is an institutional educational platform developed and operated by <strong>Osmeña Colleges</strong> ("the Institution", "we", "us", or "our") to record, monitor, verify, and report student classroom attendance securely and efficiently.</p>
    <p>We are dedicated to safeguarding the privacy and personal data of our students, faculty instructors, parents, guardians, and administrative staff. This Privacy Notice describes our practices regarding the collection, use, storage, protection, retention, and disclosure of personal information processed by the System.</p>
</div>

<div class="policy-section" id="purpose">
    <h3>2. Purpose of this Privacy Notice</h3>
    <p>The purpose of this Privacy Notice is to provide transparent and plain-language information regarding how personal information is processed in accordance with the <strong>Philippine Data Privacy Act of 2012 (Republic Act No. 10173)</strong>, its Implementing Rules and Regulations (IRR), and applicable circulars and advisories issued by the National Privacy Commission (NPC) of the Philippines.</p>
    <p>By using the Smart Classroom Attendance System, registering an account, scanning attendance QR codes, or submitting excuse documentation, you acknowledge that your personal information will be handled as described in this Notice.</p>
</div>

<div class="policy-section" id="information-collected">
    <h3>3. Information We Collect</h3>
    <p>We collect only personal data that is directly necessary, relevant, and proportional to fulfilling legitimate educational attendance tracking and academic record-keeping purposes. We do not collect unnecessary personal details.</p>
    
    <h4>A. Student Information</h4>
    <ul>
        <li><strong>Identity & Profile:</strong> Full name (first name, middle name, surname), official 7-digit institutional Student Number (e.g., <code>0703250</code>), enrolled academic program/course (e.g., BSCS), current year level (1st–4th year), enrolled semester, assigned section, and optional profile photo.</li>
        <li><strong>Contact Details:</strong> Institutional or personal email address, mobile phone number, and verified parent/guardian email address.</li>
    </ul>

    <h4>B. Teacher & Instructor Information</h4>
    <ul>
        <li><strong>Identity & Faculty Profile:</strong> Full name, employee identification number (e.g., <code>T-2024-001</code>), department/college (e.g., College of Computer Studies), academic position, specialization, and assigned subjects/schedules.</li>
        <li><strong>Contact Details:</strong> Institutional email address and faculty contact details.</li>
    </ul>

    <h4>C. Administrator Information</h4>
    <ul>
        <li>Full name, administrative role/sub-role, institutional email address, administrative activity audit logs, and authorized IP whitelist configurations.</li>
    </ul>

    <h4>D. Account, Authentication & Security Information</h4>
    <ul>
        <li><strong>Credentials:</strong> Account usernames, email addresses, and securely salted, one-way hashed passwords (using bcrypt with a minimum work factor of 12 rounds). Plaintext passwords are never stored or accessible by anyone.</li>
        <li><strong>Biometric / WebAuthn Credentials:</strong> For users enabling "Sign in with biometrics" (Fingerprint / Face ID / Passkeys), the system stores only standard WebAuthn public keys, credential IDs, and signature counters. <em>Physical biometric samples (fingerprint scans or facial templates) remain strictly inside your device's hardware security enclave and are never transmitted to, processed by, or stored on our servers.</em></li>
        <li><strong>Session & Security Data:</strong> Active session tokens, remember-me tokens, failed login attempt counters, and timestamped account lockout flags to protect accounts from brute-force intrusions.</li>
    </ul>

    <h4>E. Email Verification and One-Time Passcode (OTP) Information</h4>
    <ul>
        <li>6-digit numerical verification codes generated for user registration, password resets, and administrator two-factor authentication (2FA).</li>
        <li>Passcode generation timestamps, 10-minute expiry windows, attempt tracking, and request rate-limiting cooldown timers.</li>
    </ul>

    <h4>F. Attendance Records & Class Session Data</h4>
    <ul>
        <li><strong>Session Information:</strong> Subject code, subject title, assigned instructor, academic year, semester, and scheduled class hours.</li>
        <li><strong>Attendance Event Data:</strong> Specific date, check-in timestamp (time-in), check-out timestamp (time-out), recorded attendance status (<em>Present</em>, <em>Late</em>, <em>Absent</em>, or <em>Excused</em>), and late threshold calculations.</li>
        <li><strong>Verification Method:</strong> Recording whether attendance was verified via Dynamic QR Code, GPS Geofencing, RFID tag, Kiosk PIN, or manual teacher override.</li>
    </ul>

    <h4>G. Dynamic QR Code & Anti-Fraud Session Tokens</h4>
    <ul>
        <li>When instructors open an attendance session, the system generates dynamic, rolling session tokens that rotate automatically every few seconds. This prevents screenshot sharing, remote distribution, or proxy scanning by absent individuals.</li>
    </ul>

    <h4>H. Geolocation & Technical Information</h4>
    <ul>
        <li><strong>GPS Coordinates:</strong> When scanning a classroom QR code, the system requests browser location permissions to verify physical presence within the designated campus/classroom perimeter (geofence radius). The system records latitude, longitude, and accuracy radius strictly at the instant of scan. <em>The system does NOT track your continuous or background location.</em></li>
        <li><strong>Device Binding Data:</strong> To prevent proxy attendance from unauthorized devices, the system records a cryptographic device hash, device name, operating system / user-agent string, and IP address.</li>
    </ul>

    <h4>I. Excuse Submissions, Letters & Attachments</h4>
    <ul>
        <li>Student-submitted absence justifications, text descriptions, uploaded attachment files (such as medical certificates or formal excuse letters), reviewer notes, and approval/rejection timestamps.</li>
    </ul>
</div>

<div class="policy-section" id="how-we-use">
    <h3>4. How We Use Personal Information</h3>
    <p>All personal data is processed lawfully and transparently for legitimate academic, institutional, and administrative functions, including:</p>
    <ul>
        <li>Verifying student identity and confirming physical attendance during scheduled classes.</li>
        <li>Calculating attendance rates, tardiness metrics, and course completion eligibility.</li>
        <li>Triggering automated early warnings and intervention notices for students nearing excessive absence limits (e.g., 75% threshold).</li>
        <li>Notifying parents and legal guardians regarding unexcused absences or urgent school notices.</li>
        <li>Processing, evaluating, and documenting student absence excuse applications and supporting documentation.</li>
        <li>Preventing academic fraud, proxy attendance, location spoofing, and unauthorized account access.</li>
        <li>Generating aggregated, de-identified statistical reports for academic departments, deans, and institutional quality assurance bodies.</li>
    </ul>
</div>

<div class="policy-section" id="storage-security">
    <h3>5. Storage, Security, and Protection</h3>
    <p>We maintain strict administrative, technical, and physical safeguards to ensure data integrity and prevent unauthorized access, disclosure, alteration, or loss:</p>
    <ul>
        <li><strong>Database Encryption & Access Control:</strong> Relational and SQLite databases reside in secured server environments with restricted access limited exclusively to authorized system administrators.</li>
        <li><strong>Cryptographic Password Protection:</strong> All passwords are hashed using bcrypt with at least 12 rounds of salt.</li>
        <li><strong>Secure Communications:</strong> Data transmitted between user devices and our servers is encrypted in transit using industry-standard Transport Layer Security (TLS / HTTPS).</li>
        <li><strong>Content Security Policy (CSP):</strong> Strict Content Security Policy headers utilizing per-request cryptographic nonces to mitigate Cross-Site Scripting (XSS) and code injection vectors.</li>
        <li><strong>Defense Against Brute Force:</strong> Intelligent account lockout services and rate limiters monitor authentication endpoints and temporarily throttle suspicious activities.</li>
        <li><strong>Device Binding & Anti-Spoofing:</strong> Device fingerprinting and rotating QR session challenges prevent unauthorized multi-device proxy attendance.</li>
    </ul>
</div>

<div class="policy-section" id="retention">
    <h3>6. Data Retention and Archival</h3>
    <p>Personal and attendance data is retained only for as long as necessary to fulfill the educational purposes for which it was collected, in compliance with Commission on Higher Education (CHED) regulations and school record-keeping policies:</p>
    <ul>
        <li><strong>Active Attendance Records:</strong> Retained for the duration of the current academic term and through the completion of grading appeals.</li>
        <li><strong>Academic History & Logs:</strong> Archived in accordance with statutory academic transcript and institutional accountability requirements.</li>
        <li><strong>Temporary Verification Tokens:</strong> OTP codes expire after 10 minutes and are permanently purged upon use or expiration.</li>
        <li><strong>Soft Deletes & Archival:</strong> Inactive records are archived using soft-deletion safeguards, preventing accidental permanent destruction while restricting active access.</li>
    </ul>
</div>

<div class="policy-section" id="access-sharing">
    <h3>7. Who Can Access Personal Information</h3>
    <p>Access to personal data within the system is strictly segmented based on Role-Based Access Controls (RBAC):</p>
    <ul>
        <li><strong>Students:</strong> Can view only their own profile details, enrolled subjects, personal attendance timeline, submitted excuses, and personal alerts.</li>
        <li><strong>Teachers & Instructors:</strong> Can view only attendance rosters, real-time check-in logs, and excuse requests for students enrolled in their assigned classes.</li>
        <li><strong>Parents & Guardians:</strong> Can view only attendance records, calendars, and alerts concerning their verified, linked child.</li>
        <li><strong>Administrators:</strong> Maintain system oversight, master lists, user accounts, and audit logs necessary for administrative operation.</li>
    </ul>

    <h4>Data Sharing and Third-Party Services</h4>
    <p>We <strong>do not sell, rent, trade, or commercialize</strong> your personal data. Personal data is never shared with third-party marketers or advertisers. Data is processed through trusted infrastructure services strictly necessary for system operations:</p>
    <ul>
        <li><strong>Transactional Email Services (SMTP / Brevo):</strong> Used solely to transmit email verification passcodes (OTPs), password recovery links, and official attendance notifications.</li>
        <li><strong>OpenStreetMap / Leaflet:</strong> Used to render classroom geofence maps for configuration. No personal identity or individual scan coordinates are transmitted to OpenStreetMap.</li>
        <li><strong>Real-Time WebSockets (Laravel Reverb / Pusher):</strong> Used for instant live attendance board updates within the classroom. Broadcasts transmit anonymous event triggers.</li>
        <li><strong>UI Avatars:</strong> Used to generate fallback initials avatar images when no custom photo is uploaded.</li>
    </ul>
</div>

<div class="policy-section" id="privacy-rights">
    <h3>8. User Privacy Rights (Data Privacy Act of 2012)</h3>
    <p>Under Republic Act No. 10173, students, parents, faculty, and staff have recognized statutory rights concerning their personal data:</p>
    <ul>
        <li><strong>Right to be Informed:</strong> You have the right to know what personal information is collected, how it is used, and who can access it.</li>
        <li><strong>Right to Access:</strong> You may inspect and review your personal profile and attendance history directly through your account portal.</li>
        <li><strong>Right to Correct / Rectification:</strong> You may request the correction of inaccurate, outdated, or incomplete personal data or submit an attendance correction request through the official portal.</li>
        <li><strong>Right to Object:</strong> You may object to data processing that is not mandated by institutional academic regulations or statutory requirements.</li>
        <li><strong>Right to Erasure or Blocking:</strong> You may request deletion or suspension of data where legitimate grounds exist, subject to the Institution's legal obligation to preserve official academic and grading records.</li>
        <li><strong>Right to Data Portability:</strong> You may request a digital copy of your attendance history in standard electronic format.</li>
        <li><strong>Right to Damages:</strong> You have the right to be indemnified for any damages sustained due to unlawful, false, or unauthorized processing.</li>
    </ul>
</div>

<div class="policy-section" id="exercising-rights">
    <h3>9. How to Exercise Your Rights and File Inquiries</h3>
    <p>To exercise any of your statutory privacy rights, request data corrections, or raise questions regarding this Privacy Notice, please contact our institutional Data Protection Office:</p>
    <div class="contact-box">
        <p><strong>Osmeña Colleges — Data Protection Office</strong></p>
        <p>Email: <a href="mailto:admin@osmena.edu">admin@osmena.edu</a></p>
        <p>Address: Osmeña Colleges, Masbate City, Philippines</p>
        <p>Office Hours: Monday – Friday, 8:00 AM – 5:00 PM (PST)</p>
    </div>
    <p>If you believe that your privacy rights have been violated and we have failed to resolve your concern, you have the right to lodge a formal complaint with the <strong>National Privacy Commission (NPC)</strong> at <a href="https://privacy.gov.ph" target="_blank" rel="noopener noreferrer">https://privacy.gov.ph</a>.</p>
</div>

<div class="policy-section" id="children-privacy">
    <h3>10. Children and Minor Students' Privacy</h3>
    <p>When students under the age of 18 enroll and use the system, the Institution facilitates parental/guardian oversight through dedicated parent accounts. Parents and guardians may monitor attendance records, receive absence alerts, and submit excuse letters on behalf of minor students.</p>
</div>

<div class="policy-section" id="cookies-sessions">
    <h3>11. Cookies, Sessions & Local Storage</h3>
    <p>The system uses strictly necessary session cookies (<code>laravel_session</code> and <code>XSRF-TOKEN</code>) to authenticate users, protect against Cross-Site Request Forgery (CSRF), and maintain session integrity. We do NOT use third-party advertising cookies, behavioral tracking cookies, or commercial analytics scripts.</p>
</div>

<div class="policy-section" id="changes">
    <h3>12. Changes to this Privacy Notice</h3>
    <p>We may periodically update this Privacy Notice to reflect enhancements to system functionality, technological updates, or amendments to statutory regulations. When material revisions occur, the updated document will be published with a revised "Last Updated" date and version number. Continued use of the system following notification of updates constitutes acknowledgment of the revised terms.</p>
</div>
HTML;
    }

    /**
     * Curated, system-specific default Terms & Conditions
     */
    public function getDefaultTermsContent(): string
    {
        return <<<'HTML'
<div class="policy-section" id="introduction">
    <h3>1. Introduction & Acceptance of Terms</h3>
    <p>These Terms & Conditions ("Terms") constitute a legally binding agreement between you ("User", "you", or "your") and <strong>Osmeña Colleges</strong> ("the Institution", "we", "us", or "our") governing your access to and use of the <strong>Smart Classroom Attendance System</strong> ("the System").</p>
    <p>By registering an account, logging into the portal, scanning attendance QR codes, or accessing any service provided by the System, you affirm that you have read, understood, and agreed to be bound by these Terms. If you do not agree to these Terms, you must not access or use the System.</p>
</div>

<div class="policy-section" id="eligibility">
    <h3>2. Eligibility and Authorized Users</h3>
    <p>Access to the System is strictly restricted to authorized members of the academic community:</p>
    <ul>
        <li><strong>Enrolled Students:</strong> Individuals officially admitted and currently enrolled in courses offered by the Institution.</li>
        <li><strong>Faculty & Instructors:</strong> Officially appointed teachers and academic personnel authorized to conduct classes and manage attendance.</li>
        <li><strong>Parents & Legal Guardians:</strong> Verified parents or legal guardians linked to enrolled students through authorized student numbers.</li>
        <li><strong>Institutional Administrators:</strong> Designated IT and administrative staff tasked with managing system operations and compliance.</li>
    </ul>
    <p>Unauthorized access by third parties is strictly prohibited and subject to legal prosecution under the <strong>Cybercrime Prevention Act of 2012 (Republic Act No. 10175)</strong>.</p>
</div>

<div class="policy-section" id="account-security">
    <h3>3. Account Security and User Responsibilities</h3>
    <p>Every user is responsible for maintaining the security and integrity of their account:</p>
    <ul>
        <li><strong>Confidentiality:</strong> You must keep your login credentials, passwords, and verification passcodes (OTPs) strictly confidential. You must never share, disclose, or transfer your login credentials to any other individual.</li>
        <li><strong>Device Safeguards:</strong> You are responsible for safeguarding devices registered to your account. You must ensure that biometric credentials (fingerprints or facial recognition) registered on your device belong exclusively to you.</li>
        <li><strong>Notification of Breach:</strong> You must immediately report any lost device, unauthorized account access, or suspected credential compromise to the school administration at <a href="mailto:admin@osmena.edu">admin@osmena.edu</a>.</li>
        <li><strong>Accountability:</strong> You are fully responsible for all attendance scans, excuse submissions, and activities conducted through your authenticated account.</li>
    </ul>
</div>

<div class="policy-section" id="anti-fraud">
    <h3>4. Attendance Rules & Strict Anti-Fraud Policy</h3>
    <p>The Smart Classroom Attendance System is an official institutional instrument for recording academic attendance. The Institution maintains a <strong>ZERO-TOLERANCE POLICY</strong> regarding attendance fraud, dishonesty, and falsification.</p>

    <h4>A. Strict Prohibitions</h4>
    <ul>
        <li><strong>Proxy Attendance:</strong> You must never scan an attendance QR code, sign in, or log attendance on behalf of another student.</li>
        <li><strong>Remote Sharing of QR Codes:</strong> You must never take screenshots, photographs, video recordings, or digital copies of dynamic attendance QR codes to send to individuals who are not physically present in class.</li>
        <li><strong>GPS Spoofing & Location Emulation:</strong> You must never use mock location software, GPS spoofers, VPNs, developer emulation tools, or altered device configurations to bypass or fabricate classroom geolocation boundaries.</li>
        <li><strong>Credential Sharing:</strong> You must never lend your account, password, or device binding token to another student to allow them to falsify attendance.</li>
        <li><strong>Forged Excuse Documentation:</strong> You must never submit fabricated, altered, or fraudulent medical certificates, parent signatures, or excuse letters. Falsification of public and school documents is a serious academic offense.</li>
        <li><strong>System Tampering:</strong> You must never attempt to decompile, reverse-engineer, probe, scrape, flood, or bypass system APIs, rate limits, or verification protocols.</li>
    </ul>

    <h4>B. Disciplinary Consequences</h4>
    <p>Any violation of the Anti-Fraud Policy will result in immediate investigation by the <strong>Student Affairs Office / Prefect of Discipline</strong>. Penalties include, but are not limited to:</p>
    <ul>
        <li>Immediate invalidation and cancellation of fraudulent attendance records (marked as unexcused absence).</li>
        <li>Formal reprimand or disciplinary probation placed on the student's permanent academic record.</li>
        <li>Automatic deduction from class participation or failing grade ("5.0" / "F") in the subject, subject to instructor and departmental policy.</li>
        <li>Suspension, non-readmission, or expulsion from the Institution pursuant to the Osmeña Colleges Student Handbook.</li>
        <li>Criminal prosecution where acts constitute violations of RA 10175 (Cybercrime Prevention Act) or Revised Penal Code articles on falsification.</li>
    </ul>
</div>

<div class="policy-section" id="attendance-protocol">
    <h3>5. Attendance Scanning Protocols</h3>
    <p>To ensure accurate and valid attendance verification, students must follow standard protocols:</p>
    <ul>
        <li><strong>Physical Classroom Presence:</strong> Students must be physically present inside the designated classroom or lecture hall during the scanning window.</li>
        <li><strong>Active Session Window:</strong> Scans must be completed while the instructor's dynamic attendance session is actively broadcasting. Expired tokens cannot be redeemed.</li>
        <li><strong>Location Verification:</strong> Users must permit temporary browser GPS access when prompted so the system can verify campus proximity.</li>
        <li><strong>Tardiness Rules:</strong> Arrivals recorded after the instructor's configured grace period (e.g., 15 minutes past start time) will automatically be categorized as <em>Late</em> in accordance with course syllabi.</li>
        <li><strong>Device Binding:</strong> Each student account is bound to their verified personal mobile device. To register a new device due to replacement or loss, students must submit a device reset request to their instructor or the IT department.</li>
    </ul>
</div>

<div class="policy-section" id="system-availability">
    <h3>6. System Availability & Maintenance</h3>
    <p>While we endeavor to provide continuous and uninterrupted system availability throughout academic hours, we do not warrant that the system will always be error-free or uninterrupted:</p>
    <ul>
        <li><strong>Scheduled Maintenance:</strong> Maintenance windows are scheduled outside peak school hours whenever practicable, with advance notice posted on the system dashboard.</li>
        <li><strong>Offline & Fallback Procedures:</strong> In the event of temporary network outages, device malfunctions, or institutional power disruptions, instructors are authorized to record attendance manually or conduct roll calls without penalizing students.</li>
    </ul>
</div>

<div class="policy-section" id="attendance-disputes">
    <h3>7. Official Records & Dispute Resolution</h3>
    <p>Attendance records compiled by the System constitute official institutional records:</p>
    <ul>
        <li>Students are responsible for reviewing their attendance timelines regularly.</li>
        <li>If an error or discrepancy occurs (e.g., an unrecorded scan due to camera failure or system timeout), students must submit an <strong>Attendance Correction Request</strong> through the portal within <strong>five (5) school days</strong> of the incident.</li>
        <li>The assigned class instructor holds final academic authority to approve or deny correction requests based on class logs and physical corroboration.</li>
    </ul>
</div>

<div class="policy-section" id="account-termination">
    <h3>8. Account Suspension and Termination</h3>
    <p>The Institution reserves the right to suspend, restrict, or deactivate any account at its sole discretion, without prior notice, in the event of:</p>
    <ul>
        <li>Graduation, formal withdrawal, or cessation of active enrollment.</li>
        <li>Breach of these Terms or the Institution's Student Handbook and Code of Conduct.</li>
        <li>Security threats, automated brute-force attacks, or malicious activities originating from the account.</li>
    </ul>
</div>

<div class="policy-section" id="intellectual-property">
    <h3>9. Intellectual Property Rights</h3>
    <p>All software code, database architecture, user interfaces, branding, graphics, logos, documentation, and design assets comprising the Smart Classroom Attendance System are the exclusive intellectual property of <strong>Osmeña Colleges</strong> and its authorized developers. You are granted a limited, personal, non-exclusive, non-transferable license to access the system strictly for educational attendance purposes.</p>
</div>

<div class="policy-section" id="limitation-liability">
    <h3>10. Limitation of Liability</h3>
    <p>To the fullest extent permitted by law, Osmeña Colleges, its board of trustees, officers, faculty members, and developers shall not be held liable for:</p>
    <ul>
        <li>Personal device failures, dead batteries, corrupted operating systems, or unsupported third-party web browsers.</li>
        <li>Personal cellular network or data connectivity failures preventing timely attendance scans.</li>
        <li>Unexcused absences resulting from student neglect, tardiness, or failure to follow scanning protocols.</li>
        <li>Unauthorized account access resulting from the user's failure to safeguard credentials or personal devices.</li>
    </ul>
</div>

<div class="policy-section" id="governing-law">
    <h3>11. Governing Law & Jurisdiction</h3>
    <p>These Terms shall be construed, interpreted, and governed in all respects in accordance with the laws of the <strong>Republic of the Philippines</strong>, including the Education Act of 1982, the Data Privacy Act of 2012 (RA 10173), and the Cybercrime Prevention Act of 2012 (RA 10175). Any dispute arising under these Terms shall be resolved exclusively within the competent courts of Masbate City, Philippines.</p>
</div>

<div class="policy-section" id="changes-terms">
    <h3>12. Modifications to Terms</h3>
    <p>The Institution reserves the right to amend or update these Terms as necessary to reflect academic policies or regulatory changes. Updated Terms will be published on the system with an updated version number and effective date. Continued access to the system following publication of revisions constitutes acceptance of the revised Terms.</p>
</div>

<div class="policy-section" id="contact-info">
    <h3>13. Contact & Inquiries</h3>
    <p>For questions or clarifications regarding these Terms & Conditions, please contact the administration office:</p>
    <div class="contact-box">
        <p><strong>Osmeña Colleges — Academic Affairs & IT Administration</strong></p>
        <p>Email: <a href="mailto:admin@osmena.edu">admin@osmena.edu</a></p>
        <p>Address: Osmeña Colleges, Masbate City, Philippines</p>
        <p>Office Hours: Monday – Friday, 8:00 AM – 5:00 PM (PST)</p>
    </div>
</div>
HTML;
    }
}
