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
     *     checks:list<array{
     *         field:string,
     *         actual:mixed,
     *         operator:string,
     *         expected:mixed,
     *         passed:bool
     *     }>
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
