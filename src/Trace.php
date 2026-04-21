<?php

declare(strict_types=1);

namespace RuleFlow;

final class Trace
{
    /**
     * @param list<array{
     *     rule:string,
     *     matched:bool,
     *     match?:string,
     *     skipped?:bool,
     *     skipped_reason?:string,
     *     failure_reason?:string,
     *     duration_ms?:float,
     *     stop_reason?:string,
     *     checks:list<array<string,mixed>>
     * }> $entries
     */
    public function __construct(private readonly array $entries = [])
    {
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function entries(): array
    {
        return $this->entries;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function matchedEntries(): array
    {
        return array_values(
            array_filter(
                $this->entries,
                static fn (array $entry): bool => $entry['matched']
            )
        );
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function failedEntries(): array
    {
        return array_values(
            array_filter(
                $this->entries,
                static fn (array $entry): bool => !$entry['matched']
                    && (!isset($entry['skipped']) || !$entry['skipped'])
            )
        );
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function skippedEntries(): array
    {
        return array_values(
            array_filter(
                $this->entries,
                static fn (array $entry): bool => ($entry['skipped'] ?? false) === true
            )
        );
    }

    /**
     * @return list<string>
     */
    public function matchedRuleNames(): array
    {
        return $this->ruleNames($this->matchedEntries());
    }

    /**
     * @return array{
     *     evaluated_rules:int,
     *     matched_rules:list<string>,
     *     failed_rules:list<string>,
     *     skipped_rules:list<string>,
     *     duration_ms:float
     * }
     */
    public function summary(): array
    {
        return [
            'evaluated_rules' => count($this->entries),
            'matched_rules' => $this->matchedRuleNames(),
            'failed_rules' => $this->ruleNames($this->failedEntries()),
            'skipped_rules' => $this->ruleNames($this->skippedEntries()),
            'duration_ms' => $this->durationMs(),
        ];
    }

    /**
     * @return list<array{
     *     rule:string,
     *     matched:bool,
     *     skipped:bool,
     *     failure_reason:?string,
     *     failed_checks:list<array{
     *         field?:string,
     *         operator?:string,
     *         expected?:mixed,
     *         actual?:mixed,
     *         failure_reason:?string
     *     }>
     * }>
     */
    public function explainEntries(): array
    {
        return array_values(
            array_map(
                fn (array $entry): array => [
                    'rule' => (string) $entry['rule'],
                    'matched' => (bool) $entry['matched'],
                    'skipped' => ($entry['skipped'] ?? false) === true,
                    'failure_reason' => $this->stringOrNull($entry['failure_reason'] ?? null),
                    'failed_checks' => $this->failedChecks($entry['checks']),
                ],
                $this->entries
            )
        );
    }

    public function durationMs(): float
    {
        $duration = 0.0;

        foreach ($this->entries as $entry) {
            $entryDuration = $entry['duration_ms'] ?? 0.0;

            if (is_float($entryDuration)) {
                $duration += $entryDuration;
            }
        }

        return round($duration, 3);
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function toArray(): array
    {
        return $this->entries;
    }

    /**
     * @param list<array<string,mixed>> $entries
     * @return list<string>
     */
    private function ruleNames(array $entries): array
    {
        return array_values(
            array_map(
                static fn (array $entry): string => (string) $entry['rule'],
                $entries
            )
        );
    }

    /**
     * @param list<array<string,mixed>> $checks
     * @return list<array{
     *     field?:string,
     *     operator?:string,
     *     expected?:mixed,
     *     actual?:mixed,
     *     failure_reason:?string
     * }>
     */
    private function failedChecks(array $checks): array
    {
        $failedChecks = [];

        foreach ($checks as $check) {
            if (($check['passed'] ?? false) === true) {
                continue;
            }

            if (isset($check['checks']) && is_array($check['checks'])) {
                foreach ($this->failedChecks($check['checks']) as $failedCheck) {
                    $failedChecks[] = $failedCheck;
                }

                continue;
            }

            $failedCheck = [
                'failure_reason' => $this->stringOrNull($check['failure_reason'] ?? null),
            ];

            foreach (['field', 'operator', 'expected', 'actual'] as $key) {
                if (array_key_exists($key, $check)) {
                    $failedCheck[$key] = $check[$key];
                }
            }

            $failedChecks[] = $failedCheck;
        }

        return $failedChecks;
    }

    private function stringOrNull(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }
}
