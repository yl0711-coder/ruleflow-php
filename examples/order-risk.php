<?php

declare(strict_types=1);

use RuleFlow\Engine;
use RuleFlow\RuleSet;

require __DIR__ . '/../vendor/autoload.php';

$rules = [
    [
        'name' => 'high_risk_order',
        'priority' => 100,
        'conditions' => [
            ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
            ['field' => 'user.risk_score', 'operator' => '<', 'value' => 60],
        ],
        'action' => 'reject',
        'reason' => 'High-risk order requires manual review.',
    ],
];

$context = [
    'user' => [
        'id' => 1001,
        'risk_score' => 45,
    ],
    'order' => [
        'id' => 'O-20260420-001',
        'amount' => 1299,
    ],
];

$result = Engine::make(RuleSet::fromArray($rules))->evaluate($context);

print_r($result->toArray());
