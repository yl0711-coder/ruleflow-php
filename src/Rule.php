<?php

declare(strict_types=1);

namespace RuleFlow;

use RuleFlow\Exceptions\InvalidRuleException;

final class Rule
{
    public const MATCH_ALL = 'all';
    public const MATCH_ANY = 'any';

    /**
     * @param list<Condition> $conditions
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
     *     conditions:list<array{field:string,operator:string,value:mixed}>,
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

        return new self(
            (string) $definition['name'],
            array_map(
                static fn (array $condition): Condition => Condition::fromArray($condition),
                $definition['conditions']
            ),
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
     * @return list<Condition>
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
