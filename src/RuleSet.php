<?php

declare(strict_types=1);

namespace RuleFlow;

final class RuleSet
{
    /**
     * @param list<Rule> $rules
     */
    public function __construct(private readonly array $rules)
    {
    }

    /**
     * @param list<array<string,mixed>> $definitions
     */
    public static function fromArray(array $definitions): self
    {
        $rules = array_map(
            static fn (array $definition): Rule => Rule::fromArray($definition),
            $definitions
        );

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
