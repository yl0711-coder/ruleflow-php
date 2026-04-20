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
