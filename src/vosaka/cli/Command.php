<?php

declare(strict_types=1);

namespace vosaka\cli;

use Attribute;

/**
 * Attribute for marking CLI commands
 */
#[Attribute(Attribute::TARGET_CLASS)]
class Command
{
    public function __construct(
        public string $name,
        public string $version = '1.0.0',
        public ?string $author = null,
        public ?string $about = null,
        public ?string $long_about = null,
        public bool $disable_help = false,
        public bool $disable_version = false,
        public ?string $help_template = null,
    ) {}
}
