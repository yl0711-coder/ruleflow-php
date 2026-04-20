<?php

declare(strict_types=1);

namespace RuleFlow\Laravel\Facades;

use Illuminate\Support\Facades\Facade;

final class RuleFlow extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RuleFlow\RuleFlow::class;
    }
}
