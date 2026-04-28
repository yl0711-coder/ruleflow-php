# Decision List Model

RuleFlow uses a Decision List model.

A Decision List is an ordered set of rules:

```text
if rule_1 matches, return decision_1
else if rule_2 matches, return decision_2
else if rule_3 matches, return decision_3
else return no match
```

This model is also known as a prioritized rule list. It is common in business
decision systems where the input is a fixed context and the output is a
deterministic decision.

## How RuleFlow Maps To This Model

RuleFlow maps the model directly:

- A rule set is a decision list.
- Each rule has a `priority`.
- Rules are evaluated by descending priority.
- Each rule contains boolean predicates expressed as conditions.
- `evaluate()` returns the first matching rule.
- `evaluateAll()` returns every matching rule when the business case is non-exclusive.
- `trace()` records why each evaluated rule passed or failed.
- `explain()` turns the trace into a compact operational summary.

Example:

```php
[
    [
        'name' => 'block_high_risk_order',
        'priority' => 100,
        'conditions' => [
            ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
            ['field' => 'user.risk_score', 'operator' => '<', 'value' => 60],
        ],
        'action' => 'reject',
    ],
    [
        'name' => 'review_new_user',
        'priority' => 50,
        'conditions' => [
            ['field' => 'user.days_since_signup', 'operator' => '<=', 'value' => 7],
        ],
        'action' => 'manual_review',
    ],
]
```

The first rule that matches becomes the decision when using `evaluate()`.

## Predicate Composition

Each condition is a boolean predicate:

```text
field operator expected_value
```

Examples:

```text
order.amount > 1000
user.country in ["NG", "RU"]
user.phone exists
```

Predicates can be composed with:

- `all`: every predicate must pass
- `any`: at least one predicate must pass
- nested condition groups such as `A AND (B OR C)`

This gives RuleFlow enough structure for common PHP business decisions without
introducing a full inference engine.

## Why Not RETE

RuleFlow is not a RETE engine.

RETE-based engines such as Drools are designed for rule systems with many facts,
many rules, incremental fact changes, and inference behavior. They are a good fit
when rules can activate other rules or when a working memory of facts needs to be
matched efficiently over time.

RuleFlow targets a different problem:

```text
Given one request context, produce one explainable business decision.
```

For many PHP and Laravel systems, the main problem is not incremental rule
matching. The main problems are:

- business rules scattered through controllers, services, jobs, and listeners
- unsafe rule changes
- no validation before deployment
- no clear explanation when a decision is made
- too much framework or platform cost before the first useful rule ships

RuleFlow intentionally chooses the Decision List model because it keeps these
decisions deterministic, reviewable, testable, and easy to explain.

## Strengths

- Deterministic evaluation order.
- Small rule format that can be reviewed in pull requests.
- No external server, database, or visual designer required.
- Good fit for request-level business decisions.
- Trace output explains matches, failures, missing fields, skipped rules, and timing.
- Rule validation catches invalid rule definitions before runtime.
- Laravel integration is optional; the core stays framework-agnostic.

## Trade-offs

- Rule evaluation is linear in the number of enabled rules.
- `evaluateAll()` is more expensive than `evaluate()` because it does not stop at the first match.
- It does not provide RETE-style incremental matching.
- It does not implement forward chaining or backward chaining.
- It is not a complex event processing engine.
- It is not a workflow or orchestration platform.

## Complexity

For `evaluate()`:

```text
O(rules checked before first match * conditions per rule)
```

For `evaluateAll()`:

```text
O(all enabled rules * conditions per rule)
```

In practice, this is a good fit for small and medium rule sets used inside PHP
services. If a rule set grows large, split it by business domain, put high
confidence rules first, and measure with `benchmarks/evaluate.php`.

## When To Use RuleFlow

Use RuleFlow when you need:

- risk or fraud decisions
- content moderation routing
- campaign eligibility checks
- contextual access decisions
- support-friendly explanations for business decisions
- a rules layer that can be tested and code-reviewed

Do not use RuleFlow when you need:

- a full expert system
- complex event processing
- working-memory inference
- a visual rule management platform
- large-scale rule network optimization

