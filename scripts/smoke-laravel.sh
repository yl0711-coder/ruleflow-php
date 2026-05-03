#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
WORK_DIR="${1:-/tmp/ruleflow-laravel-smoke}"
PROJECT_DIR="${WORK_DIR}/app"
PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"

echo "RuleFlow Laravel smoke test"
echo "Root: ${ROOT_DIR}"
echo "Work dir: ${WORK_DIR}"
echo

rm -rf "${WORK_DIR}"
mkdir -p "${WORK_DIR}"

echo "1. Creating a clean Laravel project"
"${COMPOSER_BIN}" create-project laravel/laravel "${PROJECT_DIR}" --no-interaction --no-progress

cd "${PROJECT_DIR}"

echo "2. Installing RuleFlow from the local repository"
"${COMPOSER_BIN}" config repositories.ruleflow path "${ROOT_DIR}"
"${COMPOSER_BIN}" require yl0711-coder/ruleflow-php:"*@dev" --no-interaction --no-progress

echo "3. Publishing RuleFlow config"
"${PHP_BIN}" artisan vendor:publish --tag=ruleflow-config --force

echo "4. Replacing config with a minimal smoke-test rule"
cat > config/ruleflow.php <<'PHP'
<?php

return [
    'rules' => [
        [
            'name' => 'high_amount_order',
            'priority' => 100,
            'match' => 'all',
            'conditions' => [
                ['field' => 'order.amount', 'operator' => '>', 'value' => 1000],
            ],
            'action' => 'manual_review',
            'reason' => 'High amount order requires review.',
        ],
    ],

    'cache' => [
        'enabled' => false,
        'driver' => 'in_memory',
        'store' => null,
        'key' => 'ruleflow.rules',
        'ttl' => 300,
    ],
];
PHP

echo "5. Validating configured rules"
"${PHP_BIN}" artisan ruleflow:validate

echo "6. Running a container evaluation"
"${PHP_BIN}" artisan tinker --execute='
$result = app(\RuleFlow\RuleFlow::class)->evaluate(["order" => ["amount" => 1299]]);
if (!$result->matched() || $result->action() !== "manual_review") {
    throw new RuntimeException("RuleFlow smoke evaluation failed.");
}
echo json_encode($result->explain(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL;
'

echo
echo "Smoke test passed."

