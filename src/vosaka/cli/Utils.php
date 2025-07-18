<?php

declare(strict_types=1);

namespace vosaka\cli;

use Generator;

final class Utils
{
    public static function emptyGenerator(): Generator
    {
        yield null;
    }
}
