<?php

declare(strict_types=1);

namespace RuleFlow\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use RuleFlow\Operators\BetweenOperator;
use RuleFlow\Operators\ContainsOperator;
use RuleFlow\Operators\EndsWithOperator;
use RuleFlow\Operators\EqualsOperator;
use RuleFlow\Operators\GreaterThanOperator;
use RuleFlow\Operators\GreaterThanOrEqualsOperator;
use RuleFlow\Operators\InOperator;
use RuleFlow\Operators\LessThanOperator;
use RuleFlow\Operators\LessThanOrEqualsOperator;
use RuleFlow\Operators\NotEqualsOperator;
use RuleFlow\Operators\NotInOperator;
use RuleFlow\Operators\OperatorInterface;
use RuleFlow\Operators\OperatorRegistry;
use RuleFlow\Operators\StartsWithOperator;
use RuleFlow\Tests\Fixtures\RegexOperator;

final class OperatorTest extends TestCase
{
    /**
     * @param class-string<OperatorInterface> $operator
     */
    #[DataProvider('operatorCases')]
    public function testBuiltInOperators(string $operator, mixed $actual, mixed $expected, bool $passes): void
    {
        self::assertSame($passes, (new $operator())->evaluate($actual, $expected));
    }

    /**
     * @return iterable<string,array{class-string<OperatorInterface>,mixed,mixed,bool}>
     */
    public static function operatorCases(): iterable
    {
        yield 'equals passes with loose comparison' => [EqualsOperator::class, '123', 123, true];
        yield 'equals fails' => [EqualsOperator::class, '123', 456, false];

        yield 'not equals passes' => [NotEqualsOperator::class, '123', 456, true];
        yield 'not equals fails with loose comparison' => [NotEqualsOperator::class, '123', 123, false];

        yield 'greater than passes' => [GreaterThanOperator::class, 10, 5, true];
        yield 'greater than fails' => [GreaterThanOperator::class, 5, 10, false];
        yield 'greater than rejects non numeric actual' => [GreaterThanOperator::class, 'abc', 10, false];

        yield 'greater than or equals passes when greater' => [GreaterThanOrEqualsOperator::class, 10, 5, true];
        yield 'greater than or equals passes when equal' => [GreaterThanOrEqualsOperator::class, 10, 10, true];
        yield 'greater than or equals fails' => [GreaterThanOrEqualsOperator::class, 5, 10, false];

        yield 'less than passes' => [LessThanOperator::class, 5, 10, true];
        yield 'less than fails' => [LessThanOperator::class, 10, 5, false];
        yield 'less than rejects non numeric expected' => [LessThanOperator::class, 10, 'abc', false];

        yield 'less than or equals passes when lower' => [LessThanOrEqualsOperator::class, 5, 10, true];
        yield 'less than or equals passes when equal' => [LessThanOrEqualsOperator::class, 10, 10, true];
        yield 'less than or equals fails' => [LessThanOrEqualsOperator::class, 10, 5, false];

        yield 'in passes' => [InOperator::class, 'vip', ['vip', 'pro'], true];
        yield 'in fails' => [InOperator::class, 'basic', ['vip', 'pro'], false];
        yield 'in rejects non array expected' => [InOperator::class, 'vip', 'vip', false];

        yield 'not in passes' => [NotInOperator::class, 'basic', ['vip', 'pro'], true];
        yield 'not in fails' => [NotInOperator::class, 'vip', ['vip', 'pro'], false];
        yield 'not in rejects non array expected' => [NotInOperator::class, 'vip', 'vip', false];

        yield 'contains passes for string' => [ContainsOperator::class, 'hello risk engine', 'risk', true];
        yield 'contains fails for string' => [ContainsOperator::class, 'hello risk engine', 'fraud', false];
        yield 'contains passes for array' => [ContainsOperator::class, ['risk', 'audit'], 'audit', true];
        yield 'contains fails for array' => [ContainsOperator::class, ['risk', 'audit'], 'fraud', false];

        yield 'starts with passes' => [StartsWithOperator::class, 'ORD-1001', 'ORD-', true];
        yield 'starts with fails' => [StartsWithOperator::class, 'PAY-1001', 'ORD-', false];
        yield 'starts with rejects non string actual' => [StartsWithOperator::class, 1001, 'ORD-', false];

        yield 'ends with passes' => [EndsWithOperator::class, 'invoice.pdf', '.pdf', true];
        yield 'ends with fails' => [EndsWithOperator::class, 'invoice.png', '.pdf', false];
        yield 'ends with rejects non string actual' => [EndsWithOperator::class, 1001, '001', false];

        yield 'between passes' => [BetweenOperator::class, 60, [50, 80], true];
        yield 'between fails when above range' => [BetweenOperator::class, 90, [50, 80], false];
        yield 'between fails when below range' => [BetweenOperator::class, 40, [50, 80], false];
        yield 'between rejects invalid expected' => [BetweenOperator::class, 60, [50], false];
        yield 'between rejects non numeric actual' => [BetweenOperator::class, 'abc', [50, 80], false];
    }

    public function testOperatorRegistryAcceptsCustomOperators(): void
    {
        $registry = OperatorRegistry::defaults();
        $registry->register(new RegexOperator());

        self::assertTrue($registry->get('regex')->evaluate('ORD-1001', '/^ORD-[0-9]+$/'));
    }
}
