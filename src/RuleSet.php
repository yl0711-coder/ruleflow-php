<?php

declare(strict_types=1);

namespace RuleFlow;

use RuleFlow\Exceptions\InvalidRuleException;

final class RuleSet
{
    /**
     * @param list<Rule> $rules
     */
    public function __construct(private readonly array $rules)
    {
    }

    /**
     * @param list<mixed> $definitions
     */
    public static function fromArray(array $definitions): self
    {
        $rules = [];

        foreach ($definitions as $index => $definition) {
            if (!is_array($definition)) {
                throw new InvalidRuleException("Rule definitions[{$index}] must be an array.");
            }

            $rules[] = Rule::fromArray($definition);
        }

        usort(
            $rules,
            static fn (Rule $left, Rule $right): int => $right->priority() <=> $left->priority()
        );

        return new self($rules);
    }

    /**
     * @return list<Rule>
     */
    public function rules(): array
    {
        return $this->rules;
    }
}
