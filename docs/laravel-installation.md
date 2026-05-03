# Laravel Installation Smoke Test

This guide verifies RuleFlow inside a real Laravel application. It complements
the Testbench-based compatibility tests in CI.

Use this checklist when preparing a release or validating the package in a new
Laravel project.

If you want a repeatable local check, use the included smoke-test script:

```bash
PHP_BIN=/Applications/XAMPP/xamppfiles/bin/php \
COMPOSER_BIN=composer \
bash scripts/smoke-laravel.sh
```

The script creates a temporary Laravel project under `/tmp`, installs RuleFlow
from the current local repository, publishes the config, validates a minimal
rule set, and runs one container-based evaluation through Laravel.

## Supported Targets

RuleFlow is tested with:

| Laravel | PHP |
| --- | --- |
| 10.x | 8.1+ |
| 11.x | 8.2+ |
| 12.x | 8.3+ |

The core package only requires PHP 8.1+. Laravel is optional and loaded through
package auto-discovery when the package is installed in a Laravel application.

## 1. Create A Clean Laravel Project

```bash
composer create-project laravel/laravel ruleflow-smoke-test
cd ruleflow-smoke-test
```

Install RuleFlow:

```bash
composer require yl0711-coder/ruleflow-php
```

## 2. Publish The Config

```bash
php artisan vendor:publish --tag=ruleflow-config
```

Expected result:

- `config/ruleflow.php` exists.
- The file contains `rules` and `cache` sections.

## 3. Add A Minimal Rule

Edit `config/ruleflow.php`:

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

## 4. Validate Rules

```bash
php artisan ruleflow:validate
```

Expected result:

```text
RuleFlow rules are valid.
```

If validation fails, fix the rule file before deployment.

## 5. Run A Manual Evaluation

Open Laravel Tinker:

```bash
php artisan tinker
```

Evaluate a context:

```php
$result = app(\RuleFlow\RuleFlow::class)->evaluate([
    'order' => ['amount' => 1299],
]);

$result->matched();
$result->action();
$result->explain();
```

Expected result:

- `matched()` returns `true`.
- `action()` returns `manual_review`.
- `explain()` returns a compact decision summary.

## 6. Optional Cache Check

For repeated rule loading, enable Laravel cache integration:

```php
'cache' => [
    'enabled' => true,
    'driver' => 'laravel',
    'store' => null,
    'key' => 'ruleflow.rules',
    'ttl' => 300,
],
```

Run validation and Tinker evaluation again:

```bash
php artisan ruleflow:validate
php artisan tinker
```

For production multi-instance deployments, prefer a shared cache store such as
Redis. For local smoke tests, Laravel's default cache store is enough.

## Release Checklist

Before publishing a RuleFlow release, verify:

- GitHub Actions passes for Laravel 10, 11, and 12.
- A clean Laravel project can install the package.
- `vendor:publish --tag=ruleflow-config` works.
- `php artisan ruleflow:validate` works.
- A simple `app(\RuleFlow\RuleFlow::class)->evaluate()` call works.
- The README and Laravel compatibility docs match the tested versions.

## CI Versus Real Laravel Smoke Test

The GitHub Actions matrix uses Orchestra Testbench to verify Laravel package
integration across supported Laravel versions. That is fast and reliable for
service provider, facade, config, cache, and command behavior.

The clean-project smoke test verifies a different path:

- Composer can install the package into a real Laravel skeleton.
- Laravel package auto-discovery works outside Testbench.
- Config publishing works in a normal application.
- `php artisan ruleflow:validate` works from a normal Laravel CLI.
- A container-resolved `RuleFlow\RuleFlow` instance can evaluate a real context.

Use both checks before important releases.
