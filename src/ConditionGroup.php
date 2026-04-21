<?php

declare(strict_types=1);

namespace RuleFlow;

use RuleFlow\Exceptions\InvalidRuleException;

final class ConditionGroup
{
    /**
     * @param list<Condition|ConditionGroup> $conditions
     */
    public function __construct(
        private readonly array $conditions,
        private readonly string $match = Rule::MATCH_ALL
    ) {
        if ($conditions === []) {
            throw new InvalidRuleException('Condition group must contain at least one condition.');
        }

        if (!in_array($match, [Rule::MATCH_ALL, Rule::MATCH_ANY], true)) {
            throw new InvalidRuleException('Condition group match must be either [all] or [any].');
        }
    }

    /**
     * @param array<string,mixed> $definition
     */
    public static function fromArray(array $definition): self
    {
        if (!array_key_exists('conditions', $definition)) {
            throw new InvalidRuleException('Condition group is missing required key [conditions].');
        }

        if (!is_array($definition['conditions'])) {
            throw new InvalidRuleException('Condition group conditions must be an array.');
        }

        if (
            array_key_exists('match', $definition)
            && !is_string($definition['match'])
        ) {
            throw new InvalidRuleException('Condition group match must be a string.');
        }

        $conditions = [];

        foreach ($definition['conditions'] as $index => $condition) {
            if (!is_array($condition)) {
                throw new InvalidRuleException("Condition group condition [{$index}] must be an array.");
            }

            $conditions[] = self::parseConditionNode($condition);
        }

        return new self(
            $conditions,
            isset($definition['match']) ? (string) $definition['match'] : Rule::MATCH_ALL
        );
    }

    /**
     * @param array<string,mixed> $definition
     */
    public static function parseConditionNode(array $definition): Condition|ConditionGroup
    {
        if (array_key_exists('conditions', $definition)) {
            return self::fromArray($definition);
        }

        return Condition::fromArray($definition);
    }

    /**
     * @return list<Condition|ConditionGroup>
     */
    public function conditions(): array
    {
        return $this->conditions;
    }

    public function match(): string
    {
        return $this->match;
    }

    public function matchesAllConditions(): bool
    {
        return $this->match === Rule::MATCH_ALL;
    }
}
