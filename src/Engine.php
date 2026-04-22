<?php

declare(strict_types=1);

namespace RuleFlow;

use RuleFlow\Operators\ExistsOperator;
use RuleFlow\Operators\NotExistsOperator;
use RuleFlow\Operators\OperatorRegistry;

final class Engine
{
    private const REDACTED_VALUE = '[redacted]';

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

            ['matched' => $matched, 'checks' => $checks] = $this->evaluateNodes(
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
                $traceEntry['failure_reason'] = $this->firstFailureReason($checks);
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
     * @param list<Condition|ConditionGroup> $nodes
     * @param array<string,mixed> $context
     * @return array{
     *     matched:bool,
     *     checks:list<array<string,mixed>>
     * }
     */
    private function evaluateNodes(array $nodes, string $match, array $context): array
    {
        $checks = [];
        $matched = $match === Rule::MATCH_ALL;

        foreach ($nodes as $node) {
            $checkStartedAt = hrtime(true);

            if ($node instanceof ConditionGroup) {
                ['matched' => $groupMatched, 'checks' => $groupChecks] = $this->evaluateNodes(
                    $node->conditions(),
                    $node->match(),
                    $context
                );

                $check = [
                    'type' => 'group',
                    'match' => $node->match(),
                    'passed' => $groupMatched,
                    'duration_ms' => $this->durationSince($checkStartedAt),
                    'checks' => $groupChecks,
                ];

                if (!$groupMatched) {
                    $check['failure_reason'] = $this->firstFailureReason($groupChecks);
                }

                $checks[] = $check;
                $passed = $groupMatched;
            } else {
                $exists = $this->fields->exists($context, $node->field());
                $actual = $this->fields->get($context, $node->field());
                $operator = $this->operators->get($node->operator());
                $operatorInput = $this->usesExistenceInput($node->operator()) ? $exists : $actual;
                $passed = $operator->evaluate($operatorInput, $node->value());

                $check = [
                    'field' => $node->field(),
                    'sensitive' => $node->sensitive(),
                    'exists' => $exists,
                    'missing' => !$exists,
                    'actual' => $this->traceValue($node->sensitive(), $actual),
                    'operator' => $node->operator(),
                    'expected' => $this->traceValue($node->sensitive(), $node->value()),
                    'passed' => $passed,
                    'duration_ms' => $this->durationSince($checkStartedAt),
                ];

                if (!$passed) {
                    $check['failure_reason'] = $this->conditionFailureReason(
                        $node->operator(),
                        $exists,
                        $actual,
                        $node->value()
                    );
                }

                $checks[] = $check;
            }

            if ($match === Rule::MATCH_ALL && !$passed) {
                $matched = false;
            } elseif ($match === Rule::MATCH_ANY && $passed) {
                $matched = true;
            }
        }

        return [
            'matched' => $matched,
            'checks' => $checks,
        ];
    }

    private function usesExistenceInput(string $operator): bool
    {
        return in_array(
            $operator,
            [
                (new ExistsOperator())->name(),
                (new NotExistsOperator())->name(),
            ],
            true
        );
    }

    private function conditionFailureReason(
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
    private function firstFailureReason(array $checks): ?string
    {
        foreach ($checks as $check) {
            if (($check['passed'] ?? false) === true) {
                continue;
            }

            if (is_string($check['failure_reason'] ?? null)) {
                return $check['failure_reason'];
            }

            if (isset($check['checks']) && is_array($check['checks'])) {
                $nestedFailureReason = $this->firstFailureReason($check['checks']);

                if ($nestedFailureReason !== null) {
                    return $nestedFailureReason;
                }
            }
        }

        return null;
    }

    private function traceValue(bool $sensitive, mixed $value): mixed
    {
        if (!$sensitive || $value === null) {
            return $value;
        }

        return self::REDACTED_VALUE;
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
