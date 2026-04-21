# Rule Semantics

This document describes RuleFlow's rule evaluation contract.

## Rule Order

Rules are evaluated by descending `priority`.

When two rules have the same priority, their relative order should not be used
as a business guarantee. Assign explicit priorities when order matters.

## Match Modes

RuleFlow supports two match modes:

- `all`: every condition or group must pass. This is the default.
- `any`: at least one condition or group must pass.

Nested condition groups use the same `match` semantics as top-level rules.

## Evaluation Modes

`evaluate()` returns the first matched rule and stops evaluation.

The matched trace entry includes:

```php
[
    'stop_reason' => 'first_match',
]
```

`evaluateAll()` evaluates all enabled rules and returns every matched rule in
evaluation order.

Disabled rules are skipped and included in the trace with:

```php
[
    'skipped' => true,
    'skipped_reason' => 'disabled',
]
```

## Trace Diagnostics

Every evaluated rule trace entry includes rule metadata and elapsed time:

```php
[
    'rule' => 'high_risk_order',
    'priority' => 100,
    'matched' => true,
    'action' => 'manual_review',
    'reason' => 'Risk threshold reached.',
    'duration_ms' => 0.042,
]
```

Each condition and nested group also includes `duration_ms`.

Use `Trace::summary()` for a compact debugging view with matched, failed,
skipped, and total duration data.

Failed rule and condition entries include `failure_reason` when RuleFlow can
infer a useful reason:

```php
[
    'rule' => 'high_risk_order',
    'matched' => false,
    'failure_reason' => 'field_missing',
    'checks' => [
        [
            'field' => 'user.phone',
            'operator' => 'exists',
            'passed' => false,
            'failure_reason' => 'field_missing',
        ],
    ],
]
```

Built-in failure reason codes include:

- `field_missing`: the input context does not contain the required field.
- `field_present`: `not_exists` failed because the field exists.
- `type_mismatch`: the actual or expected value type is unsupported by the operator.
- `invalid_expected`: the rule's expected value has an invalid shape for the operator.
- `value_mismatch`: a strict equality or numeric comparison did not pass.
- `value_not_allowed`: an `in` condition failed.
- `value_disallowed`: a `not_in` condition failed.
- `value_not_contained`: a `contains` condition failed.
- `prefix_mismatch`: a `starts_with` condition failed.
- `suffix_mismatch`: an `ends_with` condition failed.
- `value_out_of_range`: a `between` condition failed.
- `pattern_mismatch`: a `regex` condition failed.

## Field Resolution

Fields are resolved from the input context using dot notation, for example
`user.risk_score`.

The context may be an array or an object. Nested arrays and public object
properties are supported.

When a field does not exist:

- `actual` is `null`
- `exists` is `false`
- `missing` is `true`

When a field exists and its value is `null`:

- `actual` is `null`
- `exists` is `true`
- `missing` is `false`

## Equality

`=` and `!=` use strict PHP comparison semantics:

- `=` uses `===`
- `!=` uses `!==`

This avoids type coercion surprises such as `"0" == false`.

## Numeric Operators

Numeric comparison operators only pass when both operands are numeric:

- `>`
- `>=`
- `<`
- `<=`
- `between`

Unsupported input types return `false`.

## Existence Operators

`exists` and `not_exists` operate on field existence, not the field value.

They do not require a `value` key:

```php
['field' => 'user.email', 'operator' => 'exists']
['field' => 'user.phone', 'operator' => 'not_exists']
```

## Operator Failures

Built-in operators return `false` for unsupported input types instead of
throwing exceptions.

Rule definition errors, unsupported operators, and invalid rule files are
reported through RuleFlow exceptions or validation errors.
