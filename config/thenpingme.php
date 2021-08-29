<?php

return [

    'enabled' => env('THENPINGME_ENABLED', true),

    'project_id' => env('THENPINGME_PROJECT_ID'),

    'project_name' => env('THENPINGME_PROJECT_NAME', env('APP_NAME')),

    'signing_key' => env('THENPINGME_SIGNING_KEY'),

    'queue_ping' => env('THENPINGME_QUEUE_PING', true),

    'queue_connection' => env('THENPINGME_QUEUE_CONNECTION', config('queue.default')),

    'queue_name' => env('THENPINGME_QUEUE_NAME', config(sprintf('queue.connections.%s.queue', config('thenpingme.queue_connection')))),

    // Capture git sha with ping
    // 'release' => trim(exec('git --git-dir ' . base_path('.git') . ' log --pretty="%h" -n1 HEAD')),

    'api_url' => env('THENPINGME_API_URL', 'https://thenping.me/api'),

];
