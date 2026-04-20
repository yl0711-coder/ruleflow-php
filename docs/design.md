# Design Notes

RuleFlow is designed as a small library, not a platform.

## Core Principles

- Rules should be data, not scattered `if/else` code.
- Every decision should be explainable with trace output.
- The engine should be framework-agnostic.
- Laravel integration should be convenient but optional.
- The first version should stay small enough to audit and test.

## Current Evaluation Model

RuleFlow evaluates rules in priority order. The first enabled rule whose conditions match becomes the result.

Within one rule, conditions can use:

- `all`: all conditions must pass
- `any`: at least one condition must pass

Future versions may add:

- nested condition groups
- Laravel cache driver integration
- rule validation commands
- benchmark tooling
