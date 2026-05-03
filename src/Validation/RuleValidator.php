<?php

declare(strict_types=1);

namespace RuleFlow\Validation;

use RuleFlow\Exceptions\InvalidRuleException;
use RuleFlow\Operators\ExistsOperator;
use RuleFlow\Operators\NotExistsOperator;
use RuleFlow\Operators\OperatorRegistry;
use RuleFlow\Rule;

final class RuleValidator
{
    public function __construct(private readonly OperatorRegistry $operators)
    {
    }

    public static function defaults(): self
    {
        return new self(OperatorRegistry::defaults());
    }

    /**
     * @param list<mixed> $definitions
     */
    public function validate(array $definitions): ValidationResult
    {
        $errors = [];
        $names = [];

        foreach ($definitions as $index => $definition) {
            $path = "rules[{$index}]";

            if (!is_array($definition)) {
                $errors[] = "{$path} must be an object.";
                continue;
            }

            $this->validateRequiredKeys($definition, $path, $errors);
            $this->validateRuleName($definition, $path, $names, $errors);
            $this->validateAction($definition, $path, $errors);
            $this->validateMatchMode($definition, $path, $errors);
            $this->validateConditions($definition, $path, $errors);
        }

        return new ValidationResult($errors);
    }

    /**
     * @param list<mixed> $definitions
     */
    public function assertValid(array $definitions): void
    {
        $result = $this->validate($definitions);

        if (!$result->valid()) {
            throw new InvalidRuleException(implode(PHP_EOL, $result->errors()));
        }
    }

    /**
     * @param array<string,mixed> $definition
     * @param list<string> $errors
     */
    private function validateRequiredKeys(array $definition, string $path, array &$errors): void
    {
        foreach (['name', 'conditions', 'action'] as $key) {
            if (!array_key_exists($key, $definition)) {
                $errors[] = "{$path}.{$key} is required.";
            }
        }
    }

    /**
     * @param array<string,mixed> $definition
     * @param array<string,bool> $names
     * @param list<string> $errors
     */
    private function validateRuleName(array $definition, string $path, array &$names, array &$errors): void
    {
        if (!array_key_exists('name', $definition)) {
            return;
        }

        if (!is_string($definition['name']) || trim($definition['name']) === '') {
            $errors[] = "{$path}.name must be a non-empty string.";
            return;
        }

        if (isset($names[$definition['name']])) {
            $errors[] = "{$path}.name [{$definition['name']}] is duplicated.";
            return;
        }

        $names[$definition['name']] = true;
    }

    /**
     * @param array<string,mixed> $definition
     * @param list<string> $errors
     */
    private function validateMatchMode(array $definition, string $path, array &$errors): void
    {
        if (!array_key_exists('match', $definition)) {
            return;
        }

        if (!in_array($definition['match'], [Rule::MATCH_ALL, Rule::MATCH_ANY], true)) {
            $errors[] = "{$path}.match must be either [all] or [any].";
        }
    }

    /**
     * @param array<string,mixed> $definition
     * @param list<string> $errors
     */
    private function validateAction(array $definition, string $path, array &$errors): void
    {
        if (!array_key_exists('action', $definition)) {
            return;
        }

        if (!is_string($definition['action']) || trim($definition['action']) === '') {
            $errors[] = "{$path}.action must be a non-empty string.";
        }
    }

    /**
     * @param array<string,mixed> $definition
     * @param list<string> $errors
     */
    private function validateConditions(array $definition, string $path, array &$errors): void
    {
        if (!array_key_exists('conditions', $definition)) {
            return;
        }

        if (!is_array($definition['conditions']) || $definition['conditions'] === []) {
            $errors[] = "{$path}.conditions must be a non-empty array.";
            return;
        }

        foreach ($definition['conditions'] as $conditionIndex => $condition) {
            $conditionPath = "{$path}.conditions[{$conditionIndex}]";

            if (!is_array($condition)) {
                $errors[] = "{$conditionPath} must be an object.";
                continue;
            }

            $this->validateConditionNode($condition, $conditionPath, $errors);
        }
    }

    /**
     * @param array<string,mixed> $condition
     * @param list<string> $errors
     */
    private function validateConditionNode(array $condition, string $path, array &$errors): void
    {
        if (array_key_exists('conditions', $condition)) {
            $this->validateConditionGroup($condition, $path, $errors);
            return;
        }

        $this->validateCondition($condition, $path, $errors);
    }

    /**
     * @param array<string,mixed> $group
     * @param list<string> $errors
     */
    private function validateConditionGroup(array $group, string $path, array &$errors): void
    {
        if (
            array_key_exists('match', $group)
            && !in_array($group['match'], [Rule::MATCH_ALL, Rule::MATCH_ANY], true)
        ) {
            $errors[] = "{$path}.match must be either [all] or [any].";
        }

        if (!is_array($group['conditions']) || $group['conditions'] === []) {
            $errors[] = "{$path}.conditions must be a non-empty array.";
            return;
        }

        foreach ($group['conditions'] as $index => $condition) {
            $conditionPath = "{$path}.conditions[{$index}]";

            if (!is_array($condition)) {
                $errors[] = "{$conditionPath} must be an object.";
                continue;
            }

            $this->validateConditionNode($condition, $conditionPath, $errors);
        }
    }

    /**
     * @param array<string,mixed> $condition
     * @param list<string> $errors
     */
    private function validateCondition(array $condition, string $path, array &$errors): void
    {
        foreach (['field', 'operator', 'value'] as $key) {
            if ($key === 'value' && $this->isOptionalValueOperator($condition)) {
                continue;
            }

            if (!array_key_exists($key, $condition)) {
                $errors[] = "{$path}.{$key} is required.";
            }
        }

        if (array_key_exists('field', $condition)) {
            if (!is_string($condition['field']) || trim($condition['field']) === '') {
                $errors[] = "{$path}.field must be a non-empty string.";
            }
        }

        if (!array_key_exists('operator', $condition)) {
            return;
        }

        if (array_key_exists('sensitive', $condition) && !is_bool($condition['sensitive'])) {
            $errors[] = "{$path}.sensitive must be a boolean.";
        }

        if (!is_string($condition['operator']) || trim($condition['operator']) === '') {
            $errors[] = "{$path}.operator must be a non-empty string.";
            return;
        }

        if (!in_array($condition['operator'], $this->operators->names(), true)) {
            $errors[] = "{$path}.operator [{$condition['operator']}] is not registered.";
        }
    }

    /**
     * @param array<string,mixed> $condition
     */
    private function isOptionalValueOperator(array $condition): bool
    {
        if (!array_key_exists('operator', $condition) || !is_string($condition['operator'])) {
            return false;
        }

        return in_array(
            $condition['operator'],
            [
                (new ExistsOperator())->name(),
                (new NotExistsOperator())->name(),
            ],
            true
        );
    }
}
