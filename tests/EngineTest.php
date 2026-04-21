<?php

declare(strict_types=1);

namespace RuleFlow\Tests;

use PHPUnit\Framework\TestCase;
use RuleFlow\Engine;
use RuleFlow\Operators\OperatorRegistry;
use RuleFlow\RuleSet;
use RuleFlow\Tests\Fixtures\RegexOperator;

final class EngineTest extends TestCase
{
    public function testItMatchesTheFirstPassingRuleByPriority(): void
    {
        $rules = [
            [
                'name' => 'low_priority_rule',
                'priority' => 10,
                'conditions' => [
                    ['field' => 'order.amount', 'operator' => '>', 'value' => 100],
                ],
                'action' => 'review',
            ],
            [
                'name' => 'high_priority_rule',
                'priority' => 100,
                'conditions' => [
                    ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
                ],
                'action' => 'reject',
                'reason' => 'High amount order.',
            ],
        ];

        $result = Engine::make(RuleSet::fromArray($rules))->evaluate([
            'order' => ['amount' => 1299],
        ]);

        self::assertTrue($result->matched());
        self::assertSame('high_priority_rule', $result->rule()?->name());
        self::assertSame('reject', $result->action());
        self::assertSame('High amount order.', $result->reason());
    }

    public function testItReturnsTraceForPassedAndFailedConditions(): void
    {
        $rules = [
            [
                'name' => 'high_risk_order',
                'conditions' => [
                    ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
                    ['field' => 'user.risk_score', 'operator' => '<', 'value' => 60],
                ],
                'action' => 'reject',
            ],
        ];

        $result = Engine::make(RuleSet::fromArray($rules))->evaluate([
            'order' => ['amount' => 1299],
            'user' => ['risk_score' => 45],
        ]);

        $trace = $result->trace()->toArray();

        self::assertCount(1, $trace);
        self::assertTrue($trace[0]['matched']);
        self::assertTrue($trace[0]['checks'][0]['passed']);
        self::assertSame(1299, $trace[0]['checks'][0]['actual']);
        self::assertSame(1000, $trace[0]['checks'][0]['expected']);
    }

    public function testItReturnsNoMatchWhenConditionsFail(): void
    {
        $rules = [
            [
                'name' => 'high_risk_order',
                'conditions' => [
                    ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
                ],
                'action' => 'reject',
            ],
        ];

        $result = Engine::make(RuleSet::fromArray($rules))->evaluate([
            'order' => ['amount' => 99],
        ]);

        self::assertFalse($result->matched());
        self::assertNull($result->action());
        self::assertFalse($result->trace()->toArray()[0]['checks'][0]['passed']);
    }

    public function testItSupportsAnyMatchRules(): void
    {
        $rules = [
            [
                'name' => 'suspicious_content',
                'match' => 'any',
                'conditions' => [
                    ['field' => 'post.content', 'operator' => 'contains', 'value' => 'free money'],
                    ['field' => 'post.report_count', 'operator' => '>=', 'value' => 3],
                ],
                'action' => 'manual_review',
            ],
        ];

        $result = Engine::make(RuleSet::fromArray($rules))->evaluate([
            'post' => [
                'content' => 'normal content',
                'report_count' => 5,
            ],
        ]);

        self::assertTrue($result->matched());
        self::assertSame('any', $result->trace()->toArray()[0]['match']);
        self::assertFalse($result->trace()->toArray()[0]['checks'][0]['passed']);
        self::assertTrue($result->trace()->toArray()[0]['checks'][1]['passed']);
    }

    public function testItSupportsCustomOperators(): void
    {
        $operators = OperatorRegistry::defaults();
        $operators->register(new RegexOperator());

        $rules = [
            [
                'name' => 'order_id_pattern',
                'conditions' => [
                    ['field' => 'order.id', 'operator' => 'regex', 'value' => '/^ORD-[0-9]+$/'],
                ],
                'action' => 'allow',
            ],
        ];

        $result = Engine::makeWithOperators(RuleSet::fromArray($rules), $operators)->evaluate([
            'order' => ['id' => 'ORD-1001'],
        ]);

        self::assertTrue($result->matched());
        self::assertSame('allow', $result->action());
    }

    public function testItSupportsNestedConditionGroups(): void
    {
        $rules = [
            [
                'name' => 'nested_review_rule',
                'match' => 'all',
                'conditions' => [
                    ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
                    [
                        'match' => 'any',
                        'conditions' => [
                            ['field' => 'user.risk_score', 'operator' => '<', 'value' => 60],
                            ['field' => 'user.country', 'operator' => 'in', 'value' => ['NG', 'RU']],
                        ],
                    ],
                ],
                'action' => 'manual_review',
            ],
        ];

        $result = Engine::make(RuleSet::fromArray($rules))->evaluate([
            'order' => ['amount' => 1299],
            'user' => ['risk_score' => 72, 'country' => 'NG'],
        ]);

        $trace = $result->trace()->toArray();

        self::assertTrue($result->matched());
        self::assertSame('manual_review', $result->action());
        self::assertTrue($trace[0]['matched']);
        self::assertSame('group', $trace[0]['checks'][1]['type']);
        self::assertSame('any', $trace[0]['checks'][1]['match']);
        self::assertTrue($trace[0]['checks'][1]['passed']);
        self::assertFalse($trace[0]['checks'][1]['checks'][0]['passed']);
        self::assertTrue($trace[0]['checks'][1]['checks'][1]['passed']);
    }

    public function testItCanCollectAllMatchedRules(): void
    {
        $rules = [
            [
                'name' => 'amount_review',
                'priority' => 100,
                'conditions' => [
                    ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
                ],
                'action' => 'manual_review',
                'reason' => 'Amount threshold reached.',
            ],
            [
                'name' => 'risk_hold',
                'priority' => 90,
                'conditions' => [
                    ['field' => 'user.risk_score', 'operator' => '<', 'value' => 60],
                ],
                'action' => 'hold',
                'reason' => 'Risk score threshold reached.',
            ],
            [
                'name' => 'non_match_rule',
                'conditions' => [
                    ['field' => 'user.country', 'operator' => '=', 'value' => 'US'],
                ],
                'action' => 'allow',
            ],
        ];

        $result = Engine::make(RuleSet::fromArray($rules))->evaluateAll([
            'order' => ['amount' => 1299],
            'user' => ['risk_score' => 45, 'country' => 'CN'],
        ]);

        self::assertTrue($result->matched());
        self::assertSame(['amount_review', 'risk_hold'], $result->ruleNames());
        self::assertSame(['manual_review', 'hold'], $result->actions());
        self::assertSame(
            ['Amount threshold reached.', 'Risk score threshold reached.'],
            $result->reasons()
        );
        self::assertSame('amount_review', $result->firstRule()?->name());
        self::assertCount(3, $result->trace()->toArray());
    }

    public function testItSupportsExistsAndNotExistsOperators(): void
    {
        $rules = [
            [
                'name' => 'missing_phone',
                'conditions' => [
                    ['field' => 'user.phone', 'operator' => 'not_exists'],
                ],
                'action' => 'manual_review',
            ],
            [
                'name' => 'email_present',
                'conditions' => [
                    ['field' => 'user.email', 'operator' => 'exists'],
                ],
                'action' => 'allow',
            ],
        ];

        $result = Engine::make(RuleSet::fromArray($rules))->evaluateAll([
            'user' => ['email' => 'user@example.com'],
        ]);

        self::assertSame(['missing_phone', 'email_present'], $result->ruleNames());
        self::assertFalse($result->trace()->toArray()[0]['checks'][0]['exists']);
        self::assertTrue($result->trace()->toArray()[1]['checks'][0]['exists']);
    }

    public function testItSupportsBuiltInRegexOperator(): void
    {
        $rules = [
            [
                'name' => 'order_id_pattern',
                'conditions' => [
                    ['field' => 'order.id', 'operator' => 'regex', 'value' => '/^ORD-[0-9]+$/'],
                ],
                'action' => 'allow',
            ],
        ];

        $result = Engine::make(RuleSet::fromArray($rules))->evaluate([
            'order' => ['id' => 'ORD-1001'],
        ]);

        self::assertTrue($result->matched());
        self::assertSame('allow', $result->action());
    }
}
