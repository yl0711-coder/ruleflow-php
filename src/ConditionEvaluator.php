<?php

declare(strict_types=1);

namespace RuleFlow;

use RuleFlow\Operators\ExistsOperator;
use RuleFlow\Operators\NotExistsOperator;
use RuleFlow\Operators\OperatorRegistry;

/**
 * @internal
 */
final class ConditionEvaluator
{
    private const REDACTED_VALUE = '[redacted]';

    public function __construct(
        private readonly OperatorRegistry $operators,
        private readonly FieldAccessor $fields,
        private readonly FailureReasonResolver $failureReasons
    ) {
    }

    /**
     * @param list<Condition|ConditionGroup> $nodes
     * @param array<string,mixed> $context
     * @return array{
     *     matched:bool,
     *     checks:list<array<string,mixed>>
     * }
     */
    public function evaluate(array $nodes, string $match, array $context): array
    {
        $checks = [];
        $matched = $match === Rule::MATCH_ALL;

        foreach ($nodes as $node) {
            $checkStartedAt = hrtime(true);

            if ($node instanceof ConditionGroup) {
                ['matched' => $passed, 'checks' => $groupChecks] = $this->evaluate(
                    $node->conditions(),
                    $node->match(),
                    $context
                );

                $checks[] = $this->groupCheck($node, $passed, $groupChecks, $checkStartedAt);
            } else {
                ['passed' => $passed, 'check' => $check] = $this->conditionCheck($node, $context, $checkStartedAt);

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

    /**
     * @param array<string,mixed> $context
     * @return array{
     *     passed:bool,
     *     check:array<string,mixed>
     * }
     */
    private function conditionCheck(Condition $node, array $context, int $startedAt): array
    {
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
            'duration_ms' => $this->durationSince($startedAt),
        ];

        if (!$passed) {
            $check['failure_reason'] = $this->failureReasons->forCondition(
                $node->operator(),
                $exists,
                $actual,
                $node->value()
            );
        }

        return [
            'passed' => $passed,
            'check' => $check,
        ];
    }

    /**
     * @param list<array<string,mixed>> $checks
     * @return array<string,mixed>
     */
    private function groupCheck(ConditionGroup $node, bool $passed, array $checks, int $startedAt): array
    {
        $check = [
            'type' => 'group',
            'match' => $node->match(),
            'passed' => $passed,
            'duration_ms' => $this->durationSince($startedAt),
            'checks' => $checks,
        ];

        if (!$passed) {
            $check['failure_reason'] = $this->failureReasons->first($checks);
        }

        return $check;
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

    private function traceValue(bool $sensitive, mixed $value): mixed
    {
        if (!$sensitive || $value === null) {
            return $value;
        }

        return self::REDACTED_VALUE;
    }

    private function durationSince(int $startedAt): float
    {
        return round((hrtime(true) - $startedAt) / 1_000_000, 3);
    }
}
