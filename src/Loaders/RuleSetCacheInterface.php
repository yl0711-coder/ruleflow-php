<?php

declare(strict_types=1);

namespace RuleFlow\Loaders;

use RuleFlow\RuleSet;

interface RuleSetCacheInterface
{
    public function get(string $key): ?RuleSet;

    public function put(string $key, RuleSet $ruleSet, int $ttlSeconds): void;
}
