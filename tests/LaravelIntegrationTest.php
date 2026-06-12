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

    public function testItEvaluatesEloquentModelAttributesAsContext(): void
    {
        $order = new class extends \Illuminate\Database\Eloquent\Model {
            protected $guarded = [];
        };
        $order->forceFill(['amount' => 1299]);

        $result = RuleFlowFacade::evaluate(['order' => $order]);

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

        $cachedRuleSet = $cache->get('ruleflow.test');

        self::assertInstanceOf(RuleSet::class, $cachedRuleSet);
        self::assertSame('cached_rule', $cachedRuleSet->rules()[0]->name());
    }

    public function testItReadsUpdatedConfigWhenRuleCacheIsDisabled(): void
    {
        $this->app['config']->set('ruleflow.cache.enabled', false);
        $this->app['config']->set('ruleflow.rules', [
            [
                'name' => 'first_config_rule',
                'conditions' => [
                    ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
                ],
                'action' => 'manual_review',
            ],
        ]);

        $first = $this->app->make(RuleFlow::class)->evaluate([
            'order' => [
                'amount' => 1200,
            ],
        ]);

        self::assertTrue($first->matched());
        self::assertSame('manual_review', $first->action());

        $this->app['config']->set('ruleflow.rules', [
            [
                'name' => 'second_config_rule',
                'conditions' => [
                    ['field' => 'order.amount', 'operator' => '>', 'value' => 500],
                ],
                'action' => 'hold_for_review',
            ],
        ]);

        $second = $this->app->make(RuleFlow::class)->evaluate([
            'order' => [
                'amount' => 700,
            ],
        ]);

        self::assertTrue($second->matched());
        self::assertSame('hold_for_review', $second->action());
    }

    public function testItReusesCachedRulesUntilCacheKeyChanges(): void
    {
        $this->app['config']->set('cache.default', 'array');
        $this->app['config']->set('ruleflow.cache.enabled', true);
        $this->app['config']->set('ruleflow.cache.driver', 'laravel');
        $this->app['config']->set('ruleflow.cache.store', 'array');
        $this->app['config']->set('ruleflow.cache.key', 'ruleflow.integration.cache.first');
        $this->app['config']->set('ruleflow.rules', [
            [
                'name' => 'cached_first_rule',
                'conditions' => [
                    ['field' => 'order.amount', 'operator' => '>', 'value' => 100],
                ],
                'action' => 'first_action',
            ],
        ]);

        $first = $this->app->make(RuleFlow::class)->evaluate([
            'order' => [
                'amount' => 200,
            ],
        ]);

        self::assertTrue($first->matched());
        self::assertSame('first_action', $first->action());

        $this->app['config']->set('ruleflow.rules', [
            [
                'name' => 'cached_second_rule',
                'conditions' => [
                    ['field' => 'order.amount', 'operator' => '>', 'value' => 100],
                ],
                'action' => 'second_action',
            ],
        ]);

        $cached = $this->app->make(RuleFlow::class)->evaluate([
            'order' => [
                'amount' => 200,
            ],
        ]);

        self::assertTrue($cached->matched());
        self::assertSame('first_action', $cached->action());

        $this->app['config']->set('ruleflow.cache.key', 'ruleflow.integration.cache.second');

        $second = $this->app->make(RuleFlow::class)->evaluate([
            'order' => [
                'amount' => 200,
            ],
        ]);

        self::assertTrue($second->matched());
        self::assertSame('second_action', $second->action());
    }

    public function testItProvidesAnArtisanValidationCommand(): void
    {
        $this->artisan('ruleflow:validate')
            ->expectsOutput('RuleFlow validation passed. 1 rule(s) checked.')
            ->assertSuccessful();
    }

    public function testItReportsValidationErrorsThroughArtisanCommand(): void
    {
        $this->app['config']->set('ruleflow.rules', [
            [
                'name' => '',
                'conditions' => [
                    ['field' => '', 'operator' => 'unknown', 'value' => true],
                ],
                'action' => '',
            ],
        ]);

        $this->artisan('ruleflow:validate')
            ->expectsOutput('RuleFlow validation failed.')
            ->expectsOutput('- rules[0].name must be a non-empty string.')
            ->expectsOutput('- rules[0].action must be a non-empty string.')
            ->expectsOutput('- rules[0].conditions[0].field must be a non-empty string.')
            ->expectsOutput('- rules[0].conditions[0].operator [unknown] is not registered.')
            ->assertFailed();
    }

    public function testItResolvesFreshRuleFlowInstancesFromContainer(): void
    {
        $first = $this->app->make(RuleFlow::class);
        $second = $this->app->make(RuleFlow::class);

        self::assertNotSame($first, $second);
    }
}
