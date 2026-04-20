<?php

declare(strict_types=1);

namespace RuleFlow\Loaders;

use RuleFlow\RuleSet;

final class CachedRuleLoader implements RuleLoaderInterface
{
    public function __construct(
        private readonly RuleLoaderInterface $loader,
        private readonly RuleSetCacheInterface $cache,
        private readonly string $key,
        private readonly int $ttlSeconds = 300
    ) {
    }

    public function load(): RuleSet
    {
        $cached = $this->cache->get($this->key);

        if ($cached instanceof RuleSet) {
            return $cached;
        }

        $ruleSet = $this->loader->load();
        $this->cache->put($this->key, $ruleSet, $this->ttlSeconds);

        return $ruleSet;
    }
}
