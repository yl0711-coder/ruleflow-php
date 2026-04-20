<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

final class BetweenOperator implements OperatorInterface
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
}
