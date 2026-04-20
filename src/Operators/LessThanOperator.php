<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

final class LessThanOperator implements OperatorInterface
{
    public function name(): string
    {
        return '<';
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        return is_numeric($actual) && is_numeric($expected) && $actual < $expected;
    }
}
