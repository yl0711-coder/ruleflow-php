<?php

declare(strict_types=1);

namespace RuleFlow;

use RuleFlow\Exceptions\InvalidRuleException;

final class Rule
{
    public const MATCH_ALL = 'all';
    public const MATCH_ANY = 'any';

    /**
     * @param list<Condition|ConditionGroup> $conditions
     */
    public function __construct(
        private readonly string $name,
        private readonly array $conditions,
        private readonly string $action,
        private readonly ?string $reason = null,
        private readonly int $priority = 0,
        private readonly bool $enabled = true,
        private readonly string $match = self::MATCH_ALL
    ) {
        if ($name === '') {
            throw new InvalidRuleException('Rule name cannot be empty.');
        }

        if ($conditions === []) {
            throw new InvalidRuleException("Rule [{$name}] must contain at least one condition.");
        }

        if ($action === '') {
            throw new InvalidRuleException("Rule [{$name}] action cannot be empty.");
        }

        if (!in_array($match, [self::MATCH_ALL, self::MATCH_ANY], true)) {
            throw new InvalidRuleException("Rule [{$name}] match must be either [all] or [any].");
        }
    }

    /**
     * @param array{
     *     name:string,
     *     conditions:list<array<string,mixed>>,
     *     action:string,
     *     reason?:string,
     *     priority?:int,
     *     enabled?:bool,
     *     match?:string
     * } $definition
     */
    public static function fromArray(array $definition): self
    {
        foreach (['name', 'conditions', 'action'] as $key) {
            if (!array_key_exists($key, $definition)) {
                throw new InvalidRuleException("Rule is missing required key [{$key}].");
            }
        }

        if (!is_array($definition['conditions'])) {
            throw new InvalidRuleException('Rule conditions must be an array.');
        }

        if (!is_string($definition['name'])) {
            throw new InvalidRuleException('Rule name must be a string.');
        }

        if (!is_string($definition['action'])) {
            throw new InvalidRuleException("Rule [{$definition['name']}] action must be a string.");
        }

        if (
            array_key_exists('reason', $definition)
            && $definition['reason'] !== null
            && !is_scalar($definition['reason'])
        ) {
            throw new InvalidRuleException("Rule [{$definition['name']}] reason must be a string or null.");
        }

        if (
            array_key_exists('match', $definition)
            && !is_string($definition['match'])
        ) {
            throw new InvalidRuleException("Rule [{$definition['name']}] match must be a string.");
        }

        $conditions = [];

        foreach ($definition['conditions'] as $index => $condition) {
            if (!is_array($condition)) {
                throw new InvalidRuleException("Rule [{$definition['name']}] condition [{$index}] must be an array.");
            }

            $conditions[] = ConditionGroup::parseConditionNode($condition);
        }

        return new self(
            (string) $definition['name'],
            $conditions,
            (string) $definition['action'],
            isset($definition['reason']) ? (string) $definition['reason'] : null,
            isset($definition['priority']) ? (int) $definition['priority'] : 0,
            isset($definition['enabled']) ? (bool) $definition['enabled'] : true,
            isset($definition['match']) ? (string) $definition['match'] : self::MATCH_ALL
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return list<Condition|ConditionGroup>
     */
    public function conditions(): array
    {
        return $this->conditions;
    }

    public function action(): string
    {
        return $this->action;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }

    public function priority(): int
    {
        return $this->priority;
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function match(): string
    {
        return $this->match;
    }

    public function matchesAllConditions(): bool
    {
        return $this->match === self::MATCH_ALL;
    }
}
