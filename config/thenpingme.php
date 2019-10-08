<?php

return [

    'project_id' => env('THENPINGME_PROJECT_ID'),

    'signing_key' => env('THENPINGME_SIGNING_KEY'),

    'queue_ping' => env('THENPINGME_QUEUE_PING', false),

    'options' => [

        'collect_git_information' => true,

    ],

];
