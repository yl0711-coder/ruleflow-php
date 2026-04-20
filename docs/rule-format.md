# Rule Format

A RuleFlow rule contains:

- `name`: unique rule name
- `priority`: optional integer, higher priority runs first
- `enabled`: optional boolean, disabled rules are skipped
- `match`: optional string, either `all` or `any`
- `conditions`: one or more conditions
- `action`: decision returned when all conditions pass
- `reason`: optional human-readable explanation

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
  "reason": "High-risk order requires manual review."
}
```

## Condition Format

Each condition contains:

- `field`: dot notation path in context, for example `user.risk_score`
- `operator`: comparison operator, for example `>` or `contains`
- `value`: expected value

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
