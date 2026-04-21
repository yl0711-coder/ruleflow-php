# Benchmark

RuleFlow includes a small local benchmark for checking rough evaluation cost.

Run:

```bash
php benchmarks/evaluate.php
```

Optional arguments:

```bash
php benchmarks/evaluate.php <rule-count> <iterations>
```

Example:

```bash
php benchmarks/evaluate.php 100 10000
```

The benchmark reports:

- `evaluate`: first-match evaluation
- `evaluateAll`: all-match evaluation
- peak memory usage

## Notes

Benchmark numbers depend on the PHP version, CPU, extensions, and whether
Xdebug is enabled.

Use this benchmark as a local regression signal rather than an absolute
performance guarantee.
