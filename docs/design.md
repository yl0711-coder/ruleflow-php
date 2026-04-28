# Design Notes

RuleFlow is designed as a small library, not a platform.

## Core Principles

- Use the Decision List model: deterministic priority order, first matching decision by default.
- Rules should be data, not scattered `if/else` code.
- Every decision should be explainable with trace output.
- The engine should be framework-agnostic.
- Laravel integration should be convenient but optional.
- The first version should stay small enough to audit and test.

## Current Evaluation Model

RuleFlow evaluates rules in priority order. The first enabled rule whose conditions match becomes the result.

This is a Decision List model, not a RETE inference network. RuleFlow is designed
for request-level PHP business decisions where determinism, validation, and
traceability matter more than working-memory inference.

Within one rule, conditions can use:

- `all`: all conditions must pass
- `any`: at least one condition must pass

Condition trees may also be nested, which allows structures such as `A AND (B OR C)`.

The current project direction is:

- keep the core library framework-agnostic
- keep the public rule format small enough to audit
- provide Laravel integration without making Laravel part of the core design
- prefer traceability, validation, and operational clarity over adding platform-style features

For details, see [decision-list-model.md](decision-list-model.md).
