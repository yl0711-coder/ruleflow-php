<?php

declare(strict_types=1);

namespace RuleFlow;

final class MultiEvaluationResult
{
    /**
     * @param list<Rule> $rules
     */
    public function __construct(
        private readonly array $rules,
        private readonly Trace $trace
    ) {
    }

    public function matched(): bool
    {
        return $this->rules !== [];
    }

    /**
     * @return list<Rule>
     */
    public function rules(): array
    {
        return $this->rules;
    }

    public function firstRule(): ?Rule
    {
        return $this->rules[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public function ruleNames(): array
    {
        return array_map(
            static fn (Rule $rule): string => $rule->name(),
            $this->rules
        );
    }

    /**
     * @return list<string>
     */
    public function actions(): array
    {
        return array_map(
            static fn (Rule $rule): string => $rule->action(),
            $this->rules
        );
    }

    /**
     * @return list<string>
     */
    public function reasons(): array
    {
        return array_values(
            array_filter(
                array_map(
                    static fn (Rule $rule): ?string => $rule->reason(),
                    $this->rules
                ),
                static fn (?string $reason): bool => $reason !== null
            )
        );
    }

    public function trace(): Trace
    {
        return $this->trace;
    }

    /**
     * @return array{
     *     matched:bool,
     *     rules:list<string>,
     *     matched_rules:list<string>,
     *     actions:list<string>,
     *     reasons:list<string>,
     *     failure_reason:?string,
     *     summary:array{
     *         evaluated_rules:int,
     *         matched_rules:list<string>,
     *         failed_rules:list<string>,
     *         skipped_rules:list<string>,
     *         duration_ms:float
     *     },
     *     rule_explanations:list<array<string,mixed>>
     * }
     */
    public function explain(): array
    {
        return [
            'matched' => $this->matched(),
            'rules' => $this->ruleNames(),
            'matched_rules' => $this->ruleNames(),
            'actions' => $this->actions(),
            'reasons' => $this->reasons(),
            'failure_reason' => $this->failureReason(),
            'summary' => $this->trace->summary(),
            'rule_explanations' => $this->trace->explainEntries(),
        ];
    }

    /**
     * @return array{
     *     matched:bool,
     *     rules:list<string>,
     *     matched_rules:list<string>,
     *     actions:list<string>,
     *     reasons:list<string>,
     *     trace:list<array<string,mixed>>
     * }
     */
    public function toArray(): array
    {
        return [
            'matched' => $this->matched(),
            'rules' => $this->ruleNames(),
            'matched_rules' => $this->ruleNames(),
            'actions' => $this->actions(),
            'reasons' => $this->reasons(),
            'trace' => $this->trace->toArray(),
        ];
    }

    private function failureReason(): ?string
    {
        foreach ($this->trace->failedEntries() as $entry) {
            $failureReason = $entry['failure_reason'] ?? null;

            if (is_string($failureReason)) {
                return $failureReason;
            }
        }

        return null;
    }
}
