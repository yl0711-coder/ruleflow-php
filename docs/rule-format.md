# Rule Format

A RuleFlow rule contains:

- `name`: unique rule name
- `priority`: optional integer, higher priority runs first
- `enabled`: optional boolean, disabled rules are skipped
- `match`: optional string, either `all` or `any`
- `conditions`: one or more conditions
- `action`: decision returned when all conditions pass
- `reason`: optional human-readable explanation
- `metadata`: optional object for operational ownership and audit context

## Example

```json
{
  "name": "high_risk_order",
  "priority": 100,
  "enabled": true,
  "match": "all",
  "conditions": [
    {"field": "order.amount", "operator": ">", "value": 1000},
    {"field": "user.risk_score", "operator": "<", "value": 60}
  ],
  "action": "reject",
  "reason": "High-risk order requires manual review.",
  "metadata": {
    "owner": "risk",
    "version": "2026-05",
    "ticket": "RISK-1842"
  }
}
```

## Condition Format

Each condition contains:

- `field`: dot notation path in context, for example `user.risk_score`
- `operator`: comparison operator, for example `>` or `contains`
- `value`: expected value
- `sensitive`: optional boolean, redact `actual` and `expected` values in trace and explain output

## Nested Condition Groups

Conditions can also contain nested groups. This is useful for expressions such as
`order.amount > 1000 AND (user.risk_score < 60 OR user.country in ["NG", "RU"])`.

Each group contains:

- `match`: optional string, either `all` or `any`
- `conditions`: one or more conditions or nested groups

Example:

```json
{
  "name": "high_risk_order",
  "match": "all",
  "conditions": [
    {"field": "order.amount", "operator": ">", "value": 1000},
    {
      "match": "any",
      "conditions": [
        {"field": "user.risk_score", "operator": "<", "value": 60},
        {"field": "user.country", "operator": "in", "value": ["NG", "RU"]}
      ]
    }
  ],
  "action": "manual_review"
}
```

## Match Modes

RuleFlow supports two match modes:

- `all`: all conditions must pass. This is the default.
- `any`: at least one condition must pass.

Use `any` for moderation or risk rules where several independent signals can trigger the same action.

```json
{
  "name": "suspicious_content",
  "match": "any",
  "conditions": [
    {"field": "post.content", "operator": "contains", "value": "free money"},
    {"field": "post.report_count", "operator": ">=", "value": 3}
  ],
  "action": "manual_review"
}
```

## Rule Metadata

`metadata` is optional and does not affect evaluation. Use it for information
that helps engineers and operations teams understand where a rule came from and
how it should be maintained.

Good metadata fields include:

- `owner`: team or service responsible for the rule
- `version`: business rule version or release window
- `ticket`: issue, change request, or approval reference
- `rollout`: rollout stage such as `shadow`, `canary`, or `production`

Example:

```json
{
  "name": "vip_campaign_eligibility",
  "conditions": [
    {"field": "user.segment", "operator": "=", "value": "vip"}
  ],
  "action": "allow_campaign",
  "metadata": {
    "owner": "growth",
    "version": "2026-05",
    "ticket": "OPS-1288",
    "rollout": "production"
  }
}
```

Matched rule metadata is available through `metadata()`, `toArray()`, and
`explain()`:

```php
$result = Engine::make($ruleSet)->evaluate($context);

$result->metadata(); // ['owner' => 'growth', ...]
$result->explain()['metadata'];
```

## JSON Files

Rule files can be stored as JSON arrays:

```json
[
  {
    "name": "high_risk_order",
    "priority": 100,
    "match": "all",
    "conditions": [
      {"field": "order.amount", "operator": ">", "value": 1000},
      {"field": "user.risk_score", "operator": "<", "value": 60}
    ],
    "action": "reject",
    "reason": "High-risk order requires manual review."
  }
]
```

See `examples/rules/order-risk.json`.
