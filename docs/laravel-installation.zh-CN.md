# Laravel 安装冒烟测试

这份文档用于在真实 Laravel 项目里验证 RuleFlow。它补充的是 CI 里基于
Testbench 的兼容性测试。

发布版本前，或者在新的 Laravel 项目里接入 RuleFlow 前，可以按这份清单验证。

## 支持目标

RuleFlow 当前测试：

| Laravel | PHP |
| --- | --- |
| 10.x | 8.1+ |
| 11.x | 8.2+ |
| 12.x | 8.3+ |

核心包只要求 PHP 8.1+。Laravel 是可选集成，在 Laravel 项目里通过 package
auto-discovery 自动加载。

## 1. 创建干净 Laravel 项目

```bash
composer create-project laravel/laravel ruleflow-smoke-test
cd ruleflow-smoke-test
```

安装 RuleFlow：

```bash
composer require yl0711-coder/ruleflow-php
```

## 2. 发布配置

```bash
php artisan vendor:publish --tag=ruleflow-config
```

预期结果：

- 生成 `config/ruleflow.php`。
- 文件里包含 `rules` 和 `cache` 配置。

## 3. 添加最小规则

编辑 `config/ruleflow.php`：

```php
'rules' => [
    [
        'name' => 'high_amount_order',
        'priority' => 100,
        'match' => 'all',
        'conditions' => [
            ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
        ],
        'action' => 'manual_review',
        'reason' => 'High amount order requires review.',
    ],
],
```

## 4. 校验规则

```bash
php artisan ruleflow:validate
```

预期结果：

```text
RuleFlow rules are valid.
```

如果校验失败，应该先修复规则文件，再继续部署。

## 5. 手动执行一次规则

打开 Laravel Tinker：

```bash
php artisan tinker
```

执行：

```php
$result = app(\RuleFlow\RuleFlow::class)->evaluate([
    'order' => ['amount' => 1299],
]);

$result->matched();
$result->action();
$result->explain();
```

预期结果：

- `matched()` 返回 `true`。
- `action()` 返回 `manual_review`。
- `explain()` 返回简洁的决策说明。

## 6. 可选缓存验证

如果规则会被频繁加载，可以启用 Laravel cache 集成：

```php
'cache' => [
    'enabled' => true,
    'driver' => 'laravel',
    'store' => null,
    'key' => 'ruleflow.rules',
    'ttl' => 300,
],
```

再次运行规则校验和 Tinker 验证：

```bash
php artisan ruleflow:validate
php artisan tinker
```

生产环境如果是多实例部署，建议使用 Redis 这类共享 cache store。仅做本地冒烟测试时，Laravel 默认 cache store 就够用。

## 发布前检查清单

发布 RuleFlow 版本前，确认：

- GitHub Actions 通过 Laravel 10、11、12 测试矩阵。
- 干净 Laravel 项目可以安装这个包。
- `vendor:publish --tag=ruleflow-config` 正常。
- `php artisan ruleflow:validate` 正常。
- `app(\RuleFlow\RuleFlow::class)->evaluate()` 可以执行规则。
- README 和 Laravel 兼容性文档里的版本说明与测试矩阵一致。

