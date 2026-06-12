# Custom Operators

RuleFlow ships with common operators, but production systems often need domain-specific checks.

Examples:

- regex matching for IDs or phone numbers
- IP range matching
- risk list lookup
- country or region matching
- custom score comparison

## Implement OperatorInterface

```php
use RuleFlow\Operators\OperatorInterface;

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
```

## Register Operator

```php
use RuleFlow\Engine;
use RuleFlow\Operators\OperatorRegistry;
use RuleFlow\RuleSet;

$operators = OperatorRegistry::defaults();
$operators->register(new RegexOperator());

$result = Engine::makeWithOperators(
    RuleSet::fromArray($rules),
    $operators
)->evaluate($context);
```

## Use In Rules

```php
[
    'name' => 'order_id_pattern',
    'conditions' => [
        ['field' => 'order.id', 'operator' => 'regex', 'value' => '/^ORD-[0-9]+$/'],
    ],
    'action' => 'allow',
]
```

## Optional: Validate Definition Values

Operators can also implement `ValidatesValueInterface` so that
`RuleValidator` (and `php artisan ruleflow:validate`) reports unusable
`value` shapes at validation time instead of failing silently at evaluation
time:

```php
use RuleFlow\Operators\OperatorInterface;
use RuleFlow\Operators\ValidatesValueInterface;

final class IpRangeOperator implements OperatorInterface, ValidatesValueInterface
{
    public function name(): string
    {
        return 'ip_in_range';
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        // ...
    }

    public function validateValue(mixed $value): ?string
    {
        if (!is_string($value) || !str_contains($value, '/')) {
            return 'must be a CIDR string such as 10.0.0.0/8.';
        }

        return null;
    }
}
```

Returning `null` accepts the value; returning a string reports it as a
validation error. The built-in `regex`, `between`, `in`, and `not_in`
operators implement this interface.
