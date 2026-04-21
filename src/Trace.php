<?php

declare(strict_types=1);

namespace RuleFlow;

final class Trace
{
    /**
     * @param list<array{
     *     rule:string,
     *     matched:bool,
     *     match?:string,
     *     skipped?:bool,
     *     checks:list<array<string,mixed>>
     * }> $entries
     */
    public function __construct(private readonly array $entries = [])
    {
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function entries(): array
    {
        return $this->entries;
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function toArray(): array
    {
        return $this->entries;
    }
}
