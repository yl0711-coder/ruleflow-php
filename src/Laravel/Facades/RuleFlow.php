<?php

declare(strict_types=1);

namespace RuleFlow\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static \RuleFlow\EvaluationResult evaluate(array<string,mixed> $context)
 * @method static \RuleFlow\MultiEvaluationResult evaluateAll(array<string,mixed> $context)
 */
final class RuleFlow extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RuleFlow\RuleFlow::class;
    }
}
