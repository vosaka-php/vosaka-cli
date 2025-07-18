<?php

declare(strict_types=1);

namespace vosaka\cli;

/**
 * Argument matches after parsing
 */
class ArgMatches
{
    public function __construct(
        private array $values = [],
        private array $occurrences = [],
        private ?array $subcommand = null,
    ) {}

    public function get(string $name): mixed
    {
        return $this->values[$name] ?? null;
    }

    public function getMany(string $name): array
    {
        return (array)($this->values[$name] ?? []);
    }

    public function contains(string $name): bool
    {
        return isset($this->values[$name]);
    }

    public function occurrences(string $name): int
    {
        return $this->occurrences[$name] ?? 0;
    }

    public function subcommand(): ?array
    {
        return $this->subcommand;
    }

    public function values(): array
    {
        return $this->values;
    }
}
