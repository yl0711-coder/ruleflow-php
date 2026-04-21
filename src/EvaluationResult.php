<?php

declare(strict_types=1);

namespace RuleFlow;

final class EvaluationResult
{
    public function __construct(
        private readonly bool $matched,
        private readonly ?Rule $rule,
        private readonly Trace $trace
    ) {
    }

    public static function noMatch(Trace $trace): self
    {
        return new self(false, null, $trace);
    }

    public static function match(Rule $rule, Trace $trace): self
    {
        return new self(true, $rule, $trace);
    }

    public function matched(): bool
    {
        return $this->matched;
    }

    public function rule(): ?Rule
    {
        return $this->rule;
    }

    public function action(): ?string
    {
        return $this->rule?->action();
    }

    public function reason(): ?string
    {
        return $this->rule?->reason();
    }

    public function trace(): Trace
    {
        return $this->trace;
    }

    /**
     * @return array{
     *     matched:bool,
     *     rule:?string,
     *     matched_rules:list<string>,
     *     action:?string,
     *     reason:?string,
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
            'matched' => $this->matched,
            'rule' => $this->rule?->name(),
            'matched_rules' => $this->rule !== null ? [$this->rule->name()] : [],
            'action' => $this->action(),
            'reason' => $this->reason(),
            'failure_reason' => $this->failureReason(),
            'summary' => $this->trace->summary(),
            'rule_explanations' => $this->trace->explainEntries(),
        ];
    }

    /**
     * @return array{
     *     matched:bool,
     *     rule:?string,
     *     matched_rules:list<string>,
     *     action:?string,
     *     reason:?string,
     *     trace:list<array<string,mixed>>
     * }
     */
    public function toArray(): array
    {
        return [
            'matched' => $this->matched,
            'rule' => $this->rule?->name(),
            'matched_rules' => $this->rule !== null ? [$this->rule->name()] : [],
            'action' => $this->action(),
            'reason' => $this->reason(),
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
