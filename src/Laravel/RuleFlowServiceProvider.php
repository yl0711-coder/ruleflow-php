<?php

declare(strict_types=1);

namespace RuleFlow\Laravel;

use Illuminate\Support\ServiceProvider;
use RuleFlow\Laravel\Commands\ValidateRulesCommand;
use RuleFlow\Loaders\ArrayRuleLoader;
use RuleFlow\Loaders\CachedRuleLoader;
use RuleFlow\Loaders\InMemoryRuleSetCache;
use RuleFlow\Loaders\RuleLoaderInterface;
use RuleFlow\Loaders\RuleSetCacheInterface;
use RuleFlow\RuleFlow;

final class RuleFlowServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/ruleflow.php', 'ruleflow');

        $this->app->bind(RuleLoaderInterface::class, function (): RuleLoaderInterface {
            return new ArrayRuleLoader((array) config('ruleflow.rules', []));
        });

        $this->app->bind(RuleSetCacheInterface::class, function (): RuleSetCacheInterface {
            $driver = (string) config('ruleflow.cache.driver', 'in_memory');

            if ($driver === 'laravel') {
                $store = config('ruleflow.cache.store');
                $repository = $store !== null
                    ? $this->app['cache']->store((string) $store)
                    : $this->app['cache']->store();

                return new LaravelRuleSetCache($repository);
            }

            return new InMemoryRuleSetCache();
        });

        $this->app->bind(RuleFlow::class, function (): RuleFlow {
            $loader = $this->app->make(RuleLoaderInterface::class);

            if ((bool) config('ruleflow.cache.enabled', false)) {
                $loader = new CachedRuleLoader(
                    $loader,
                    $this->app->make(RuleSetCacheInterface::class),
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

        if ($this->app->runningInConsole()) {
            $this->commands([
                ValidateRulesCommand::class,
            ]);
        }
    }
}
