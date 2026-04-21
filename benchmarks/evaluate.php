<?php

declare(strict_types=1);

use RuleFlow\Engine;
use RuleFlow\RuleSet;

require __DIR__ . '/../vendor/autoload.php';

$ruleCount = (int) ($argv[1] ?? 100);
$iterations = (int) ($argv[2] ?? 10000);

$rules = [];

for ($i = 1; $i <= $ruleCount; $i++) {
    $rules[] = [
        'name' => "amount_rule_{$i}",
        'priority' => $ruleCount - $i,
        'conditions' => [
            ['field' => 'order.amount', 'operator' => '>', 'value' => $i * 10],
            ['field' => 'user.email', 'operator' => 'exists'],
        ],
        'action' => 'manual_review',
    ];
}

$context = [
    'order' => [
        'amount' => $ruleCount * 10 + 1,
    ],
    'user' => [
        'email' => 'user@example.com',
    ],
];

$engine = Engine::make(RuleSet::fromArray($rules));

$firstMatch = benchmark(static function () use ($engine, $context, $iterations): void {
    for ($i = 0; $i < $iterations; $i++) {
        $engine->evaluate($context);
    }
});

$allMatches = benchmark(static function () use ($engine, $context, $iterations): void {
    for ($i = 0; $i < $iterations; $i++) {
        $engine->evaluateAll($context);
    }
});

printf("RuleFlow benchmark\n");
printf("==================\n");
printf("Rules:      %d\n", $ruleCount);
printf("Iterations: %d\n", $iterations);
printf("PHP:        %s\n", PHP_VERSION);
printf("\n");

printResult('evaluate', $firstMatch, $iterations);
printResult('evaluateAll', $allMatches, $iterations);

printf("Peak memory: %.2f MB\n", memory_get_peak_usage(true) / 1024 / 1024);

/**
 * @return array{seconds:float}
 */
function benchmark(callable $callback): array
{
    $start = microtime(true);
    $callback();

    return [
        'seconds' => microtime(true) - $start,
    ];
}

/**
 * @param array{seconds:float} $result
 */
function printResult(string $name, array $result, int $iterations): void
{
    $perSecond = $iterations / $result['seconds'];

    printf(
        "%-12s %.4f sec | %.0f eval/sec\n",
        $name . ':',
        $result['seconds'],
        $perSecond
    );
}
