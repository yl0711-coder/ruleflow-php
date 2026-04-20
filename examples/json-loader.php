<?php

declare(strict_types=1);

use RuleFlow\Engine;
use RuleFlow\Loaders\JsonRuleLoader;
use RuleFlow\Validation\RuleValidator;

require __DIR__ . '/../vendor/autoload.php';

$rulesPath = __DIR__ . '/rules/order-risk.json';
$rules = json_decode((string) file_get_contents($rulesPath), true);

$validation = RuleValidator::defaults()->validate($rules);

if (!$validation->valid()) {
    echo "Invalid rules:\n";
    echo implode("\n", $validation->errors());
    exit(1);
}

$context = [
    'user' => [
        'id' => 1001,
        'risk_score' => 45,
        'days_since_signup' => 3,
    ],
    'order' => [
        'id' => 'ORD-1001',
        'amount' => 1299,
    ],
];

$ruleSet = (new JsonRuleLoader($rulesPath))->load();
$result = Engine::make($ruleSet)->evaluate($context);

print_r($result->toArray());
