<?php

return [

    'project_id' => env('THENPINGME_PROJECT_ID'),

    'signing_key' => env('THENPINGME_SIGNING_KEY'),

    'queue_ping' => env('THENPINGME_QUEUE_PING', false),

    'collect_git_sha' => env('THENPINGME_COLLECT_GIT_SHA', true),

    'test_mode' => env('THENPINGME_TEST_MODE', false),

];
