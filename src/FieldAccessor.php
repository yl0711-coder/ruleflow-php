<?php

declare(strict_types=1);

namespace RuleFlow;

use ArrayAccess;

final class FieldAccessor
{
    /**
     * Reads nested values with dot notation, for example user.risk_score.
     *
     * Each path segment is resolved against arrays, public object properties,
     * ArrayAccess offsets (which covers Eloquent models), and magic
     * __isset/__get pairs, in that order. Non-public properties are treated
     * as missing instead of being read.
     *
     * @param array<string,mixed>|object $context
     */
    public function get(array|object $context, string $path, mixed $default = null): mixed
    {
        $result = $this->resolve($context, $path);

        return $result['exists'] ? $result['value'] : $default;
    }

    /**
     * @param array<string,mixed>|object $context
     */
    public function exists(array|object $context, string $path): bool
    {
        return $this->resolve($context, $path)['exists'];
    }

    /**
     * @param array<string,mixed>|object $context
     * @return array{exists:bool,value:mixed}
     */
    private function resolve(array|object $context, string $path): array
    {
        $current = $context;

        foreach (explode('.', $path) as $segment) {
            $step = $this->step($current, $segment);

            if (!$step['exists']) {
                return [
                    'exists' => false,
                    'value' => null,
                ];
            }

            $current = $step['value'];
        }

        return [
            'exists' => true,
            'value' => $current,
        ];
    }

    /**
     * @return array{exists:bool,value:mixed}
     */
    private function step(mixed $current, string $segment): array
    {
        if (is_array($current)) {
            if (array_key_exists($segment, $current)) {
                return [
                    'exists' => true,
                    'value' => $current[$segment],
                ];
            }

            return [
                'exists' => false,
                'value' => null,
            ];
        }

        if (!is_object($current)) {
            return [
                'exists' => false,
                'value' => null,
            ];
        }

        // get_object_vars() from this external scope only exposes public
        // properties, so private and protected properties stay unreadable
        // instead of triggering property access errors.
        $publicProperties = get_object_vars($current);

        if (array_key_exists($segment, $publicProperties)) {
            return [
                'exists' => true,
                'value' => $publicProperties[$segment],
            ];
        }

        if ($current instanceof ArrayAccess && $current->offsetExists($segment)) {
            return [
                'exists' => true,
                'value' => $current->offsetGet($segment),
            ];
        }

        // isset() on inaccessible properties delegates to __isset(), which
        // means null values reported by magic accessors count as missing.
        if (method_exists($current, '__isset') && method_exists($current, '__get') && isset($current->{$segment})) {
            return [
                'exists' => true,
                'value' => $current->{$segment},
            ];
        }

        return [
            'exists' => false,
            'value' => null,
        ];
    }
}
