<?php

return [
    // Optional fixed API token for the n8n integration (set N8N_API_TOKEN in the environment).
    // The seeder registers it, so it survives the demo database being rebuilt on every restart.
    'n8n_token' => env('N8N_API_TOKEN'),
];
