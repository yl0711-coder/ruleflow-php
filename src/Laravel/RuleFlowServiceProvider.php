<?php

declare(strict_types=1);

namespace RuleFlow\Laravel;

use Illuminate\Support\ServiceProvider;
use RuleFlow\Loaders\ArrayRuleLoader;
use RuleFlow\Loaders\CachedRuleLoader;
use RuleFlow\Loaders\InMemoryRuleSetCache;
use RuleFlow\RuleFlow;

final class RuleFlowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/ruleflow.php', 'ruleflow');

        $this->app->singleton(RuleFlow::class, function (): RuleFlow {
            $loader = new ArrayRuleLoader((array) config('ruleflow.rules', []));

            if ((bool) config('ruleflow.cache.enabled', false)) {
                $loader = new CachedRuleLoader(
                    $loader,
                    new InMemoryRuleSetCache(),
                    (string) config('ruleflow.cache.key', 'ruleflow.rules'),
                    (int) config('ruleflow.cache.ttl', 300)
                );
            }

            return new RuleFlow($loader);
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/ruleflow.php' => config_path('ruleflow.php'),
        ], 'ruleflow-config');
    }
}
