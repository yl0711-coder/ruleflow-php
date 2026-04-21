<?php

declare(strict_types=1);

namespace RuleFlow\Tests;

use Orchestra\Testbench\TestCase;
use RuleFlow\Laravel\Facades\RuleFlow as RuleFlowFacade;
use RuleFlow\Laravel\LaravelRuleSetCache;
use RuleFlow\Laravel\RuleFlowServiceProvider;
use RuleFlow\Loaders\RuleSetCacheInterface;
use RuleFlow\RuleFlow;
use RuleFlow\RuleSet;

final class LaravelIntegrationTest extends TestCase
{
    /**
     * @param mixed $app
     * @return list<class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            RuleFlowServiceProvider::class,
        ];
    }

    /**
     * @param mixed $app
     * @return array<string,class-string>
     */
    protected function getPackageAliases($app): array
    {
        return [
            'RuleFlow' => RuleFlowFacade::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('ruleflow.rules', [
            [
                'name' => 'high_risk_order',
                'conditions' => [
                    ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
                ],
                'action' => 'manual_review',
                'reason' => 'High amount order.',
            ],
        ]);
    }

    public function testItResolvesRuleFlowFromLaravelContainer(): void
    {
        $ruleFlow = $this->app->make(RuleFlow::class);

        $result = $ruleFlow->evaluate([
            'order' => [
                'amount' => 1299,
            ],
        ]);

        self::assertTrue($result->matched());
        self::assertSame('manual_review', $result->action());
        self::assertSame('High amount order.', $result->reason());
    }

    public function testItEvaluatesRulesThroughLaravelFacade(): void
    {
        $result = RuleFlowFacade::evaluate([
            'order' => [
                'amount' => 1299,
            ],
        ]);

        self::assertTrue($result->matched());
        self::assertSame('manual_review', $result->action());
    }

    public function testItCanEvaluateAllRulesThroughLaravelContainer(): void
    {
        $this->app['config']->set('ruleflow.rules', [
            [
                'name' => 'amount_review',
                'conditions' => [
                    ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
                ],
                'action' => 'manual_review',
            ],
            [
                'name' => 'vip_review',
                'conditions' => [
                    ['field' => 'user.level', 'operator' => '>=', 'value' => 5],
                ],
                'action' => 'priority_review',
            ],
        ]);

        $ruleFlow = $this->app->make(RuleFlow::class);
        $result = $ruleFlow->evaluateAll([
            'order' => [
                'amount' => 1299,
            ],
            'user' => [
                'level' => 6,
            ],
        ]);

        self::assertTrue($result->matched());
        self::assertSame(['amount_review', 'vip_review'], $result->ruleNames());
    }

    public function testItUsesLaravelCacheDriverWhenConfigured(): void
    {
        $this->app['config']->set('cache.default', 'array');
        $this->app['config']->set('ruleflow.cache.enabled', true);
        $this->app['config']->set('ruleflow.cache.driver', 'laravel');
        $this->app['config']->set('ruleflow.cache.store', 'array');

        $cache = $this->app->make(RuleSetCacheInterface::class);

        self::assertInstanceOf(LaravelRuleSetCache::class, $cache);

        $ruleSet = RuleSet::fromArray([
            [
                'name' => 'cached_rule',
                'conditions' => [
                    ['field' => 'user.id', 'operator' => '>', 'value' => 0],
                ],
                'action' => 'allow',
            ],
        ]);

        $cache->put('ruleflow.test', $ruleSet, 60);

        self::assertInstanceOf(RuleSet::class, $cache->get('ruleflow.test'));
        self::assertSame('cached_rule', $cache->get('ruleflow.test')?->rules()[0]->name());
    }
}
