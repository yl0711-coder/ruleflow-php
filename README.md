# RuleFlow PHP

[![Tests](https://github.com/yl0711-coder/ruleflow-php/actions/workflows/tests.yml/badge.svg)](https://github.com/yl0711-coder/ruleflow-php/actions/workflows/tests.yml)
[![Packagist Version](https://img.shields.io/packagist/v/yl0711-coder/ruleflow-php.svg)](https://packagist.org/packages/yl0711-coder/ruleflow-php)
[![License](https://img.shields.io/packagist/l/yl0711-coder/ruleflow-php.svg)](LICENSE)

A lightweight, explainable rule engine for PHP and Laravel.

RuleFlow helps backend teams move complex business rules out of hard-coded `if/else` logic and into testable, configurable, and traceable rule definitions.

It is designed for risk control, content moderation, marketing eligibility, access control, and business decision workflows.

## Why RuleFlow?

Many backend systems start with simple condition checks:

```php
if ($order->amount > 1000 && $user->risk_score < 60) {
    return 'reject';
}
```

As the business grows, those checks become scattered across controllers, services, jobs, and middleware. They become hard to test, hard to explain, and risky to change.

RuleFlow provides a small and predictable way to:

- define business rules as structured data
- evaluate nested request, user, order, or content context
- return an explainable trace for every rule check
- integrate with Laravel without forcing a heavy architecture
- keep rule logic testable before shipping it to production

## Installation

### Via Packagist

```bash
composer require yl0711-coder/ruleflow-php
```

### From GitHub VCS

```bash
composer config repositories.ruleflow vcs https://github.com/yl0711-coder/ruleflow-php
composer require yl0711-coder/ruleflow-php:^0.2
```

The package requires PHP 8.1 or later.

## Quick Start

```php
use RuleFlow\Engine;
use RuleFlow\RuleSet;

$rules = [
    [
        'name' => 'high_risk_order',
        'priority' => 100,
        'match' => 'all',
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

$result->matched(); // true
$result->action();  // reject
$result->reason();  // High-risk order requires manual review.
$result->trace();   // explainable rule execution trace
```

## JSON Rules

Rules can also be stored as JSON and loaded at runtime:

```php
use RuleFlow\Engine;
use RuleFlow\Loaders\JsonRuleLoader;
use RuleFlow\Validation\RuleValidator;

$rulesPath = __DIR__ . '/rules/order-risk.json';
$rules = json_decode((string) file_get_contents($rulesPath), true);

RuleValidator::defaults()->assertValid($rules);

$ruleSet = (new JsonRuleLoader($rulesPath))->load();
$result = Engine::make($ruleSet)->evaluate($context);
```

See [examples/json-loader.php](examples/json-loader.php).

## Trace Output

RuleFlow returns a trace so engineers, support teams, and reviewers can understand why a rule matched.

```php
print_r($result->toArray());
```

Example output:

```php
[
    'matched' => true,
    'rule' => 'high_risk_order',
    'action' => 'reject',
    'reason' => 'High-risk order requires manual review.',
    'trace' => [
        [
            'rule' => 'high_risk_order',
            'priority' => 0,
            'matched' => true,
            'match' => 'all',
            'action' => 'reject',
            'reason' => 'High-risk order requires manual review.',
            'duration_ms' => 0.042,
            'stop_reason' => 'first_match',
            'checks' => [
                [
                    'field' => 'order.amount',
                    'exists' => true,
                    'missing' => false,
                    'actual' => 1299,
                    'operator' => '>',
                    'expected' => 1000,
                    'passed' => true,
                    'duration_ms' => 0.011,
                ],
                [
                    'field' => 'user.risk_score',
                    'exists' => true,
                    'missing' => false,
                    'actual' => 45,
                    'operator' => '<',
                    'expected' => 60,
                    'passed' => true,
                    'duration_ms' => 0.009,
                ],
            ],
        ],
    ],
]
```

When a field does not exist, the trace explicitly marks it with `exists: false` and
`missing: true`. Disabled rules are marked with `skipped: true` and
`skipped_reason: "disabled"`.

When a rule or condition fails, RuleFlow includes `failure_reason` when it can
infer one. Examples include `field_missing`, `type_mismatch`,
`invalid_expected`, `value_mismatch`, `value_not_allowed`,
`value_not_contained`, and `pattern_mismatch`.

You can also use trace helpers for operational debugging:

```php
$trace = $result->trace();

$trace->matchedRuleNames(); // ['high_risk_order']
$trace->failedEntries();    // rules evaluated but not matched
$trace->skippedEntries();   // disabled rules
$trace->summary();          // matched, failed, skipped, and total duration
```

## Supported Operators

RuleFlow v0.1 supports:

- `=`
- `!=`
- `>`
- `>=`
- `<`
- `<=`
- `in`
- `not_in`
- `exists`
- `not_exists`
- `contains`
- `starts_with`
- `ends_with`
- `between`
- `regex`

`=` and `!=` use strict PHP comparison semantics (`===` / `!==`).

See [docs/semantics.md](docs/semantics.md) for the full evaluation contract.

## Match Modes

Each rule supports a `match` mode:

- `all`: every condition must pass. This is the default.
- `any`: at least one condition must pass.

Example:

```php
[
    'name' => 'suspicious_content',
    'match' => 'any',
    'conditions' => [
        ['field' => 'post.content', 'operator' => 'contains', 'value' => 'free money'],
        ['field' => 'post.report_count', 'operator' => '>=', 'value' => 3],
    ],
    'action' => 'manual_review',
]
```

## Nested Condition Groups

RuleFlow also supports nested condition groups for cases like `A AND (B OR C)`:

```php
[
    'name' => 'high_risk_order',
    'match' => 'all',
    'conditions' => [
        ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
        [
            'match' => 'any',
            'conditions' => [
                ['field' => 'user.risk_score', 'operator' => '<', 'value' => 60],
                ['field' => 'user.country', 'operator' => 'in', 'value' => ['NG', 'RU']],
            ],
        ],
    ],
    'action' => 'manual_review',
]
```

Nested groups are evaluated recursively and included in the execution trace.

## Collect All Matches

Use `evaluateAll()` when you need every matched rule instead of only the first one:

```php
$result = Engine::make(RuleSet::fromArray($rules))->evaluateAll($context);

$result->matched();   // true
$result->ruleNames(); // ['amount_review', 'risk_hold']
$result->actions();   // ['manual_review', 'hold']
$result->reasons();   // ['Amount threshold reached.', 'Risk score threshold reached.']
```

This is useful for risk scoring, moderation signals, and rule-based tagging scenarios.

## Custom Operators

Register a custom operator when built-in operators are not enough:

```php
use RuleFlow\Engine;
use RuleFlow\Operators\OperatorInterface;
use RuleFlow\Operators\OperatorRegistry;
use RuleFlow\RuleSet;

final class RegexOperator implements OperatorInterface
{
    public function name(): string
    {
        return 'regex';
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        return is_string($actual)
            && is_string($expected)
            && preg_match($expected, $actual) === 1;
    }
}

$operators = OperatorRegistry::defaults();
$operators->register(new RegexOperator());

$result = Engine::makeWithOperators(RuleSet::fromArray($rules), $operators)->evaluate($context);
```

## Rule Validation

Validate rule definitions before loading them:

```php
use RuleFlow\Validation\RuleValidator;

$validation = RuleValidator::defaults()->validate($rules);

if (!$validation->valid()) {
    print_r($validation->errors());
}
```

See [docs/validation.md](docs/validation.md).

## Laravel Usage

Publish the config:

```bash
php artisan vendor:publish --tag=ruleflow-config
```

Define rules in `config/ruleflow.php`:

```php
'rules' => [
    [
        'name' => 'new_user_external_link',
        'conditions' => [
            ['field' => 'user.days_since_signup', 'operator' => '<=', 'value' => 7],
            ['field' => 'post.links', 'operator' => 'contains', 'value' => 'https://example.com'],
        ],
        'action' => 'manual_review',
    ],
],
```

Optional cache settings:

```php
'cache' => [
    'enabled' => true,
    'driver' => 'laravel', // or 'in_memory'
    'store' => 'redis',    // optional, uses default cache store when null
    'key' => 'ruleflow.rules',
    'ttl' => 300,
],
```

Evaluate rules:

```php
$result = app(\RuleFlow\RuleFlow::class)->evaluate($context);
```

Validate configured rules:

```bash
php artisan ruleflow:validate
```

## Use Cases

- Risk control: reject or review suspicious orders, users, or API requests.
- Content moderation: route posts, comments, and profiles to review queues.
- Marketing eligibility: decide whether a user can receive a coupon or campaign.
- Access control: evaluate contextual access decisions.
- Business workflow: choose approval paths from structured business conditions.

## Non-Goals

RuleFlow is intentionally small. The first versions do not aim to provide:

- a visual rule management UI
- a distributed decision platform
- a full workflow engine
- database migrations or admin dashboards
- replacement for Laravel policies, gates, or validation

## Development

```bash
composer install
composer test
composer lint
composer analyse
```

Run examples:

```bash
php examples/order-risk.php
php examples/content-moderation.php
php examples/json-loader.php
```

Generate coverage locally when Xdebug or PCOV is available:

```bash
composer test-coverage
```

Run the local benchmark:

```bash
php benchmarks/evaluate.php
```

See [docs/benchmark.md](docs/benchmark.md).

## Roadmap

- v0.1: core engine, built-in operators, trace output, array/JSON loaders, custom operators, rule validation
- v0.2: nested rule groups, evaluateAll, existence operators, built-in regex, trace improvements, Laravel cache driver, artisan validation command, PHPStan, coverage CI
- v0.3: benchmark suite, production tuning guide
- v1.0: stable rule format and semantic versioning guarantee

## License

RuleFlow PHP is open-sourced software licensed under the MIT license.
