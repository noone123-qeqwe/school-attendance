# OTP EMAIL FLOW - COMPLETE SYSTEM DIAGRAM

## USER REGISTRATION FLOW (STEP-BY-STEP)

```
┌─────────────────────────────────────────────────────────────────┐
│                        USER BROWSER                              │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  1. User enters email: janessa@gmail.com                   │ │
│  │  2. User enters password                                   │ │
│  │  3. User clicks "Verify Email" button                      │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ JavaScript captures email value
                              │ from input field (line 1304)
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                   FRONTEND (register.blade.php)                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  const email = document.getElementById('email').value;     │ │
│  │                                                            │ │
│  │  fetch('/otp/send-register', {                            │ │
│  │    method: 'POST',                                        │ │
│  │    body: JSON.stringify({                                 │ │
│  │      email: email  ← USER'S ACTUAL EMAIL                  │ │
│  │    })                                                      │ │
│  │  })                                                        │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ HTTP POST Request
                              │ {"email": "janessa@gmail.com"}
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                ROUTE: /otp/send-register (web.php)              │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  Route::post('/otp/send-register',                        │ │
│  │      [OtpController::class, 'sendRegisterOtp'])           │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│           CONTROLLER: OtpController::sendRegisterOtp()          │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  // Line 45-47                                            │ │
│  │  $emailClean = strtolower(trim($request->email));        │ │
│  │  // emailClean = "janessa@gmail.com"                     │ │
│  │                                                            │ │
│  │  $result = $this->otpService->sendOtp(                   │ │
│  │      $emailClean,        ← USER'S EMAIL                   │ │
│  │      'register',         ← PURPOSE                        │ │
│  │      null,                ← USER ID (not yet created)     │ │
│  │      null,                ← NAME                          │ │
│  │      $requestId           ← REQUEST ID                    │ │
│  │  );                                                        │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│              SERVICE: OtpService::sendOtp()                     │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  // Line 103-109                                          │ │
│  │  public function sendOtp(                                 │ │
│  │      string $email,           ← janessa@gmail.com         │ │
│  │      string $purpose,         ← 'register'                │ │
│  │      ?int $userId,                                        │ │
│  │      ?string $recipientName,                              │ │
│  │      ?string $requestId                                   │ │
│  │  )                                                         │ │
│  │                                                            │ │
│  │  // Line 110-111                                          │ │
│  │  $cleanEmail = strtolower(trim($email));                 │ │
│  │  // cleanEmail = "janessa@gmail.com"                     │ │
│  │                                                            │ │
│  │  // Line 158-164 - Enforce 30-second cooldown            │ │
│  │  $cooldown = Otp::getCooldownRemaining($cleanEmail, ...);│ │
│  │  if ($cooldown > 0) throw Exception(...);                │ │
│  │                                                            │ │
│  │  // Line 172 - Generate 6-digit OTP code                 │ │
│  │  $otp = Otp::generateForEmail($cleanEmail, ...);         │ │
│  │  // $otp->code = "123456" (random)                       │ │
│  │                                                            │ │
│  │  // Line 188-195 - Send email                            │ │
│  │  $delivery = $this->emailDeliveryService->sendOtp(       │ │
│  │      recipientEmail: $cleanEmail,  ← janessa@gmail.com   │ │
│  │      otpCode: $otp->code,          ← "123456"            │ │
│  │      purpose: $purpose,            ← "register"          │ │
│  │      recipientName: $recipientName ← "User"              │ │
│  │  );                                                        │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│         SERVICE: EmailDeliveryService::sendOtp()                │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  // Line 75-81                                            │ │
│  │  public function sendOtp(                                 │ │
│  │      string $recipientEmail,  ← janessa@gmail.com        │ │
│  │      string $otpCode,         ← "123456"                 │ │
│  │      string $purpose,         ← "register"               │ │
│  │      string $recipientName    ← "User"                   │ │
│  │  )                                                         │ │
│  │                                                            │ │
│  │  // PRIORITY 1: Try Resend HTTP API (if configured)      │ │
│  │  if (!empty($resendKey)) {                                │ │
│  │      sendViaResendHttp($recipientEmail, ...)             │ │
│  │  }                                                         │ │
│  │                                                            │ │
│  │  // PRIORITY 2: Try Brevo HTTP API (if configured)       │ │
│  │  if (!empty($brevoKey)) {                                 │ │
│  │      sendViaBrevoHttp($recipientEmail, ...)              │ │
│  │  }                                                         │ │
│  │                                                            │ │
│  │  // PRIORITY 3: Use SMTP                                 │ │
│  │  // Line 111-112                                          │ │
│  │  $mailable = new OtpMail($otpCode, $purpose, $recipientName);│
│  │  Mail::to($recipientEmail)->send($mailable);             │ │
│  │  //       ↑                                               │ │
│  │  //       └── DYNAMIC RECIPIENT (janessa@gmail.com)      │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    SMTP SERVER (Gmail)                          │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  From: osmenacolleges.attendance@gmail.com                │ │
│  │  To:   janessa@gmail.com  ← USER'S EMAIL                  │ │
│  │  Subject: Your Smart Classroom Verification Code          │ │
│  │  Body: Your OTP code is: 123456                           │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              │
                              │ Email delivered
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                  USER'S INBOX (janessa@gmail.com)               │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  📧 New Email from Smart Classroom Attendance System       │ │
│  │                                                            │ │
│  │  Your verification code is: 123456                        │ │
│  │  Valid for 10 minutes                                     │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

---

## MULTIPLE USERS SCENARIO

### Scenario: Three users register simultaneously

```
┌──────────────────────┐     ┌──────────────────────┐     ┌──────────────────────┐
│   USER A ENTERS:     │     │   USER B ENTERS:     │     │   USER C ENTERS:     │
│  alice@gmail.com     │     │  bob@yahoo.com       │     │  carol@outlook.com   │
└──────────────────────┘     └──────────────────────┘     └──────────────────────┘
          │                            │                            │
          │ POST /otp/send-register    │ POST /otp/send-register    │ POST /otp/send-register
          ▼                            ▼                            ▼
    ┌──────────┐               ┌──────────┐               ┌──────────┐
    │ Backend  │               │ Backend  │               │ Backend  │
    │ receives │               │ receives │               │ receives │
    └──────────┘               └──────────┘               └──────────┘
          │                            │                            │
          │ OtpService                 │ OtpService                 │ OtpService
          │ sendOtp(                   │ sendOtp(                   │ sendOtp(
          │   "alice@gmail.com"        │   "bob@yahoo.com"          │   "carol@outlook.com"
          │ )                          │ )                          │ )
          ▼                            ▼                            ▼
    ┌──────────┐               ┌──────────┐               ┌──────────┐
    │Generate  │               │Generate  │               │Generate  │
    │OTP: 123456│              │OTP: 789012│              │OTP: 345678│
    └──────────┘               └──────────┘               └──────────┘
          │                            │                            │
          │ EmailDeliveryService       │ EmailDeliveryService       │ EmailDeliveryService
          │ sendOtp(                   │ sendOtp(                   │ sendOtp(
          │   "alice@gmail.com",       │   "bob@yahoo.com",         │   "carol@outlook.com",
          │   "123456"                 │   "789012"                 │   "345678"
          │ )                          │ )                          │ )
          ▼                            ▼                            ▼
    ┌──────────┐               ┌──────────┐               ┌──────────┐
    │Mail::to( │               │Mail::to( │               │Mail::to( │
    │  "alice@ │               │  "bob@   │               │  "carol@ │
    │  gmail.  │               │  yahoo.  │               │  outlook.│
    │  com"    │               │  com"    │               │  com"    │
    │)->send() │               │)->send() │               │)->send() │
    └──────────┘               └──────────┘               └──────────┘
          │                            │                            │
          ▼                            ▼                            ▼
    ┌──────────┐               ┌──────────┐               ┌──────────┐
    │  Alice   │               │   Bob    │               │  Carol   │
    │ receives │               │ receives │               │ receives │
    │  123456  │               │  789012  │               │  345678  │
    └──────────┘               └──────────┘               └──────────┘
```

**KEY POINT:** Each user receives their OTP at their own email address. No hardcoded recipient.

---

## CRITICAL CODE PATHS

### Path 1: Frontend Email Capture
```javascript
// File: resources/views/auth/register.blade.php
// Line: 1304

const email = document.getElementById('email')?.value.trim() || '';
//    ↑
//    └── Captures WHATEVER the user types in the email field

fetch('{{ route("otp.register.send") }}', {
    method: 'POST',
    body: JSON.stringify({ 
        email: email  // ← Dynamic user input, NOT hardcoded
    })
});
```

### Path 2: Controller Receives Email
```php
// File: app/Http/Controllers/OtpController.php
// Line: 45-47

$emailClean = strtolower(trim((string) $request->email));
//                                       ↑
//                                       └── User's input from request

$result = $this->otpService->sendOtp($emailClean, ...);
//                                    ↑
//                                    └── Passed to service (no hardcoding)
```

### Path 3: Service Processes Email
```php
// File: app/Services/OtpService.php
// Line: 110, 188-190

$cleanEmail = strtolower(trim($email));  // Normalize user's email

$delivery = $this->emailDeliveryService->sendOtp(
    recipientEmail: $cleanEmail,  // ← User's email passed as parameter
    otpCode: $otp->code,
    purpose: $purpose,
    recipientName: $recipientName,
    requestId: $resolvedRequestId
);
```

### Path 4: Email Delivery Service Sends to Dynamic Recipient
```php
// File: app/Services/Email/EmailDeliveryService.php
// Line: 75-76, 112

public function sendOtp(
    string $recipientEmail,  // ← Parameter from user input
    string $otpCode,
    string $purpose,
    string $recipientName = 'User',
    ?string $requestId = null
): EmailDeliveryResult {
    // ...
    $mailable = new OtpMail($otpCode, $purpose, $recipientName);
    $sentMessage = Mail::to($recipientEmail)->send($mailable);
    //                       ↑
    //                       └── DYNAMIC recipient (NOT hardcoded)
}
```

---

## WHAT IF IT WERE HARDCODED? (Comparison)

### ❌ WRONG (Hardcoded) - What system DOES NOT do:
```php
// THIS DOES NOT EXIST IN THE CODE
Mail::to("mcg4538@gmail.com")->send($mailable);  // ← BAD! Hardcoded
```

### ✅ CORRECT (Dynamic) - What system DOES:
```php
// THIS IS WHAT THE CODE ACTUALLY DOES
Mail::to($recipientEmail)->send($mailable);  // ← GOOD! Dynamic from user input
```

---

## VERIFICATION CHECKLIST

- [x] Frontend captures user's email input (not hardcoded)
- [x] Frontend sends email to backend via AJAX
- [x] Backend receives email from request (not hardcoded)
- [x] Backend validates and normalizes email
- [x] Backend passes email to OtpService
- [x] OtpService generates unique OTP for that email
- [x] OtpService passes email to EmailDeliveryService
- [x] EmailDeliveryService uses Mail::to($recipientEmail) (dynamic)
- [x] No Mail::alwaysTo() configuration exists
- [x] No hardcoded recipient in OtpMail class
- [x] No test mode recipient override

**RESULT: ✅ ALL CHECKS PASSED - System uses dynamic recipients**

---

## CONCLUSION

**The OTP email system correctly sends to any user-provided email address.**

There is **NO hardcoded recipient restriction** anywhere in the email delivery pipeline.

If users report not receiving OTP emails, the issue is:
- Email delivery configuration (SMTP credentials)
- Spam filtering by recipient's email provider
- Rate limiting (30-second cooldown)
- Network/port restrictions (consider using RESEND_API_KEY or BREVO_API_KEY)

The issue is **NOT** a hardcoded recipient problem.

---

**Document Version:** 1.0  
**Date:** 2026-09-06  
**Status:** ✅ VERIFIED
