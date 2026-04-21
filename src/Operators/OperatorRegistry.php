<?php

declare(strict_types=1);

namespace RuleFlow\Operators;

use RuleFlow\Exceptions\UnsupportedOperatorException;

final class OperatorRegistry
{
    /** @var array<string,OperatorInterface> */
    private array $operators = [];

    /**
     * @param list<OperatorInterface> $operators
     */
    public function __construct(array $operators = [])
    {
        foreach ($operators as $operator) {
            $this->register($operator);
        }
    }

    public static function defaults(): self
    {
        return new self([
            new EqualsOperator(),
            new NotEqualsOperator(),
            new GreaterThanOperator(),
            new GreaterThanOrEqualsOperator(),
            new LessThanOperator(),
            new LessThanOrEqualsOperator(),
            new InOperator(),
            new NotInOperator(),
            new ExistsOperator(),
            new NotExistsOperator(),
            new ContainsOperator(),
            new StartsWithOperator(),
            new EndsWithOperator(),
            new BetweenOperator(),
            new RegexOperator(),
        ]);
    }

    public function register(OperatorInterface $operator): void
    {
        $this->operators[$operator->name()] = $operator;
    }

    public function get(string $name): OperatorInterface
    {
        if (!isset($this->operators[$name])) {
            throw new UnsupportedOperatorException("Unsupported operator [{$name}].");
        }

        return $this->operators[$name];
    }

    /**
     * @return list<string>
     */
    public function names(): array
    {
        return array_keys($this->operators);
    }
}
