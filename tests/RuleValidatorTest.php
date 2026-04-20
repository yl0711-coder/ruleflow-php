<?php

declare(strict_types=1);

namespace RuleFlow\Tests;

use PHPUnit\Framework\TestCase;
use RuleFlow\Operators\OperatorRegistry;
use RuleFlow\Tests\Fixtures\RegexOperator;
use RuleFlow\Validation\RuleValidator;

final class RuleValidatorTest extends TestCase
{
    public function testItPassesValidRules(): void
    {
        $result = RuleValidator::defaults()->validate([
            [
                'name' => 'high_risk_order',
                'match' => 'all',
                'conditions' => [
                    ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
                ],
                'action' => 'reject',
            ],
        ]);

        self::assertTrue($result->valid());
        self::assertSame([], $result->errors());
    }

    public function testItReportsInvalidRules(): void
    {
        $result = RuleValidator::defaults()->validate([
            [
                'name' => '',
                'match' => 'sometimes',
                'conditions' => [
                    ['field' => '', 'operator' => 'unknown', 'value' => true],
                ],
                'action' => '',
            ],
        ]);

        self::assertFalse($result->valid());
        self::assertContains('rules[0].name must be a non-empty string.', $result->errors());
        self::assertContains('rules[0].match must be either [all] or [any].', $result->errors());
        self::assertContains('rules[0].action must be a non-empty string.', $result->errors());
        self::assertContains('rules[0].conditions[0].field must be a non-empty string.', $result->errors());
        self::assertContains('rules[0].conditions[0].operator [unknown] is not registered.', $result->errors());
    }

    public function testItReportsDuplicatedRuleNames(): void
    {
        $result = RuleValidator::defaults()->validate([
            [
                'name' => 'duplicated',
                'conditions' => [
                    ['field' => 'user.id', 'operator' => '>', 'value' => 0],
                ],
                'action' => 'allow',
            ],
            [
                'name' => 'duplicated',
                'conditions' => [
                    ['field' => 'user.id', 'operator' => '>', 'value' => 0],
                ],
                'action' => 'allow',
            ],
        ]);

        self::assertFalse($result->valid());
        self::assertContains('rules[1].name [duplicated] is duplicated.', $result->errors());
    }

    public function testItRespectsCustomOperators(): void
    {
        $operators = OperatorRegistry::defaults();
        $operators->register(new RegexOperator());

        $result = (new RuleValidator($operators))->validate([
            [
                'name' => 'order_id_pattern',
                'conditions' => [
                    ['field' => 'order.id', 'operator' => 'regex', 'value' => '/^ORD-[0-9]+$/'],
                ],
                'action' => 'allow',
            ],
        ]);

        self::assertTrue($result->valid());
    }
}
