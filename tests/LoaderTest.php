<?php

declare(strict_types=1);

namespace RuleFlow\Tests;

use PHPUnit\Framework\TestCase;
use RuleFlow\Exceptions\InvalidRuleException;
use RuleFlow\Loaders\ArrayRuleLoader;
use RuleFlow\Loaders\CachedRuleLoader;
use RuleFlow\Loaders\InMemoryRuleSetCache;
use RuleFlow\Loaders\JsonRuleLoader;

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

    public function testJsonLoaderRejectsInvalidJson(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'ruleflow-invalid-json-');
        self::assertIsString($path);
        file_put_contents($path, '{invalid json');

        try {
            $this->expectException(InvalidRuleException::class);
            $this->expectExceptionMessage('Invalid JSON rule file');

            (new JsonRuleLoader($path))->load();
        } finally {
            unlink($path);
        }
    }

    public function testArrayLoaderRejectsMalformedRuleDefinitions(): void
    {
        $this->expectException(InvalidRuleException::class);
        $this->expectExceptionMessage('Rule definitions[0] must be an array.');

        (new ArrayRuleLoader(['not-a-rule']))->load();
    }

    public function testArrayLoaderRejectsMalformedConditions(): void
    {
        $this->expectException(InvalidRuleException::class);
        $this->expectExceptionMessage('Rule [test_rule] condition [0] must be an array.');

        (new ArrayRuleLoader([
            [
                'name' => 'test_rule',
                'conditions' => ['not-a-condition'],
                'action' => 'allow',
            ],
        ]))->load();
    }

    public function testArrayLoaderRejectsNonStringRuleName(): void
    {
        $this->expectException(InvalidRuleException::class);
        $this->expectExceptionMessage('Rule name must be a string.');

        (new ArrayRuleLoader([
            [
                'name' => ['invalid'],
                'conditions' => [
                    ['field' => 'user.id', 'operator' => '>', 'value' => 0],
                ],
                'action' => 'allow',
            ],
        ]))->load();
    }

    public function testArrayLoaderRejectsNonStringConditionField(): void
    {
        $this->expectException(InvalidRuleException::class);
        $this->expectExceptionMessage('Condition field must be a string.');

        (new ArrayRuleLoader([
            [
                'name' => 'test_rule',
                'conditions' => [
                    ['field' => ['invalid'], 'operator' => '>', 'value' => 0],
                ],
                'action' => 'allow',
            ],
        ]))->load();
    }
}
