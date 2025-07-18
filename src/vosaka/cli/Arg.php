<?php

declare(strict_types=1);

namespace vosaka\cli;

use Attribute;

/**
 * Attribute for command arguments
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Arg
{
    public function __construct(
        public ?string $short = null,
        public ?string $long = null,
        public ?string $help = null,
        public ?string $value_name = null,
        public bool $required = false,
        public bool $multiple = false,
        public bool $takes_value = true,
        public ?string $default = null,
        public ?array $possible_values = null,
        public ?string $env = null,
        public bool $hide = false,
        public ?string $conflicts_with = null,
        public ?string $requires = null,
        public ?int $index = null, // For positional args
    ) {}
}
