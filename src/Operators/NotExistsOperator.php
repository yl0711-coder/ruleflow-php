<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

final class NotExistsOperator implements OperatorInterface
{
    public function name(): string
    {
        return 'not_exists';
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        return $actual === false;
    }
}
