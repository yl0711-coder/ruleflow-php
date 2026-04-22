<?php

declare(strict_types=1);

namespace RuleFlow;

/**
 * @internal
 */
final class FailureReasonResolver
{
    public function forCondition(
        string $operator,
        bool $exists,
        mixed $actual,
        mixed $expected
    ): string {
        if ($operator === 'exists') {
            return 'field_missing';
        }

        if ($operator === 'not_exists') {
            return 'field_present';
        }

        if (!$exists) {
            return 'field_missing';
        }

        if (in_array($operator, ['>', '>=', '<', '<='], true)) {
            return is_numeric($actual) && is_numeric($expected)
                ? 'value_mismatch'
                : 'type_mismatch';
        }

        if ($operator === 'between') {
            if (!is_array($expected) || count($expected) !== 2) {
                return 'invalid_expected';
            }

            [$min, $max] = array_values($expected);

            return is_numeric($actual) && is_numeric($min) && is_numeric($max)
                ? 'value_out_of_range'
                : 'type_mismatch';
        }

        if ($operator === 'in') {
            return is_array($expected) ? 'value_not_allowed' : 'invalid_expected';
        }

        if ($operator === 'not_in') {
            return is_array($expected) ? 'value_disallowed' : 'invalid_expected';
        }

        if ($operator === 'contains') {
            return is_string($actual) || is_array($actual)
                ? 'value_not_contained'
                : 'type_mismatch';
        }

        if ($operator === 'starts_with') {
            return is_string($actual) ? 'prefix_mismatch' : 'type_mismatch';
        }

        if ($operator === 'ends_with') {
            return is_string($actual) ? 'suffix_mismatch' : 'type_mismatch';
        }

        if ($operator === 'regex') {
            if (!is_string($actual)) {
                return 'type_mismatch';
            }

            return is_string($expected) ? 'pattern_mismatch' : 'invalid_expected';
        }

        return 'value_mismatch';
    }

    /**
     * @param list<array<string,mixed>> $checks
     */
    public function first(array $checks): ?string
    {
        foreach ($checks as $check) {
            if (($check['passed'] ?? false) === true) {
                continue;
            }

            if (is_string($check['failure_reason'] ?? null)) {
                return $check['failure_reason'];
            }

            if (isset($check['checks']) && is_array($check['checks'])) {
                $nestedFailureReason = $this->first($check['checks']);

                if ($nestedFailureReason !== null) {
                    return $nestedFailureReason;
                }
            }
        }

        return null;
    }
}
