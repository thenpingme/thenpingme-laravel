<?php

return [

    'enabled' => env('THENPINGME_ENABLED', true),

    'project_id' => env('THENPINGME_PROJECT_ID'),

    'project_name' => env('THENPINGME_PROJECT_NAME') ?: env('APP_NAME'),

    'signing_key' => env('THENPINGME_SIGNING_KEY'),

    'queue_ping' => env('THENPINGME_QUEUE_PING', true),

    'queue_connection' => env('THENPINGME_QUEUE_CONNECTION', config('queue.default')),

    'queue_name' => env('THENPINGME_QUEUE_NAME', config(sprintf('queue.connections.%s.queue', config('thenpingme.queue_connection')))),

    // Capture git sha with ping
    // 'release' => trim(exec('git --git-dir ' . base_path('.git') . ' log --pretty="%h" -n1 HEAD')),

    'api_url' => env('THENPINGME_API_URL', 'https://thenping.me/api'),

    /*
     |-----------------------------------------------------------------------------
     | Alert notification defaults
     |-----------------------------------------------------------------------------
     |
     | You may configure default values that should be used for your task alerts.
     | These values take precedence over those used within thenping.me, unless
     | you override them on a task by task basis using the thenpingme mixin.
     |
     | $schedule->command('thenpingme:sync')->daily()->thenpingme(
     |     allowed_run_time: 2
     | );
     |
     */

    'settings' => [
        // How much time, in minutes, should be allowed to pass before a task is considered late
        'grace_period' => env('THENPINGME_SETTING_GRACE_PERIOD', 1),

        // How much time, in minutes, should a task be allowed to run before it is considered timed out
        'allowed_run_time' => env('THENPINGME_SETTING_ALLOWED_RUN_TIME', 1),

        // How many consecutive alerts should occur before you wish to be notified
        'notify_after_consecutive_alerts' => env('THENPINGME_SETTING_NOTIFY_AFTER_CONSECUTIVE_ALERTS', 1),
    ],

];
