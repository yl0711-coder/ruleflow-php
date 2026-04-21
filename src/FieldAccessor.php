<?php

declare(strict_types=1);

namespace RuleFlow;

final class FieldAccessor
{
    /**
     * Reads nested values with dot notation, for example user.risk_score.
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
            if (is_array($current) && array_key_exists($segment, $current)) {
                $current = $current[$segment];
                continue;
            }

            if (is_object($current) && property_exists($current, $segment)) {
                $current = $current->{$segment};
                continue;
            }

            return [
                'exists' => false,
                'value' => null,
            ];
        }

        return [
            'exists' => true,
            'value' => $current,
        ];
    }
}
