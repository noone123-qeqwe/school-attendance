<?php

return [
    // Mobile bearer tokens should require re-authentication periodically.
    'api_token_lifetime_days' => (int) env('API_TOKEN_LIFETIME_DAYS', 30),
];
