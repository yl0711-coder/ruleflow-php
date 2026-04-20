# Rule Validation

RuleFlow includes a validator to catch rule format problems before evaluation.

This is useful for:

- CI checks for JSON rule files
- admin panels that let users edit rules
- deployment pipelines that validate changed rules
- local debugging before a new rule is released

## Basic Usage

```php
use RuleFlow\Validation\RuleValidator;

$validation = RuleValidator::defaults()->validate($rules);

if (!$validation->valid()) {
    print_r($validation->errors());
}
```

## Fast Failure

```php
RuleValidator::defaults()->assertValid($rules);
```

`assertValid()` throws `InvalidRuleException` with all validation errors.

## Custom Operators

When rules use custom operators, pass the same operator registry to the validator:

```php
use RuleFlow\Operators\OperatorRegistry;
use RuleFlow\Validation\RuleValidator;

$operators = OperatorRegistry::defaults();
$operators->register(new RegexOperator());

$validation = (new RuleValidator($operators))->validate($rules);
```

## Current Checks

- required rule keys: `name`, `conditions`, `action`
- non-empty rule names
- duplicated rule names
- valid `match` mode: `all` or `any`
- non-empty condition arrays
- required condition keys: `field`, `operator`, `value`
- non-empty field paths
- registered operators
