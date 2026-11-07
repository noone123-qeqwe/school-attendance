# Face verification limits and privacy review

## Technical limits

The attendance matcher uses OpenCV YuNet face detection and SFace similarity against the student's existing profile photo. It now checks two or three samples for image quality, similarity, and face-box consistency. This is a consistency check only. It is not liveness detection or presentation attack detection (PAD); a printed photo, screen replay, or other presentation attack may still pass.

Do not use face matching as sole proof of identity. The student screen states this limitation, and administrators can require an existing WebAuthn authenticator, an already registered device, or both after a face match. If reliable PAD is required, evaluate a purpose-built PAD implementation with independent testing evidence (for example, an applicable ISO/IEC 30107-3 test report), then validate it against the school's actual devices, lighting, and attack scenarios before enabling it.

Camera samples are sent to the application for the match. The PHP matcher writes temporary files for the OpenCV process and attempts to remove them in a `finally` cleanup. This is best-effort cleanup, not a guarantee of secure erasure after operating-system or infrastructure failure. The attendance endpoint does not store the submitted frames or feature vectors in the database or ordinary application logs. The student's existing profile photo remains stored under the application's current profile-photo policy.

## School privacy review required

The school, as the data controller, should have its Data Protection Officer or qualified privacy counsel determine and document the lawful basis for this specific attendance use before enabling face verification. That review should cover notice, purpose limitation, proportionality, access controls, retention/deletion for profile photos and temporary processing artifacts, student/parent rights, vendor roles, and a practical non-face alternative. The application notice is not a substitute for the school's privacy notice or legal-basis decision.

The National Privacy Commission's published material says biometric processing remains subject to the Data Privacy Act and its implementing rules, and emphasizes transparency, legitimate purpose, proportionality, and security measures. See the [NPC advisory on biometric data processing](https://privacy.gov.ph/wp-content/uploads/2025/09/NPC-Advisory-Opinion-No.-2025-007_Redacted.pdf), the [Data Privacy Act of 2012](https://privacy.gov.ph/data%20-privacy-act/), and the NPC's [right to be informed guidance](https://privacy.gov.ph/the-right-to-be-informed/).
