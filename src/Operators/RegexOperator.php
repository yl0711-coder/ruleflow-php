<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

final class RegexOperator implements OperatorInterface, ValidatesValueInterface
{
    public function name(): string
    {
        return 'regex';
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        if (!is_string($actual) || !is_string($expected)) {
            return false;
        }

        // Invalid patterns are reported through validateValue(); at
        // evaluation time they must degrade to a non-match without
        // emitting per-evaluation PHP warnings.
        return @preg_match($expected, $actual) === 1;
    }

    public function validateValue(mixed $value): ?string
    {
        if (!is_string($value) || $value === '') {
            return 'must be a non-empty string regex pattern.';
        }

        if (@preg_match($value, '') === false) {
            return 'must be a valid regex pattern.';
        }

        return null;
    }
}
