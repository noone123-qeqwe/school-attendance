<?php

return [
    'attendance_start_grace_minutes' => (int) env('ATTENDANCE_START_GRACE_MINUTES', 10),
    'qr_token_ttl' => max(15, (int) env('QR_TOKEN_TTL', 60)),
    'max_manual_regenerations' => max(0, (int) env('QR_MAX_MANUAL_REGENERATIONS', 3)),
];
