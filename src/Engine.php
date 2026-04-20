<?php

declare(strict_types=1);

namespace RuleFlow;

use RuleFlow\Operators\OperatorRegistry;

final class Engine
{
    public function __construct(
        private readonly RuleSet $ruleSet,
        private readonly OperatorRegistry $operators,
        private readonly FieldAccessor $fields = new FieldAccessor()
    ) {
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
        $trace = [];

        foreach ($this->ruleSet->rules() as $rule) {
            if (!$rule->enabled()) {
                $trace[] = [
                    'rule' => $rule->name(),
                    'matched' => false,
                    'skipped' => true,
                    'checks' => [],
                ];
                continue;
            }

            $checks = [];
            $matched = $rule->matchesAllConditions();

            foreach ($rule->conditions() as $condition) {
                $actual = $this->fields->get($context, $condition->field());
                $operator = $this->operators->get($condition->operator());
                $passed = $operator->evaluate($actual, $condition->value());

                $checks[] = [
                    'field' => $condition->field(),
                    'actual' => $actual,
                    'operator' => $condition->operator(),
                    'expected' => $condition->value(),
                    'passed' => $passed,
                ];

                if ($rule->matchesAllConditions() && !$passed) {
                    $matched = false;
                } elseif (!$rule->matchesAllConditions() && $passed) {
                    $matched = true;
                }
            }

            $trace[] = [
                'rule' => $rule->name(),
                'matched' => $matched,
                'match' => $rule->match(),
                'checks' => $checks,
            ];

            if ($matched) {
                return EvaluationResult::matched($rule, new Trace($trace));
            }
        }

        return EvaluationResult::noMatch(new Trace($trace));
    }
}
