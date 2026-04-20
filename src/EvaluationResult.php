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

    public static function matched(Rule $rule, Trace $trace): self
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
     * @return array{matched:bool,rule:?string,action:?string,reason:?string,trace:list<array<string,mixed>>}
     */
    public function toArray(): array
    {
        return [
            'matched' => $this->matched,
            'rule' => $this->rule?->name(),
            'action' => $this->action(),
            'reason' => $this->reason(),
            'trace' => $this->trace->toArray(),
        ];
    }
}
