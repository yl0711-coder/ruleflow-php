<?php

declare(strict_types=1);

namespace RuleFlow\Laravel;

use Illuminate\Contracts\Cache\Repository;
use RuleFlow\Loaders\RuleSetCacheInterface;
use RuleFlow\RuleSet;

final class LaravelRuleSetCache implements RuleSetCacheInterface
{
    public function __construct(private readonly Repository $cache)
    {
    }

    public function get(string $key): ?RuleSet
    {
        $value = $this->cache->get($key);

        return $value instanceof RuleSet ? $value : null;
    }

    public function put(string $key, RuleSet $ruleSet, int $ttlSeconds): void
    {
        $this->cache->put($key, $ruleSet, $ttlSeconds);
    }
}
