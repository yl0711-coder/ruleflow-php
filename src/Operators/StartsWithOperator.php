<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

final class StartsWithOperator implements OperatorInterface
{
    public function name(): string
    {
        return 'starts_with';
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        return is_string($actual) && str_starts_with($actual, (string) $expected);
    }
}
