<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

final class ExistsOperator implements OperatorInterface
{
    public function name(): string
    {
        return 'exists';
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        return $actual === true;
    }
}
