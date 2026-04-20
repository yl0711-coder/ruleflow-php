<?php

declare(strict_types=1);

namespace RuleFlow\Loaders;

use RuleFlow\RuleSet;

final class ArrayRuleLoader implements RuleLoaderInterface
{
    /**
     * @param list<array<string,mixed>> $rules
     */
    public function __construct(private readonly array $rules)
    {
    }

    public function load(): RuleSet
    {
        return RuleSet::fromArray($this->rules);
    }
}
