# Explain Output

RuleFlow provides two debugging views:

- `trace()` returns the full execution detail for every evaluated rule and condition.
- `explain()` returns a compact decision summary for logs, API responses, and support tools.

Use `trace()` when you need deep diagnostics. Use `explain()` when you need a stable, human-readable payload that explains the decision without exposing every internal detail.

## Example

```php
use RuleFlow\Engine;
use RuleFlow\RuleSet;

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
        'name' => 'missing_phone',
        'priority' => 80,
        'conditions' => [
            ['field' => 'user.phone', 'operator' => 'exists'],
        ],
        'action' => 'manual_review',
        'reason' => 'User phone number is required for this flow.',
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

print_r($result->explain());
```

In this example:

- `high_risk_order` fails because `user.risk_score` is not below `60`.
- `missing_phone` fails because `user.phone` is missing.
- No rule matches, but the output explains why each rule failed.

## Output Shape

`explain()` returns a compact array:

```php
[
    'matched' => false,
    'rules' => [],
    'matched_rules' => [],
    'actions' => [],
    'reasons' => [],
    'failure_reason' => 'value_mismatch',
    'summary' => [
        'evaluated_rules' => 2,
        'matched_rules' => [],
        'failed_rules' => ['high_risk_order', 'missing_phone'],
        'skipped_rules' => [],
        'duration_ms' => 0.062,
    ],
    'rule_explanations' => [
        [
            'rule' => 'high_risk_order',
            'matched' => false,
            'skipped' => false,
            'failure_reason' => 'value_mismatch',
            'failed_checks' => [
                [
                    'field' => 'user.risk_score',
                    'operator' => '<',
                    'expected' => 60,
                    'actual' => 72,
                    'failure_reason' => 'value_mismatch',
                ],
            ],
        ],
        [
            'rule' => 'missing_phone',
            'matched' => false,
            'skipped' => false,
            'failure_reason' => 'field_missing',
            'failed_checks' => [
                [
                    'field' => 'user.phone',
                    'operator' => 'exists',
                    'expected' => null,
                    'actual' => null,
                    'failure_reason' => 'field_missing',
                ],
            ],
        ],
    ],
]
```

For `evaluate()`, the shape is similar but includes a single `rule`, `action`, and `reason` field instead of lists of actions and reasons.

## Explain vs Trace

Use `explain()` for:

- API responses that need to tell callers why a decision happened.
- Application logs where full trace output would be too noisy.
- Support dashboards that need a compact reason summary.
- Tests that assert high-level rule behavior.

Use `trace()` for:

- Deep debugging of nested condition groups.
- Inspecting every actual and expected value.
- Measuring per-rule and per-condition duration.
- Auditing the complete rule evaluation path.

## Run the Example

```bash
php examples/explain.php
```
