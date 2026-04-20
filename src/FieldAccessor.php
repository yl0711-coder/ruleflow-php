<?php

declare(strict_types=1);

namespace RuleFlow;

final class FieldAccessor
{
    /**
     * Reads nested values with dot notation, for example user.risk_score.
     *
     * @param array<string,mixed> $context
     */
    public function get(array $context, string $path, mixed $default = null): mixed
    {
        $current = $context;

        foreach (explode('.', $path) as $segment) {
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
                continue;
            }

            if (is_object($current) && isset($current->{$segment})) {
                $current = $current->{$segment};
                continue;
            }

            return $default;
        }

        return $current;
    }
}
