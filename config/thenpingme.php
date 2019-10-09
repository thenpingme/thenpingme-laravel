<?php

return [

    'project_id' => env('THENPINGME_PROJECT_ID'),

    'signing_key' => env('THENPINGME_SIGNING_KEY'),

    'queue_ping' => env('THENPINGME_QUEUE_PING', false),

    'options' => [

        'collect_git_sha' => env('THENPINGME_COLLECT_GIT_SHA', true),

        'endpoints' => [

            'setup' => env('THENPINGME_ENDPOINT_SETUP', 'https://thenping.me/api/projects/:project/setup'),

            'ping' => env('THENPINGME_ENDPOINT_PING', 'https://thenping.me/api/projects/:project/ping'),

        ],

    ],

];
