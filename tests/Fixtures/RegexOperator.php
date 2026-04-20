<?php

declare(strict_types=1);

namespace RuleFlow\Tests\Fixtures;

use RuleFlow\Operators\OperatorInterface;

final class RegexOperator implements OperatorInterface
{
    public function name(): string
    {
        return 'regex';
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        if (!is_string($actual) || !is_string($expected)) {
            return false;
        }

        return preg_match($expected, $actual) === 1;
    }
}
