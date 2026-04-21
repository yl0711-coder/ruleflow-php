<?php

declare(strict_types=1);

namespace RuleFlow\Laravel\Commands;

use Illuminate\Console\Command;
use RuleFlow\Validation\RuleValidator;

final class ValidateRulesCommand extends Command
{
    protected $signature = 'ruleflow:validate';

    protected $description = 'Validate RuleFlow rule definitions from config(ruleflow.rules).';

    public function handle(): int
    {
        $rules = (array) config('ruleflow.rules', []);
        $result = RuleValidator::defaults()->validate($rules);

        if ($result->valid()) {
            $this->info(sprintf('RuleFlow validation passed. %d rule(s) checked.', count($rules)));

            return self::SUCCESS;
        }

        $this->error('RuleFlow validation failed.');

        foreach ($result->errors() as $error) {
            $this->line("- {$error}");
        }

        return self::FAILURE;
    }
}
