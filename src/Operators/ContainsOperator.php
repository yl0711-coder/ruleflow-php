<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

final class ContainsOperator implements OperatorInterface
{
    public function name(): string
    {
        return 'contains';
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        if (is_string($actual)) {
            return str_contains($actual, (string) $expected);
        }

        return is_array($actual) && in_array($expected, $actual, true);
    }
}
