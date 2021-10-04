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
     * The following settings are the default configuration options that are used when running
     * either of the `thenpingme:setup` or `thenpingme:sync` commands and are considered to
     * be the source of truth for configuration, meaning they will take precendence over
     * values that you may have configured at the thenping.me task settings interface.
     *
     * You may, of course, override these default values on a per-task basis by using the
     * `thenpingme()` mixin that is available via Laravel's task scheduling interface.
     *
     * $schedule->command('thenpingme:sync')->daily()->thenpingme(
     *     allowed_run_time: 2
     * );
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
