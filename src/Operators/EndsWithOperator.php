<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

final class EndsWithOperator implements OperatorInterface
{
    public function name(): string
    {
        return 'ends_with';
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        return is_string($actual) && str_ends_with($actual, (string) $expected);
    }
}
