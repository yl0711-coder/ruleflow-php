# Laravel 安装冒烟测试

这份文档用于在真实 Laravel 项目里验证 RuleFlow。它补充的是 CI 里基于
Testbench 的兼容性测试。

发布版本前，或者在新的 Laravel 项目里接入 RuleFlow 前，可以按这份清单验证。

如果需要可重复执行的本地检查，可以使用项目里的 smoke-test 脚本：

```bash
PHP_BIN=/Applications/XAMPP/xamppfiles/bin/php \
COMPOSER_BIN=composer \
bash scripts/smoke-laravel.sh
```

这个脚本会在 `/tmp` 下创建临时 Laravel 项目，从当前本地仓库安装 RuleFlow，发布配置，写入一组最小规则，执行规则校验，并通过 Laravel 容器完成一次真实规则执行。

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

## CI 与真实 Laravel 冒烟测试的区别

GitHub Actions 里的 Laravel 矩阵使用 Orchestra Testbench 验证 Laravel package 集成。它适合快速验证 service provider、facade、config、cache、artisan command 等行为。

真实 Laravel 空项目冒烟测试验证的是另一条路径：

- Composer 能把包安装到真实 Laravel 骨架项目中。
- Laravel package auto-discovery 在 Testbench 之外也正常。
- 配置发布在普通应用里可用。
- `php artisan ruleflow:validate` 能从普通 Laravel CLI 执行。
- 通过 Laravel 容器解析出的 `RuleFlow\RuleFlow` 可以执行真实上下文。

重要版本发布前，建议两类检查都做。
