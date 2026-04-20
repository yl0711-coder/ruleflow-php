<?php

declare(strict_types=1);

namespace RuleFlow\Loaders;

use JsonException;
use RuleFlow\Exceptions\InvalidRuleException;
use RuleFlow\RuleSet;

use function file_get_contents;
use function is_array;
use function json_decode;

use const JSON_THROW_ON_ERROR;

final class JsonRuleLoader implements RuleLoaderInterface
{
    public function __construct(private readonly string $path)
    {
    }

    public function load(): RuleSet
    {
        $contents = file_get_contents($this->path);

        if ($contents === false) {
            throw new InvalidRuleException("Unable to read rule file [{$this->path}].");
        }

        try {
            $rules = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidRuleException(
                "Invalid JSON rule file [{$this->path}]: {$exception->getMessage()}",
                previous: $exception
            );
        }

        if (!is_array($rules)) {
            throw new InvalidRuleException("Rule file [{$this->path}] must decode to an array.");
        }

        return RuleSet::fromArray($rules);
    }
}
