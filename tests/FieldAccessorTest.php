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
}
