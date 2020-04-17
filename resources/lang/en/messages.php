<?php

return [
    'setup' => [
        'signing_key' => 'Generate signing key',
        'write_env' => 'Write configuration to the .env file',
        'write_env_example' => 'Write configuration to the .env.example file',
        'public_config' => 'Publish config file',
    ],

    'env_missing' => 'The .env file is missing. Please add the following lines to your configuration, then run:',

    'initial_setup' => 'Setting up initial tasks with :url',

    'healthy_tasks' => 'Your tasks are correctly configured and can be synced to thenping.me!',

    'indistinguishable_tasks' => 'Tasks have been identified that are not uniquely distinguishable!',

    'duplicate_jobs' => 'Job-based tasks should set a description, or run on a unique schedule.',
    'duplicate_closures' => 'Closure-based tasks should set a description to ensure uniqueness.',
];
