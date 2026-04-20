<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

interface OperatorInterface
{
    public function name(): string;

    public function evaluate(mixed $actual, mixed $expected): bool;
}
