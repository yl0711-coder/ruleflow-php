<?php

declare(strict_types=1);

namespace RuleFlow\Tests;

use PHPUnit\Framework\TestCase;
use RuleFlow\Operators\BetweenOperator;
use RuleFlow\Operators\ContainsOperator;
use RuleFlow\Operators\InOperator;
use RuleFlow\Operators\OperatorRegistry;
use RuleFlow\Operators\StartsWithOperator;
use RuleFlow\Tests\Fixtures\RegexOperator;

final class OperatorTest extends TestCase
{
    public function testInOperator(): void
    {
        self::assertTrue((new InOperator())->evaluate('vip', ['vip', 'pro']));
        self::assertFalse((new InOperator())->evaluate('basic', ['vip', 'pro']));
    }

    public function testContainsOperatorSupportsStringsAndArrays(): void
    {
        self::assertTrue((new ContainsOperator())->evaluate('hello risk engine', 'risk'));
        self::assertTrue((new ContainsOperator())->evaluate(['risk', 'audit'], 'audit'));
    }

    public function testBetweenOperator(): void
    {
        self::assertTrue((new BetweenOperator())->evaluate(60, [50, 80]));
        self::assertFalse((new BetweenOperator())->evaluate(90, [50, 80]));
    }

    public function testStartsWithOperator(): void
    {
        self::assertTrue((new StartsWithOperator())->evaluate('ORD-1001', 'ORD-'));
    }

    public function testOperatorRegistryAcceptsCustomOperators(): void
    {
        $registry = OperatorRegistry::defaults();
        $registry->register(new RegexOperator());

        self::assertTrue($registry->get('regex')->evaluate('ORD-1001', '/^ORD-[0-9]+$/'));
    }
}
