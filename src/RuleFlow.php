<?php

declare(strict_types=1);

namespace RuleFlow;

use RuleFlow\Loaders\RuleLoaderInterface;

final class RuleFlow
{
    public function __construct(private readonly RuleLoaderInterface $loader)
    {
    }

    /**
     * @param array<string,mixed> $context
     */
    public function evaluate(array $context): EvaluationResult
    {
        return Engine::make($this->loader->load())->evaluate($context);
    }
}
