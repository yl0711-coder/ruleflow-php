# Security And Privacy

RuleFlow helps explain rule decisions, but explainability comes with operational
security responsibilities.

The engine can expose internal rule names, threshold values, and context data
through `trace()` and `explain()`. Use these outputs deliberately.

## Preferred Output

Prefer `explain()` for normal operational use:

- Internal API responses.
- Application logs.
- Support tooling.
- High-level diagnostics.

Use `trace()` only when you need deeper debugging:

- Per-condition actual and expected values.
- Nested group behavior.
- Rule stop behavior.
- Per-rule and per-condition duration analysis.

## Sensitive Conditions

When a condition may contain personal or secret data, mark it as sensitive:

```php
[
    'field' => 'user.phone',
    'operator' => '=',
    'value' => '13800138000',
    'sensitive' => true,
]
```

RuleFlow still evaluates the real value, but redacts `actual` and `expected`
in trace and explain output:

```php
[
    'actual' => '[redacted]',
    'expected' => '[redacted]',
]
```

This is useful for:

- Phone numbers.
- Email addresses.
- Access tokens.
- Session identifiers.
- Payment-related values.
- Internal risk signals that should not be broadly logged.

## Logging Guidance

Good default:

```php
$logger->info('Rule decision evaluated.', [
    'ruleflow' => $result->explain(),
]);
```

Controlled debug usage:

```php
$logger->debug('Rule decision trace.', [
    'trace' => $result->trace()->toArray(),
]);
```

Avoid:

- Returning full trace payloads to public clients.
- Logging full trace output in high-volume general-purpose logs.
- Exposing rule thresholds to external consumers unless required by product design.

## Rule Disclosure

Remember that rule names and reasons are also operational data.

Examples:

- `high_risk_order`
- `fraud_device_mismatch`
- `manual_review_for_large_amount`

These may reveal internal decision strategy. If you expose RuleFlow output
outside trusted systems, review:

- Rule names.
- `reason` text.
- Failure reason codes.
- Whether matched rule lists should be visible.

## Production Checklist

- Use `explain()` by default.
- Use `trace()` only for targeted debugging.
- Mark sensitive conditions with `sensitive: true`.
- Review whether rule names and reasons are safe to expose.
- Keep debug logging access-controlled.
- Retain operational logs according to your data retention policy.
