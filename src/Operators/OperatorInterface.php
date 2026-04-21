<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

interface OperatorInterface
{
    /**
     * Returns the operator name used in rule definitions.
     *
     * Operator names are stable rule-format identifiers, for example `=`,
     * `between`, `exists`, or `regex`.
     */
    public function name(): string;

    /**
     * Evaluates a condition value.
     *
     * `$actual` is the value resolved from the input context. For existence
     * operators, `$actual` is a boolean indicating whether the field exists.
     * `$expected` is the optional value from the rule definition.
     *
     * Operators must return false for unsupported input types instead of
     * throwing, unless the operator implementation documents otherwise.
     */
    public function evaluate(mixed $actual, mixed $expected): bool;
}
