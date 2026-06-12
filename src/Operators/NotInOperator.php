<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

final class NotInOperator implements OperatorInterface, ValidatesValueInterface
{
    public function name(): string
    {
        return 'not_in';
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        return is_array($expected) && !in_array($actual, $expected, true);
    }

    public function validateValue(mixed $value): ?string
    {
        if (!is_array($value) || $value === []) {
            return 'must be a non-empty array.';
        }

        return null;
    }
}
