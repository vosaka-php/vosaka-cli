<?php

declare(strict_types=1);

namespace vosaka\cli;

use Attribute;

/**
 * Value parser attribute
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
class Parser
{
    public function __construct(
        public string $parser, // 'int', 'float', 'bool', 'string', 'json', 'csv', custom callable
        public ?array $options = null,
    ) {}
}
