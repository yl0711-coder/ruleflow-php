# Laravel Compatibility

RuleFlow has two layers:

- Core PHP library: framework-agnostic, PHP 8.1 or later.
- Laravel integration: service provider, facade alias, config publishing, cache integration, and `ruleflow:validate`.

## Supported Laravel Versions

The package is tested against:

| Laravel | Testbench | PHP |
| --- | --- | --- |
| 10.x | 8.x | 8.1 |
| 11.x | 9.x | 8.2 |
| 12.x | 10.x | 8.3 |

This matrix is reflected in GitHub Actions.

## Composer Notes

The core package does not require `laravel/framework`. Laravel is listed as a
suggested package because RuleFlow can be used without Laravel.

For non-Laravel PHP projects:

```bash
composer require yl0711-coder/ruleflow-php
```

For Laravel projects:

```bash
composer require yl0711-coder/ruleflow-php
php artisan vendor:publish --tag=ruleflow-config
php artisan ruleflow:validate
```

For a full clean-project verification flow, see
[laravel-installation.md](laravel-installation.md).

## What The Laravel Integration Provides

- Auto-discovered service provider.
- Optional facade alias.
- `config/ruleflow.php` publishing.
- Rule loading from Laravel config.
- Optional Laravel cache store support.
- Artisan validation command.

## What It Does Not Replace

RuleFlow is not a replacement for:

- Laravel validation rules.
- Laravel policies.
- Laravel gates.
- Laravel queues or workflows.
- Full-featured rule management UIs.

Use Laravel validation for input correctness. Use policies and gates for
authorization. Use RuleFlow when business decisions need to be structured,
reviewable, and explainable.
