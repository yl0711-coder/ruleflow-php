<?php

declare(strict_types=1);

namespace RuleFlow\Tests\Fixtures;

use RuleFlow\Operators\OperatorInterface;
use RuleFlow\Operators\UsesExistenceInput;

/**
 * Custom existence-style operator used to prove third-party operators can opt
 * into existence input via the UsesExistenceInput marker: `$actual` is the
 * boolean "field exists" flag, and the rule `value` is optional.
 */
final class IsMissingOperator implements OperatorInterface, UsesExistenceInput
{
    public function name(): string
    {
        return 'is_missing';
    }

    public function evaluate(mixed $actual, mixed $expected): bool
    {
        return $actual === false;
    }
}
