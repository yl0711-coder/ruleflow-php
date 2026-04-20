<?php

declare(strict_types=1);

namespace RuleFlow\Validation;

final class ValidationResult
{
    /**
     * @param list<string> $errors
     */
    public function __construct(private readonly array $errors = [])
    {
    }

    public function valid(): bool
    {
        return $this->errors === [];
    }

    /**
     * @return list<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
