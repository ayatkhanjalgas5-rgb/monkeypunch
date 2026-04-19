<?php

return [
    'allow_registration' => env('ADMIN_ALLOW_REGISTRATION', false),
    'task_claim_cooldown_seconds' => (int) env('TASK_CLAIM_COOLDOWN_SECONDS', 15),
];
