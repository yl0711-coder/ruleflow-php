# Changelog

All notable changes to RuleFlow PHP will be documented in this file.

## Unreleased

- Added a real Laravel clean-project smoke-test script for checking package discovery, config publishing, rule validation, and container evaluation.
- Documented how the Laravel smoke test complements the existing Testbench-based CI checks.

## v0.3.2 - 2026-04-29

- Improved README positioning and added a documentation index.
- Clarified README positioning against heavier rule engine approaches.
- Added a Chinese project overview and language links in the README.
- Refreshed documentation examples and design notes to match the current feature set.
- Added Composer metadata validation to CI and split internal engine evaluation responsibilities.
- Added Decision List model documentation and Laravel compatibility notes.
- Added real Laravel installation smoke-test documentation in English and Chinese.
- Added a Chinese Laravel order risk example.
- Fixed example rule naming so the phone existence example matches its behavior.

## v0.3.1 - 2026-04-22

- Added production usage documentation.
- Added sensitive condition redaction for trace and explain output.
- Added security and privacy documentation.
- Added a production-style Laravel integration example.

## v0.3.0 - 2026-04-22

- Added richer trace diagnostics with rule duration, action, reason, priority, and first-match stop reason.
- Added trace helper methods for matched, failed, skipped, and summary views.
- Added trace failure reasons for unmatched rules and failed conditions.
- Added compact `explain()` output for single-rule and multi-rule evaluation results.
- Added explain documentation and a runnable explain example.

## v0.2.2 - 2026-04-21

- Added Packagist and license badges to the README.
- Added Packagist installation documentation.
- Added a benchmark script for rule evaluation performance.
- Added benchmark documentation with local measurement results.

## v0.2.1 - 2026-04-21

- Documented rule semantics and evaluation behavior.
- Documented public loader, cache, and operator interface contracts.
- Refined engine lifecycle and internal rule handling.
- Switched equality comparisons to strict operators.

## v0.2.0 - 2026-04-21

- Added nested condition groups for expressions such as `A AND (B OR C)`.
- Added `evaluateAll()` and `MultiEvaluationResult` for collecting all matched rules.
- Added built-in `exists`, `not_exists`, and `regex` operators.
- Improved trace readability with `exists`, `missing`, `matched_rules`, and `skipped_reason`.
- Added Laravel cache driver support with configurable cache store selection.
- Added `php artisan ruleflow:validate` command for Laravel projects.
- Added PHPStan static analysis and coverage generation scripts.
- Added CI quality job for PHPStan and Clover coverage output.
