# RuleFlow PHP

[English](README.md) | [简体中文](README.zh-CN.md)

[![Tests](https://github.com/yl0711-coder/ruleflow-php/actions/workflows/tests.yml/badge.svg)](https://github.com/yl0711-coder/ruleflow-php/actions/workflows/tests.yml)
[![Packagist Version](https://img.shields.io/packagist/v/yl0711-coder/ruleflow-php.svg)](https://packagist.org/packages/yl0711-coder/ruleflow-php)
[![License](https://img.shields.io/packagist/l/yl0711-coder/ruleflow-php.svg)](LICENSE)

一个面向 PHP 和 Laravel 的轻量、可解释规则引擎。

RuleFlow 用来把复杂业务规则从硬编码的 `if/else` 里抽出来，变成可测试、可配置、可追踪的规则定义。

它适合风控、内容审核、营销资格判断、访问控制，以及各种业务决策场景。

## 这个项目解决什么问题

很多 PHP 系统一开始只是少量判断：

```php
if ($order->amount > 1000 && $user->risk_score < 60) {
    return 'reject';
}
```

真正变复杂后，问题通常不是“能不能写出来”，而是：

- 规则散落在 controller、service、job、listener 里
- 发布前很难校验规则是否写错
- 支持、运营、审核同学很难知道为什么命中某条规则
- 业务逻辑和流程代码混在一起，改动风险越来越高

RuleFlow 有意保持模型很小：

- 规则是结构化数据，不是框架绑定代码
- 按优先级做确定性评估
- 可以用 `trace()` 看完整执行轨迹，也可以用 `explain()` 看简洁解释
- Laravel 集成是可选能力，核心仍然保持框架无关

## 核心能力

- `evaluate()` 和 `evaluateAll()` 两种评估模式
- 支持 `A AND (B OR C)` 这类嵌套条件组
- 内置相等、数值、数组、存在性、字符串、正则等操作符
- 运行前规则校验
- 带失败原因和耗时的 trace 诊断
- 面向 API、日志、支持工具的 `explain()` 简洁输出
- `sensitive: true` 敏感值脱敏
- Laravel 配置加载、缓存支持、`php artisan ruleflow:validate`

## 适合什么场景

- 订单和支付风控
- 内容审核分流
- 活动或优惠资格判断
- 基于请求上下文的访问控制决策
- 需要被审查、测试、解释的业务规则

它不打算做这些事情：

- 可视化规则编辑平台
- BPMN 或工作流平台
- 分布式决策服务
- 替代完整权限、校验、工作流系统

## 为什么不是重型规则引擎

很多团队并不需要一个完整规则平台，只需要一个能放进现有 PHP 服务里的小型库，解决一个实际问题：把失控的条件分支逻辑整理成结构化、可测试、可解释的规则。

RuleFlow 就是围绕这个目标设计的：

- 不需要额外服务
- 不需要先学 DSL 或可视化设计器
- 不要求数据库 schema
- 核心使用不依赖框架
- 在第一条有价值规则上线前，不需要巨大的接入成本

和更重型的规则引擎相比，RuleFlow 用“能力边界”换“接入清晰度”：

- 概念更少
- 更容易嵌进已有 Laravel / PHP 项目
- 代码审查更直接，因为规则离业务上下文更近
- 线上排查更简单，因为内置了 `trace()` 和 `explain()`

如果你的问题是“我们要做规则平台”，RuleFlow 可能太小。  
如果你的问题是“PHP 业务逻辑已经变成难以测试的 if/else 丛林”，RuleFlow 的尺寸是合适的。

## 项目状态

当前发布线：`v0.3.x`

目前已经具备：

- Packagist 发布
- PHPUnit、PHPCS、PHPStan、examples、安装验证 CI
- changelog 和 GitHub Release 流程
- production、security、Laravel 文档

## 安装

### 通过 Packagist

```bash
composer require yl0711-coder/ruleflow-php
```

### 通过 GitHub VCS

```bash
composer config repositories.ruleflow vcs https://github.com/yl0711-coder/ruleflow-php
composer require yl0711-coder/ruleflow-php:^0.3
```

要求 PHP 8.1 及以上。

## 快速开始

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
$result->trace();   // 可解释的规则执行轨迹
```

## 文档入口

起步：

- [docs/README.md](docs/README.md)
- [docs/quickstart.md](docs/quickstart.md)
- [docs/rule-format.md](docs/rule-format.md)
- [docs/semantics.md](docs/semantics.md)

运行和排障：

- [docs/explain.md](docs/explain.md)
- [docs/production.md](docs/production.md)
- [docs/security-privacy.md](docs/security-privacy.md)

Laravel：

- [docs/laravel.md](docs/laravel.md)
- [docs/laravel-example.md](docs/laravel-example.md)

## Trace 输出

RuleFlow 会返回 trace，方便工程师、支持和审核人员理解一条规则为什么命中。

```php
print_r($result->toArray());
```

输出示例：

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
                    'sensitive' => false,
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
                    'sensitive' => false,
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

当字段不存在时，trace 会明确标记 `exists: false` 和 `missing: true`。被禁用的规则会标记 `skipped: true` 和 `skipped_reason: "disabled"`。

当规则或条件失败时，如果可以推断，RuleFlow 会附带 `failure_reason`，例如 `field_missing`、`type_mismatch`、`invalid_expected`、`value_mismatch`、`value_not_allowed`、`value_not_contained`、`pattern_mismatch`。

当条件标记为 `sensitive: true` 时，RuleFlow 会在 trace 和 explain 输出里把 `actual` 与 `expected` 脱敏为 `[redacted]`。

也可以使用 trace 辅助方法做运行排障：

```php
$trace = $result->trace();

$trace->matchedRuleNames(); // ['high_risk_order']
$trace->failedEntries();    // 已评估但未命中的规则
$trace->skippedEntries();   // 被禁用的规则
$trace->summary();          // 命中、失败、跳过和总耗时
```

## Explain 输出

当你需要更紧凑的决策说明，用于日志、接口响应或支持工具时，使用 `explain()`：

```php
$explain = $result->explain();
```

输出示例：

```php
[
    'matched' => false,
    'rule' => null,
    'matched_rules' => [],
    'action' => null,
    'reason' => null,
    'failure_reason' => 'field_missing',
    'summary' => [
        'evaluated_rules' => 1,
        'matched_rules' => [],
        'failed_rules' => ['missing_phone'],
        'skipped_rules' => [],
        'duration_ms' => 0.031,
    ],
    'rule_explanations' => [
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

`trace()` 保留完整执行细节，`explain()` 保留更稳定、更适合运营场景的决策摘要。

更多内容见：

- [docs/explain.md](docs/explain.md)
- [docs/production.md](docs/production.md)
- [docs/security-privacy.md](docs/security-privacy.md)
- [examples/explain.php](examples/explain.php)

## 支持的操作符

RuleFlow 目前支持：

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

`=` 和 `!=` 使用 PHP 严格比较语义，也就是 `===` / `!==`。

完整评估约定见 [docs/semantics.md](docs/semantics.md)。

## 匹配模式

每条规则都支持 `match` 模式：

- `all`：所有条件都必须通过，这是默认值。
- `any`：至少一个条件通过即可。

示例：

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

## 嵌套条件组

RuleFlow 支持 `A AND (B OR C)` 这类嵌套条件组：

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

嵌套组会被递归评估，并出现在执行 trace 中。

## 收集所有命中规则

当你需要所有命中规则，而不是只要第一条命中规则时，使用 `evaluateAll()`：

```php
$result = Engine::make(RuleSet::fromArray($rules))->evaluateAll($context);

$result->matched();   // true
$result->ruleNames(); // ['amount_review', 'risk_hold']
$result->actions();   // ['manual_review', 'hold']
$result->reasons();   // ['Amount threshold reached.', 'Risk score threshold reached.']
```

这适合风控评分、审核信号收集、规则化打标签等场景。

## 自定义操作符

当内置操作符不够用时，可以注册自定义操作符：

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

## 规则校验

加载规则前可以先校验规则定义：

```php
use RuleFlow\Validation\RuleValidator;

$validation = RuleValidator::defaults()->validate($rules);

if (!$validation->valid()) {
    print_r($validation->errors());
}
```

见 [docs/validation.md](docs/validation.md)。

## Laravel 用法

发布配置：

```bash
php artisan vendor:publish --tag=ruleflow-config
```

在 `config/ruleflow.php` 里定义规则：

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

可选缓存配置：

```php
'cache' => [
    'enabled' => true,
    'driver' => 'laravel', // 也可以是 'in_memory'
    'store' => 'redis',    // 可选，为 null 时使用默认 cache store
    'key' => 'ruleflow.rules',
    'ttl' => 300,
],
```

执行规则：

```php
$result = app(\RuleFlow\RuleFlow::class)->evaluate($context);
```

校验配置中的规则：

```bash
php artisan ruleflow:validate
```

见 [docs/laravel.md](docs/laravel.md) 和 [docs/laravel-example.md](docs/laravel-example.md)。

## 使用场景

- 风控：拒绝或复核可疑订单、用户、API 请求。
- 内容审核：把帖子、评论、资料页路由到审核队列。
- 营销资格：判断用户是否可以领取优惠券或参与活动。
- 访问控制：根据上下文做访问决策。
- 业务流程：根据结构化业务条件选择审批路径。

## 非目标

RuleFlow 有意保持小而清晰，当前版本不打算提供：

- 可视化规则管理 UI
- 分布式决策平台
- 完整工作流引擎
- 数据库迁移或后台管理面板
- Laravel policies、gates、validation 的替代品

## 开发

```bash
composer install
composer test
composer lint
composer analyse
```

运行示例：

```bash
php examples/order-risk.php
php examples/content-moderation.php
php examples/json-loader.php
php examples/explain.php
```

本地有 Xdebug 或 PCOV 时，可以生成覆盖率：

```bash
composer test-coverage
```

运行本地 benchmark：

```bash
php benchmarks/evaluate.php
```

见 [docs/benchmark.md](docs/benchmark.md)。

生产使用建议见 [docs/production.md](docs/production.md)。

## Roadmap

- v0.1：核心引擎、内置操作符、trace 输出、数组/JSON loader、自定义操作符、规则校验
- v0.2：嵌套规则组、evaluateAll、存在性操作符、内置 regex、trace 改进、Laravel cache driver、artisan 校验命令、PHPStan、覆盖率 CI
- v0.3：更丰富的 trace 诊断、失败原因、紧凑 explain 输出、benchmark、生产和安全文档
- v0.4：文档打磨、更多生产示例、API 优化、生态兼容性增强
- v1.0：稳定规则格式和语义化版本保证

## License

RuleFlow PHP 是基于 MIT license 开源的软件。
