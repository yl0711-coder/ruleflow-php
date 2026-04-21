<?php

declare(strict_types=1);

namespace RuleFlow\Tests;

use PHPUnit\Framework\TestCase;
use RuleFlow\FieldAccessor;

final class FieldAccessorTest extends TestCase
{
    public function testItReadsNestedArrayValuesWithDotNotation(): void
    {
        $accessor = new FieldAccessor();

        self::assertSame(45, $accessor->get([
            'user' => ['risk_score' => 45],
        ], 'user.risk_score'));
    }

    public function testItReturnsDefaultWhenPathDoesNotExist(): void
    {
        $accessor = new FieldAccessor();

        self::assertSame('missing', $accessor->get([], 'user.risk_score', 'missing'));
    }

    public function testItCanReportFieldExistence(): void
    {
        $accessor = new FieldAccessor();

        self::assertTrue($accessor->exists([
            'user' => ['risk_score' => null],
        ], 'user.risk_score'));

        self::assertFalse($accessor->exists([], 'user.risk_score'));
    }

    public function testItReadsTopLevelObjects(): void
    {
        $accessor = new FieldAccessor();
        $context = (object) [
            'user' => (object) [
                'risk_score' => 45,
            ],
        ];

        self::assertSame(45, $accessor->get($context, 'user.risk_score'));
        self::assertTrue($accessor->exists($context, 'user.risk_score'));
    }
}
