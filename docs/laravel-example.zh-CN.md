# Laravel 风控示例

这份文档展示 RuleFlow 在 Laravel 项目里的一个真实风控接入方式：订单风险复核。

目标很明确：

- 把风险规则放在 `config/ruleflow.php`。
- 发布前校验规则。
- 在 controller 或 service 中执行订单决策。
- 给内部系统返回简洁的 `explain()` 决策说明。
- 规则加载频繁时使用 Redis-backed cache。

## 1. 发布配置

```bash
php artisan vendor:publish --tag=ruleflow-config
```

## 2. 定义风控规则

示例 `config/ruleflow.php`：

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
                ['field' => 'user.phone', 'operator' => 'not_exists', 'sensitive' => true],
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

## 3. 在 CI 或部署流程中校验

应用发布前执行：

```bash
php artisan ruleflow:validate
```

推荐部署检查：

```bash
composer test
composer lint
composer analyse
php artisan ruleflow:validate
```

如果 `ruleflow:validate` 失败，应该阻断部署。

## 4. 在 Controller 中执行

示例 controller：

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

        return response()->json([
            'decision' => $result->action() ?? 'allow',
            'ruleflow' => $result->explain(),
        ]);
    }
}
```

## 5. 日志里优先使用 explain

日常应用日志建议记录 `explain()`：

```php
$logger->info('Order risk decision evaluated.', [
    'order_id' => $context['order']['id'],
    'ruleflow' => $result->explain(),
]);
```

这样日志更紧凑，也不会暴露完整 trace。

如果条件标记了 `sensitive: true`，RuleFlow 会把 trace 和 explain 中的
`actual`、`expected` 脱敏成 `[redacted]`。

## 6. 只在排障时使用 trace

支持或研发需要更细诊断时，再记录完整 trace：

```php
$logger->debug('Order risk trace.', [
    'order_id' => $context['order']['id'],
    'trace' => $result->trace()->toArray(),
]);
```

这类日志只适合受控的内部环境。

## 7. Service 层写法

如果不希望 controller 知道 RuleFlow 细节，可以把决策逻辑放到应用服务里：

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

这种方式更适合：

- 多个 controller 复用同一套决策。
- 希望单独测试 context 映射。
- 项目本身有清晰的 service 分层。

## 推荐模式

Laravel 企业后端里，比较稳妥的默认方案是：

- 规则放在 `config/ruleflow.php`。
- 规则加载频繁时启用 Redis-backed cache。
- CI 和部署流程里运行 `php artisan ruleflow:validate`。
- 给内部调用方返回 `explain()`。
- 默认日志记录 `explain()`，只在定向排障时记录 `trace()`。
- 敏感字段条件加 `sensitive: true`。

