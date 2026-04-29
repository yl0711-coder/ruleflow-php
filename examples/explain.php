<?php

declare(strict_types=1);

use RuleFlow\Engine;
use RuleFlow\RuleSet;

require __DIR__ . '/../vendor/autoload.php';

ini_set('serialize_precision', '-1');

$rules = [
    [
        'name' => 'high_risk_order',
        'priority' => 100,
        'conditions' => [
            ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
            ['field' => 'user.risk_score', 'operator' => '<', 'value' => 60],
        ],
        'action' => 'manual_review',
        'reason' => 'High-risk order requires manual review.',
    ],
    [
        'name' => 'phone_present',
        'priority' => 80,
        'conditions' => [
            ['field' => 'user.phone', 'operator' => 'exists'],
        ],
        'action' => 'allow',
        'reason' => 'User phone number is present.',
    ],
];

$context = [
    'user' => [
        'id' => 1001,
        'risk_score' => 72,
    ],
    'order' => [
        'id' => 'ORD-1001',
        'amount' => 1299,
    ],
];

$result = Engine::make(RuleSet::fromArray($rules))->evaluateAll($context);

echo "Compact explain output:\n";
echo json_encode($result->explain(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;

echo "\nFull trace output:\n";
echo json_encode($result->trace()->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
