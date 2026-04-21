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
            'actions' => $this->actions(),
            'reasons' => $this->reasons(),
            'trace' => $this->trace->toArray(),
        ];
    }
}
