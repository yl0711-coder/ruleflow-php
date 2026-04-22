<?php

declare(strict_types=1);

namespace RuleFlow;

use RuleFlow\Operators\OperatorRegistry;

final class Engine
{
    private readonly ConditionEvaluator $conditions;
    private readonly FailureReasonResolver $failureReasons;

    public function __construct(
        private readonly RuleSet $ruleSet,
        OperatorRegistry $operators,
        FieldAccessor $fields = new FieldAccessor()
    ) {
        $this->failureReasons = new FailureReasonResolver();
        $this->conditions = new ConditionEvaluator($operators, $fields, $this->failureReasons);
    }

    public static function make(RuleSet $ruleSet): self
    {
        return new self($ruleSet, OperatorRegistry::defaults());
    }

    public static function makeWithOperators(RuleSet $ruleSet, OperatorRegistry $operators): self
    {
        return new self($ruleSet, $operators);
    }

    /**
     * @param array<string,mixed> $context
     */
    public function evaluate(array $context): EvaluationResult
    {
        ['trace' => $trace, 'matchedRules' => $matchedRules] = $this->run($context, false);

        if ($matchedRules !== []) {
            return EvaluationResult::match($matchedRules[0], new Trace($trace));
        }

        return EvaluationResult::noMatch(new Trace($trace));
    }

    /**
     * @param array<string,mixed> $context
     */
    public function evaluateAll(array $context): MultiEvaluationResult
    {
        ['trace' => $trace, 'matchedRules' => $matchedRules] = $this->run($context, true);

        return new MultiEvaluationResult($matchedRules, new Trace($trace));
    }

    /**
     * @param array<string,mixed> $context
     * @return array{
     *     trace:list<array<string,mixed>>,
     *     matchedRules:list<Rule>
     * }
     */
    private function run(array $context, bool $collectAll): array
    {
        $trace = [];
        $matchedRules = [];

        foreach ($this->ruleSet->rules() as $rule) {
            $ruleStartedAt = hrtime(true);

            if (!$rule->enabled()) {
                $trace[] = $this->skippedTraceEntry($rule, $ruleStartedAt);
                continue;
            }

            ['matched' => $matched, 'checks' => $checks] = $this->conditions->evaluate(
                $rule->conditions(),
                $rule->match(),
                $context
            );

            $traceEntry = [
                'rule' => $rule->name(),
                'priority' => $rule->priority(),
                'matched' => $matched,
                'match' => $rule->match(),
                'action' => $rule->action(),
                'reason' => $rule->reason(),
                'duration_ms' => $this->durationSince($ruleStartedAt),
                'checks' => $checks,
            ];

            if (!$matched) {
                $traceEntry['failure_reason'] = $this->failureReasons->first($checks);
                $trace[] = $traceEntry;
                continue;
            }

            $matchedRules[] = $rule;

            if (!$collectAll) {
                $traceEntry['stop_reason'] = 'first_match';
                $trace[] = $traceEntry;
                break;
            }

            $trace[] = $traceEntry;
        }

        return [
            'trace' => $trace,
            'matchedRules' => $matchedRules,
        ];
    }

    /**
     * @return array{
     *     rule:string,
     *     matched:bool,
     *     skipped:bool,
     *     skipped_reason:string,
     *     duration_ms:float,
     *     checks:list<array<string,mixed>>
     * }
     */
    private function skippedTraceEntry(Rule $rule, int $startedAt): array
    {
        return [
            'rule' => $rule->name(),
            'priority' => $rule->priority(),
            'matched' => false,
            'skipped' => true,
            'skipped_reason' => 'disabled',
            'duration_ms' => $this->durationSince($startedAt),
            'checks' => [],
        ];
    }

    private function durationSince(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 3);
    }
}
