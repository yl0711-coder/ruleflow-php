# Production Usage

This guide describes practical production recommendations for RuleFlow PHP.

RuleFlow is intentionally small. It should help you keep rule decisions
testable, explainable, and safe to operate without becoming a workflow platform
or visual rule system.

## Evaluation Mode

Use `evaluate()` when your business decision needs the first matched rule:

```php
$result = Engine::make($ruleSet)->evaluate($context);
```

This is the right default for decisions such as:

- Blocking or allowing an order.
- Selecting the highest-priority moderation action.
- Returning one eligibility result.
- Applying a single access-control decision.

Use `evaluateAll()` when you need every matched rule:

```php
$result = Engine::make($ruleSet)->evaluateAll($context);
```

This is useful for:

- Collecting all triggered risk signals.
- Showing all moderation reasons.
- Running diagnostics or reporting.
- Combining multiple non-exclusive business actions.

## Explain vs Trace

Use `explain()` for compact operational output:

```php
$explain = $result->explain();
```

Good places for `explain()`:

- API responses for internal services.
- Application logs.
- Support dashboards.
- High-level feature tests.

Use `trace()` for deep debugging:

```php
$trace = $result->trace()->toArray();
```

Good places for `trace()`:

- Debugging nested condition groups.
- Auditing exact actual and expected values.
- Investigating rule priority or stop behavior.
- Measuring per-rule and per-condition duration.

Do not expose full `trace()` output directly to end users. It can contain
internal rule names, business thresholds, and raw context values.

## Rule Storage

For small systems, rules can live in PHP arrays or JSON files.

Recommended options:

- PHP config arrays for Laravel applications.
- JSON files for framework-agnostic deployments.
- Database-backed rules only when product or operations teams need runtime rule changes.

If rules are stored outside version control, keep a separate audit trail for:

- Who changed a rule.
- What changed.
- When it changed.
- Why it changed.
- Which deployment or validation process approved it.

## Validation

Validate rules before deployment:

```php
RuleValidator::defaults()->assertValid($rules);
```

For Laravel applications, run:

```bash
php artisan ruleflow:validate
```

Recommended CI usage:

```bash
composer test
composer lint
composer analyse
php artisan ruleflow:validate
```

Fail the deployment if rule validation fails. Invalid rules should be caught
before traffic reaches production.

## Laravel Cache

Enable cache when rules are loaded repeatedly from config or JSON:

```php
'cache' => [
    'enabled' => true,
    'driver' => 'laravel',
    'store' => 'redis',
    'key' => 'ruleflow.rules',
    'ttl' => 300,
],
```

Use a shared cache store such as Redis when multiple application instances need
consistent rule loading behavior.

Keep TTL short enough that rule updates can be picked up quickly, but long
enough to avoid unnecessary repeated parsing and validation.

## Logging

Prefer logging `explain()` instead of full `trace()`:

```php
$logger->info('Rule decision evaluated.', [
    'ruleflow' => $result->explain(),
]);
```

Only log full trace output in controlled debug environments:

```php
$logger->debug('Rule decision trace.', [
    'trace' => $result->trace()->toArray(),
]);
```

Before logging trace output, check whether your context contains:

- Phone numbers.
- Email addresses.
- Access tokens.
- Payment information.
- Internal fraud or risk scores.
- Any regulated personal data.

If sensitive data may be present, avoid logging full trace output or sanitize
the context before evaluation.

## Performance

RuleFlow evaluates rules in priority order.

Practical guidance:

- Put high-priority and high-confidence rules first.
- Keep expensive custom operators rare and explicit.
- Prefer `evaluate()` when only the first matched decision is needed.
- Use `evaluateAll()` only when collecting all matches is required.
- Cache rule loading separately from rule evaluation.

For local performance checks, run:

```bash
php benchmarks/evaluate.php
```

Benchmark numbers should be treated as local guidance, not a production SLA.
Measure with your actual rule count, context size, and custom operators.

## Recommended Production Checklist

- Rules are validated in CI.
- Tests cover important business decisions.
- Rule changes are reviewed.
- `explain()` is used for normal operational logs.
- Full `trace()` is only used for debugging or internal audits.
- Sensitive context values are not written to general application logs.
- Laravel cache is configured when rule loading becomes repeated or expensive.
- Release versions are pinned with Composer constraints such as `^0.3`.
