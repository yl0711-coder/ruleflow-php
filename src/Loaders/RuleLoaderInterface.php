<?php

declare(strict_types=1);

namespace RuleFlow\Loaders;

use RuleFlow\RuleSet;

interface RuleLoaderInterface
{
    /**
     * Loads and returns a validated rule set source.
     *
     * Implementations may load rules from arrays, JSON files, caches, databases,
     * or external services. Invalid rule definitions should be reported through
     * RuleFlow exceptions rather than PHP type errors.
     */
    public function load(): RuleSet;
}
