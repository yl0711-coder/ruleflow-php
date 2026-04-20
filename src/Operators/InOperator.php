<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

final class InOperator implements OperatorInterface
{
    public function name(): string
    {
        return 'in';
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        return is_array($expected) && in_array($actual, $expected, true);
    }
}
