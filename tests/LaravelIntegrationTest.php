<?php

declare(strict_types=1);

namespace RuleFlow\Tests;

use Orchestra\Testbench\TestCase;
use RuleFlow\Laravel\Facades\RuleFlow as RuleFlowFacade;
use RuleFlow\Laravel\RuleFlowServiceProvider;
use RuleFlow\RuleFlow;

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
}
