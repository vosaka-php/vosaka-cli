<?php

declare(strict_types=1);

namespace vosaka\cli;

use Attribute;

/**
 * Attribute for subcommands
 */
#[Attribute(Attribute::TARGET_METHOD)]
class SubCommand
{
    public function __construct(
        public string $name,
        public ?string $about = null,
        public ?string $long_about = null,
        public ?array $aliases = null,
        public bool $hide = false,
    ) {}
}
