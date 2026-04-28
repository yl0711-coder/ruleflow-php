# Laravel 兼容性

RuleFlow 分为两层：

- Core PHP library：框架无关，要求 PHP 8.1 或以上。
- Laravel integration：service provider、facade alias、配置发布、缓存集成、`ruleflow:validate`。

## 支持的 Laravel 版本

当前测试矩阵：

| Laravel | Testbench | PHP |
| --- | --- | --- |
| 10.x | 8.x | 8.1 |
| 11.x | 9.x | 8.2 |
| 12.x | 10.x | 8.3 |

这个矩阵会在 GitHub Actions 中显式验证。

## Composer 说明

核心包不强制依赖 `laravel/framework`。Laravel 只放在 `suggest` 中，因为 RuleFlow 可以在非 Laravel PHP 项目中使用。

非 Laravel PHP 项目：

```bash
composer require yl0711-coder/ruleflow-php
```

Laravel 项目：

```bash
composer require yl0711-coder/ruleflow-php
php artisan vendor:publish --tag=ruleflow-config
php artisan ruleflow:validate
```

## Laravel 集成提供什么

- 自动发现 service provider。
- 可选 facade alias。
- 发布 `config/ruleflow.php`。
- 从 Laravel config 加载规则。
- 可选 Laravel cache store。
- Artisan 规则校验命令。

## 它不替代什么

RuleFlow 不替代：

- Laravel validation rules。
- Laravel policies。
- Laravel gates。
- Laravel queues 或 workflows。
- 完整规则管理后台。

输入正确性仍然应该使用 Laravel validation。授权仍然应该使用 policies 和 gates。RuleFlow 适合需要结构化、可审查、可解释的业务决策。

