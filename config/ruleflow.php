<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Rule definitions
    |--------------------------------------------------------------------------
    |
    | Keep the first version simple: define rules in config or load them from
    | JSON files. Database-backed and UI-managed rules can be built later.
    |
    */
    'rules' => [],

    'cache' => [
        'enabled' => false,
        'driver' => 'in_memory',
        'store' => null,
        'key' => 'ruleflow.rules',
        'ttl' => 300,
    ],
];
