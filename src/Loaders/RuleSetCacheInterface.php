<?php

declare(strict_types=1);

namespace RuleFlow\Loaders;

use RuleFlow\RuleSet;

interface RuleSetCacheInterface
{
    /**
     * Returns a cached RuleSet or null on cache miss.
     *
     * Cache implementations should ignore invalid or unserializable cached
     * values and return null instead of leaking backend-specific values.
     */
    public function get(string $key): ?RuleSet;

    /**
     * Stores a RuleSet for the given number of seconds.
     *
     * `$ttlSeconds` is a relative TTL. Implementations may map it to the
     * underlying cache backend's native expiration format.
     */
    public function put(string $key, RuleSet $ruleSet, int $ttlSeconds): void;
}
