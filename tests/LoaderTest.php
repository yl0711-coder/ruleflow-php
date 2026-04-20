<?php

declare(strict_types=1);

namespace RuleFlow\Tests;

use PHPUnit\Framework\TestCase;
use RuleFlow\Loaders\ArrayRuleLoader;
use RuleFlow\Loaders\CachedRuleLoader;
use RuleFlow\Loaders\InMemoryRuleSetCache;

final class LoaderTest extends TestCase
{
    public function testArrayLoaderLoadsRuleSet(): void
    {
        $loader = new ArrayRuleLoader([
            [
                'name' => 'test_rule',
                'conditions' => [
                    ['field' => 'user.id', 'operator' => '>', 'value' => 0],
                ],
                'action' => 'allow',
            ],
        ]);

        self::assertCount(1, $loader->load()->rules());
    }

    public function testCachedLoaderUsesCache(): void
    {
        $loader = new CachedRuleLoader(
            new ArrayRuleLoader([
                [
                    'name' => 'test_rule',
                    'conditions' => [
                        ['field' => 'user.id', 'operator' => '>', 'value' => 0],
                    ],
                    'action' => 'allow',
                ],
            ]),
            new InMemoryRuleSetCache(),
            'test-rules'
        );

        self::assertSame($loader->load(), $loader->load());
    }
}
