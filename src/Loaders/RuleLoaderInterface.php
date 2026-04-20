<?php

declare(strict_types=1);

namespace RuleFlow\Loaders;

use RuleFlow\RuleSet;

interface RuleLoaderInterface
{
    public function load(): RuleSet;
}
