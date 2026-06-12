<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

/**
 * Optional contract for operators that can validate the `value` shape used
 * in rule definitions before evaluation.
 *
 * RuleValidator checks for this interface so misconfigured rule values, such
 * as an invalid regex pattern or a malformed between range, are reported at
 * validation time instead of failing silently at evaluation time. Custom
 * operators do not have to implement it.
 */
interface ValidatesValueInterface
{
    /**
     * Returns a human-readable error for an unusable definition value, or
     * null when the value is acceptable. Error messages are appended to
     * "rules[i].conditions[j].value " by the validator, so they should read
     * as the rest of that sentence, for example "must be a valid regex
     * pattern."
     */
    public function validateValue(mixed $value): ?string;
}
