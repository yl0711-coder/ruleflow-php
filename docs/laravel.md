# Laravel Integration

RuleFlow ships with a Laravel service provider and facade alias.

Supported and tested Laravel versions are documented in
[laravel-compatibility.md](laravel-compatibility.md).

## Publish Config

```bash
php artisan vendor:publish --tag=ruleflow-config
```

## Define Rules

Edit `config/ruleflow.php`:

```php
'rules' => [
    [
        'name' => 'high_risk_order',
        'conditions' => [
            ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
        ],
        'action' => 'manual_review',
    ],
],
```

## Evaluate

```php
$result = app(\RuleFlow\RuleFlow::class)->evaluate([
    'order' => [
        'amount' => 1299,
    ],
]);
```

The facade alias can also be used when Laravel auto-discovery is enabled.

For a fuller production-style example, see [laravel-example.md](laravel-example.md).
