<?php

declare(strict_types=1);

namespace RuleFlow;

use RuleFlow\Exceptions\InvalidRuleException;

final class Condition
{
    public function __construct(
        private readonly string $field,
        private readonly string $operator,
        private readonly mixed $value
    ) {
        if ($field === '') {
            throw new InvalidRuleException('Condition field cannot be empty.');
        }

        if ($operator === '') {
            throw new InvalidRuleException('Condition operator cannot be empty.');
        }
    }

    /**
     * @param array{field:string,operator:string,value:mixed} $definition
     */
    public static function fromArray(array $definition): self
    {
        foreach (['field', 'operator', 'value'] as $key) {
            if (!array_key_exists($key, $definition)) {
                throw new InvalidRuleException("Condition is missing required key [{$key}].");
            }
        }

        if (!is_string($definition['field'])) {
            throw new InvalidRuleException('Condition field must be a string.');
        }

        if (!is_string($definition['operator'])) {
            throw new InvalidRuleException('Condition operator must be a string.');
        }

        return new self(
            (string) $definition['field'],
            (string) $definition['operator'],
            $definition['value']
        );
    }

    public function field(): string
    {
        return $this->field;
    }

    public function operator(): string
    {
        return $this->operator;
    }

    public function value(): mixed
    {
        return $this->value;
    }

    /**
     * @return array{field:string,operator:string,value:mixed}
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'operator' => $this->operator,
            'value' => $this->value,
        ];
    }
}
