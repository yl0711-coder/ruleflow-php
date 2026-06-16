<?php

declare(strict_types=1);

namespace RuleFlow\Tests;

use PHPUnit\Framework\TestCase;
use RuleFlow\Operators\OperatorRegistry;
use RuleFlow\Tests\Fixtures\IsMissingOperator;
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

    public function testCustomExistenceOperatorsAreAllowedWithoutValue(): void
    {
        $operators = OperatorRegistry::defaults();
        $operators->register(new IsMissingOperator());

        $result = (new RuleValidator($operators))->validate([
            [
                'name' => 'missing_coupon',
                'conditions' => [
                    ['field' => 'order.coupon', 'operator' => 'is_missing'],
                ],
                'action' => 'allow',
            ],
        ]);

        self::assertTrue($result->valid());
    }

    public function testItValidatesNestedConditionGroups(): void
    {
        $result = RuleValidator::defaults()->validate([
            [
                'name' => 'nested_rule',
                'conditions' => [
                    [
                        'match' => 'any',
                        'conditions' => [
                            ['field' => 'user.score', 'operator' => '>=', 'value' => 80],
                            ['field' => 'user.country', 'operator' => 'in', 'value' => ['CN', 'SG']],
                        ],
                    ],
                ],
                'action' => 'allow',
            ],
        ]);

        self::assertTrue($result->valid());
    }

    public function testItAllowsExistsOperatorsWithoutValue(): void
    {
        $result = RuleValidator::defaults()->validate([
            [
                'name' => 'missing_phone',
                'conditions' => [
                    ['field' => 'user.phone', 'operator' => 'not_exists', 'sensitive' => true],
                    ['field' => 'user.email', 'operator' => 'exists'],
                ],
                'action' => 'manual_review',
            ],
        ]);

        self::assertTrue($result->valid());
    }

    public function testItReportsInvalidSensitiveFlags(): void
    {
        $result = RuleValidator::defaults()->validate([
            [
                'name' => 'invalid_sensitive_flag',
                'conditions' => [
                    ['field' => 'user.phone', 'operator' => 'exists', 'sensitive' => 'yes'],
                ],
                'action' => 'manual_review',
            ],
        ]);

        self::assertFalse($result->valid());
        self::assertContains('rules[0].conditions[0].sensitive must be a boolean.', $result->errors());
    }

    public function testItValidatesRuleMetadata(): void
    {
        $valid = RuleValidator::defaults()->validate([
            [
                'name' => 'campaign_eligibility',
                'conditions' => [
                    ['field' => 'user.segment', 'operator' => '=', 'value' => 'vip'],
                ],
                'action' => 'allow_campaign',
                'metadata' => [
                    'owner' => 'growth',
                    'version' => '2026-05',
                ],
            ],
        ]);

        $invalidType = RuleValidator::defaults()->validate([
            [
                'name' => 'invalid_metadata',
                'conditions' => [
                    ['field' => 'user.id', 'operator' => 'exists'],
                ],
                'action' => 'allow',
                'metadata' => 'growth',
            ],
        ]);

        $invalidKeys = RuleValidator::defaults()->validate([
            [
                'name' => 'invalid_metadata_keys',
                'conditions' => [
                    ['field' => 'user.id', 'operator' => 'exists'],
                ],
                'action' => 'allow',
                'metadata' => ['growth'],
            ],
        ]);

        self::assertTrue($valid->valid());
        self::assertFalse($invalidType->valid());
        self::assertFalse($invalidKeys->valid());
        self::assertContains('rules[0].metadata must be an object.', $invalidType->errors());
        self::assertContains('rules[0].metadata keys must be strings.', $invalidKeys->errors());
    }

    public function testItReportsInvalidNestedConditionGroups(): void
    {
        $result = RuleValidator::defaults()->validate([
            [
                'name' => 'nested_rule',
                'conditions' => [
                    [
                        'match' => 'sometimes',
                        'conditions' => [
                            ['field' => '', 'operator' => 'unknown', 'value' => true],
                        ],
                    ],
                ],
                'action' => 'allow',
            ],
        ]);

        self::assertFalse($result->valid());
        self::assertContains('rules[0].conditions[0].match must be either [all] or [any].', $result->errors());
        self::assertContains(
            'rules[0].conditions[0].conditions[0].field must be a non-empty string.',
            $result->errors()
        );
        self::assertContains(
            'rules[0].conditions[0].conditions[0].operator [unknown] is not registered.',
            $result->errors()
        );
    }

    public function testItReportsInvalidRegexPatterns(): void
    {
        $result = RuleValidator::defaults()->validate([
            [
                'name' => 'broken_regex',
                'conditions' => [
                    ['field' => 'email', 'operator' => 'regex', 'value' => '/[unclosed'],
                ],
                'action' => 'flag',
            ],
            [
                'name' => 'non_string_regex',
                'conditions' => [
                    ['field' => 'email', 'operator' => 'regex', 'value' => 123],
                ],
                'action' => 'flag',
            ],
        ]);

        self::assertFalse($result->valid());
        self::assertContains(
            'rules[0].conditions[0].value must be a valid regex pattern.',
            $result->errors()
        );
        self::assertContains(
            'rules[1].conditions[0].value must be a non-empty string regex pattern.',
            $result->errors()
        );
    }

    public function testItAcceptsValidRegexPatterns(): void
    {
        $result = RuleValidator::defaults()->validate([
            [
                'name' => 'valid_regex',
                'conditions' => [
                    ['field' => 'email', 'operator' => 'regex', 'value' => '/@example\\.com$/'],
                ],
                'action' => 'flag',
            ],
        ]);

        self::assertTrue($result->valid());
    }

    public function testItReportsMalformedBetweenValues(): void
    {
        $result = RuleValidator::defaults()->validate([
            [
                'name' => 'single_bound',
                'conditions' => [
                    ['field' => 'age', 'operator' => 'between', 'value' => [18]],
                ],
                'action' => 'allow',
            ],
            [
                'name' => 'non_numeric_bounds',
                'conditions' => [
                    ['field' => 'age', 'operator' => 'between', 'value' => ['low', 'high']],
                ],
                'action' => 'allow',
            ],
            [
                'name' => 'inverted_bounds',
                'conditions' => [
                    ['field' => 'age', 'operator' => 'between', 'value' => [65, 18]],
                ],
                'action' => 'allow',
            ],
        ]);

        self::assertFalse($result->valid());
        self::assertContains(
            'rules[0].conditions[0].value must be an array of exactly two numeric values.',
            $result->errors()
        );
        self::assertContains(
            'rules[1].conditions[0].value must be an array of exactly two numeric values.',
            $result->errors()
        );
        self::assertContains(
            'rules[2].conditions[0].value minimum must not be greater than maximum.',
            $result->errors()
        );
    }

    public function testItReportsNonArrayInValues(): void
    {
        $result = RuleValidator::defaults()->validate([
            [
                'name' => 'in_scalar',
                'conditions' => [
                    ['field' => 'status', 'operator' => 'in', 'value' => 'active'],
                ],
                'action' => 'allow',
            ],
            [
                'name' => 'not_in_empty',
                'conditions' => [
                    ['field' => 'status', 'operator' => 'not_in', 'value' => []],
                ],
                'action' => 'allow',
            ],
        ]);

        self::assertFalse($result->valid());
        self::assertContains('rules[0].conditions[0].value must be a non-empty array.', $result->errors());
        self::assertContains('rules[1].conditions[0].value must be a non-empty array.', $result->errors());
    }

    public function testItValidatesOperatorValuesInsideNestedGroups(): void
    {
        $result = RuleValidator::defaults()->validate([
            [
                'name' => 'nested_value_check',
                'conditions' => [
                    [
                        'match' => 'any',
                        'conditions' => [
                            ['field' => 'email', 'operator' => 'regex', 'value' => '/[unclosed'],
                        ],
                    ],
                ],
                'action' => 'flag',
            ],
        ]);

        self::assertFalse($result->valid());
        self::assertContains(
            'rules[0].conditions[0].conditions[0].value must be a valid regex pattern.',
            $result->errors()
        );
    }
}
