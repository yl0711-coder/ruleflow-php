<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

final class RegexOperator implements OperatorInterface
{
    public function name(): string
    {
        return 'regex';
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        return is_string($actual)
            && is_string($expected)
            && preg_match($expected, $actual) === 1;
    }
}
