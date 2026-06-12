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

    public function testItTreatsNonPublicPropertiesAsMissingInsteadOfFailing(): void
    {
        $accessor = new FieldAccessor();
        $context = [
            'order' => new class {
                public function __construct(
                    private readonly float $amount = 200.0,
                    protected string $currency = 'USD'
                ) {
                }

                public function describe(): string
                {
                    return "{$this->amount} {$this->currency}";
                }
            },
        ];

        self::assertFalse($accessor->exists($context, 'order.amount'));
        self::assertFalse($accessor->exists($context, 'order.currency'));
        self::assertSame('missing', $accessor->get($context, 'order.amount', 'missing'));
    }

    public function testItReadsMagicGetObjects(): void
    {
        $accessor = new FieldAccessor();
        $context = [
            'order' => new class {
                /** @var array<string,mixed> */
                private array $attributes = ['amount' => 200, 'note' => null];

                public function __get(string $key): mixed
                {
                    return $this->attributes[$key] ?? null;
                }

                public function __isset(string $key): bool
                {
                    return isset($this->attributes[$key]);
                }
            },
        ];

        self::assertSame(200, $accessor->get($context, 'order.amount'));
        self::assertTrue($accessor->exists($context, 'order.amount'));

        // isset() semantics apply to magic accessors: null values are missing.
        self::assertFalse($accessor->exists($context, 'order.note'));
        self::assertFalse($accessor->exists($context, 'order.unknown'));
    }

    public function testItReadsArrayAccessObjects(): void
    {
        $accessor = new FieldAccessor();
        $context = [
            'order' => new \ArrayObject(['amount' => 200]),
        ];

        self::assertSame(200, $accessor->get($context, 'order.amount'));
        self::assertTrue($accessor->exists($context, 'order.amount'));
        self::assertFalse($accessor->exists($context, 'order.unknown'));
    }

    public function testItPrefersPublicPropertiesOverMagicAccessors(): void
    {
        $accessor = new FieldAccessor();
        $context = [
            'order' => new class {
                public int $amount = 100;

                public function __get(string $key): mixed
                {
                    return 999;
                }

                public function __isset(string $key): bool
                {
                    return true;
                }
            },
        ];

        self::assertSame(100, $accessor->get($context, 'order.amount'));
    }
}
