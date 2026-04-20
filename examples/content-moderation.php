<?php

declare(strict_types=1);

use RuleFlow\Engine;
use RuleFlow\RuleSet;

require __DIR__ . '/../vendor/autoload.php';

$rules = [
    [
        'name' => 'spam_keyword',
        'match' => 'any',
        'priority' => 100,
        'conditions' => [
            ['field' => 'post.content', 'operator' => 'contains', 'value' => 'free money'],
            ['field' => 'post.report_count', 'operator' => '>=', 'value' => 3],
        ],
        'action' => 'manual_review',
        'reason' => 'Suspicious marketing keyword detected.',
    ],
    [
        'name' => 'new_user_external_link',
        'priority' => 80,
        'conditions' => [
            ['field' => 'user.days_since_signup', 'operator' => '<=', 'value' => 7],
            ['field' => 'post.links', 'operator' => 'contains', 'value' => 'https://example.com'],
        ],
        'action' => 'manual_review',
        'reason' => 'New user posted external links.',
    ],
];

$context = [
    'user' => [
        'id' => 1001,
        'days_since_signup' => 3,
    ],
    'post' => [
        'content' => 'Check this page for free money today.',
        'links' => ['https://example.com'],
        'report_count' => 0,
    ],
];

$result = Engine::make(RuleSet::fromArray($rules))->evaluate($context);

print_r($result->toArray());
