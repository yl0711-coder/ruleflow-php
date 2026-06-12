<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

final class BetweenOperator implements OperatorInterface, ValidatesValueInterface
{
    public function name(): string
    {
        return 'between';
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        if (!is_numeric($actual) || !is_array($expected) || count($expected) !== 2) {
            return false;
        }

        [$min, $max] = array_values($expected);

        return is_numeric($min) && is_numeric($max) && $actual >= $min && $actual <= $max;
    }

    public function validateValue(mixed $value): ?string
    {
        if (!is_array($value) || count($value) !== 2) {
            return 'must be an array of exactly two numeric values.';
        }

        [$min, $max] = array_values($value);

        if (!is_numeric($min) || !is_numeric($max)) {
            return 'must be an array of exactly two numeric values.';
        }

        if ($min > $max) {
            return 'minimum must not be greater than maximum.';
        }

        return null;
    }
}
