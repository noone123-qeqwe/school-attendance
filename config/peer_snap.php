<?php

return [
    'enabled' => (bool) env('PEER_SNAP_ENABLED', false),
    'max_vouches' => (int) env('MAX_PEER_VOUCHES_PER_HOST_PER_SESSION', 2),
    'max_failed_attempts' => (int) env('PEER_MAX_FAILED_ATTEMPTS', 3),
    'lockout_minutes' => (int) env('PEER_LOCKOUT_MINUTES', 30),
    'session_ttl_seconds' => (int) env('PEER_VERIFICATION_TTL_SECONDS', 120),
    'presence_fresh_seconds' => (int) env('PEER_PRESENCE_FRESH_SECONDS', 120),
    'match_threshold' => (float) env('FACE_MATCH_THRESHOLD', 0.85),
    'pad_threshold' => (float) env('PEER_PAD_THRESHOLD', 0.90),
    'model_version' => env('PEER_FACE_MODEL_VERSION', ''),
    'verifier_url' => env('PEER_FACE_VERIFIER_URL', ''),
    'verifier_token' => env('PEER_FACE_VERIFIER_TOKEN', ''),
];
