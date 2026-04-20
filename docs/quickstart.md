# Quick Start

This guide shows the smallest useful RuleFlow setup.

## 1. Define Rules

```php
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
```

## 2. Prepare Context

```php
$context = [
    'user' => ['risk_score' => 45],
    'order' => ['amount' => 1299],
];
```

## 3. Evaluate

```php
use RuleFlow\Engine;
use RuleFlow\RuleSet;

$result = Engine::make(RuleSet::fromArray($rules))->evaluate($context);
```

## 4. Read Result

```php
if ($result->matched()) {
    echo $result->action();
}
```

Use `$result->trace()->toArray()` when you need to debug why a rule matched or failed.

## Validate Rules

```php
use RuleFlow\Validation\RuleValidator;

RuleValidator::defaults()->assertValid($rules);
```

Validation helps catch missing fields, invalid match modes, and unsupported operators before evaluation.
