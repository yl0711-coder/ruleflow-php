<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

final class NotEqualsOperator implements OperatorInterface
{
    public function name(): string
    {
        return '!=';
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        return $actual != $expected;
    }
}
