# Laravel Example

This document shows a realistic Laravel integration example for RuleFlow using
an order risk review flow.

The goal is simple:

- Keep risk rules in `config/ruleflow.php`.
- Validate those rules before deployment.
- Evaluate an order request inside a controller or service.
- Return a compact `explain()` payload to internal systems.
- Use Redis-backed cache when rule loading is repeated across instances.

## 1. Publish The Config

```bash
php artisan vendor:publish --tag=ruleflow-config
```

## 2. Define Risk Rules

Example `config/ruleflow.php`:

```php
<?php

return [
    'rules' => [
        [
            'name' => 'high_amount_high_risk_user',
            'priority' => 100,
            'match' => 'all',
            'conditions' => [
                ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
                ['field' => 'user.risk_score', 'operator' => '<', 'value' => 60],
            ],
            'action' => 'manual_review',
            'reason' => 'High amount order from a high-risk user.',
        ],
        [
            'name' => 'missing_phone_for_high_amount',
            'priority' => 90,
            'match' => 'all',
            'conditions' => [
                ['field' => 'order.amount', 'operator' => '>', 'value' => 500],
                ['field' => 'user.phone', 'operator' => 'exists', 'sensitive' => true],
            ],
            'action' => 'manual_review',
            'reason' => 'High amount order requires a phone number.',
        ],
        [
            'name' => 'new_user_in_high_risk_region',
            'priority' => 80,
            'match' => 'all',
            'conditions' => [
                ['field' => 'user.days_since_signup', 'operator' => '<=', 'value' => 7],
                [
                    'match' => 'any',
                    'conditions' => [
                        ['field' => 'user.country', 'operator' => 'in', 'value' => ['NG', 'RU']],
                        ['field' => 'user.ip_country', 'operator' => 'in', 'value' => ['NG', 'RU']],
                    ],
                ],
            ],
            'action' => 'manual_review',
            'reason' => 'New user from a high-risk region.',
        ],
    ],

    'cache' => [
        'enabled' => true,
        'driver' => 'laravel',
        'store' => 'redis',
        'key' => 'ruleflow.rules',
        'ttl' => 300,
    ],
];
```

## 3. Validate Rules In CI Or Deployment

Run rule validation before the application is released:

```bash
php artisan ruleflow:validate
```

Recommended deployment quality checks:

```bash
composer test
composer lint
composer analyse
php artisan ruleflow:validate
```

If `ruleflow:validate` fails, stop the deployment.

## 4. Evaluate Inside A Controller

Example controller:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuleFlow\RuleFlow;

final class OrderRiskController
{
    public function __invoke(Request $request, RuleFlow $ruleFlow): JsonResponse
    {
        $context = [
            'user' => [
                'id' => (int) $request->user()->id,
                'risk_score' => (int) $request->user()->risk_score,
                'days_since_signup' => (int) $request->user()->created_at->diffInDays(now()),
                'country' => (string) $request->user()->country_code,
                'ip_country' => (string) $request->ip_country,
                'phone' => $request->user()->phone,
            ],
            'order' => [
                'id' => (string) $request->input('order_id'),
                'amount' => (float) $request->input('amount'),
            ],
        ];

        $result = $ruleFlow->evaluate($context);

        if (!$result->matched()) {
            return response()->json([
                'decision' => 'allow',
                'ruleflow' => $result->explain(),
            ]);
        }

        return response()->json([
            'decision' => $result->action(),
            'ruleflow' => $result->explain(),
        ]);
    }
}
```

## 5. Log Explain Output

For normal application logs, prefer `explain()`:

```php
$logger->info('Order risk decision evaluated.', [
    'order_id' => $context['order']['id'],
    'ruleflow' => $result->explain(),
]);
```

This keeps logs compact and avoids exposing the full trace payload.

Because the `user.phone` condition is marked `sensitive: true`, the output
redacts `actual` and `expected` values as `[redacted]`.

## 6. Debug With Trace Only When Needed

When support or engineering needs deeper diagnostics:

```php
$logger->debug('Order risk trace.', [
    'order_id' => $context['order']['id'],
    'trace' => $result->trace()->toArray(),
]);
```

Use this only in controlled internal environments.

## 7. Example Decision Output

Example `explain()` response for an internal API:

```php
[
    'matched' => true,
    'rule' => 'missing_phone_for_high_amount',
    'matched_rules' => ['missing_phone_for_high_amount'],
    'action' => 'manual_review',
    'reason' => 'High amount order requires a phone number.',
    'failure_reason' => null,
    'summary' => [
        'evaluated_rules' => 2,
        'matched_rules' => ['missing_phone_for_high_amount'],
        'failed_rules' => ['high_amount_high_risk_user'],
        'skipped_rules' => [],
        'duration_ms' => 0.091,
    ],
    'rule_explanations' => [
        [
            'rule' => 'high_amount_high_risk_user',
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
            'rule' => 'missing_phone_for_high_amount',
            'matched' => true,
            'skipped' => false,
            'failure_reason' => null,
            'failed_checks' => [],
        ],
    ],
]
```

## 8. Service Layer Variant

If you do not want controllers to know RuleFlow details, move the evaluation
into an application service:

```php
<?php

namespace App\Services;

use RuleFlow\RuleFlow;

final class OrderRiskService
{
    public function __construct(private readonly RuleFlow $ruleFlow)
    {
    }

    public function decide(array $context): array
    {
        $result = $this->ruleFlow->evaluate($context);

        return [
            'matched' => $result->matched(),
            'action' => $result->action() ?? 'allow',
            'ruleflow' => $result->explain(),
        ];
    }
}
```

This is usually the better choice when:

- Multiple controllers need the same decision.
- You want dedicated unit tests around context mapping.
- The application has a service-oriented architecture.

## Recommended Pattern

For a production Laravel backend, a good default is:

- Store rules in `config/ruleflow.php`.
- Use Redis-backed cache for repeated rule loading.
- Run `php artisan ruleflow:validate` in CI and deployment.
- Return `explain()` to internal callers.
- Log `explain()` by default and `trace()` only for targeted debugging.
- Mark sensitive conditions with `sensitive: true`.
