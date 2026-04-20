<?php

declare(strict_types=1);

namespace RuleFlow\Loaders;

use RuleFlow\RuleSet;

final class InMemoryRuleSetCache implements RuleSetCacheInterface
{
    /** @var array<string,array{expires_at:int,rule_set:RuleSet}> */
    private array $items = [];

    public function get(string $key): ?RuleSet
    {
        if (!isset($this->items[$key])) {
            return null;
        }

        if ($this->items[$key]['expires_at'] < time()) {
            unset($this->items[$key]);
            return null;
        }

        return $this->items[$key]['rule_set'];
    }

    public function put(string $key, RuleSet $ruleSet, int $ttlSeconds): void
    {
        $this->items[$key] = [
            'expires_at' => time() + $ttlSeconds,
            'rule_set' => $ruleSet,
        ];
    }
}
