<?php

declare(strict_types=1);

namespace vosaka\cli;

/**
 * Terminal color and style management
 */
class Style
{
    private static bool $colorEnabled = true;
    private static ?bool $isWindows = null;

    public const RESET = "\033[0m";

    // Foreground colors
    public const BLACK = "\033[30m";
    public const RED = "\033[31m";
    public const GREEN = "\033[32m";
    public const YELLOW = "\033[33m";
    public const BLUE = "\033[34m";
    public const MAGENTA = "\033[35m";
    public const CYAN = "\033[36m";
    public const WHITE = "\033[37m";
    public const GRAY = "\033[90m";
    public const BRIGHT_RED = "\033[91m";
    public const BRIGHT_GREEN = "\033[92m";
    public const BRIGHT_YELLOW = "\033[93m";
    public const BRIGHT_BLUE = "\033[94m";
    public const BRIGHT_MAGENTA = "\033[95m";
    public const BRIGHT_CYAN = "\033[96m";
    public const BRIGHT_WHITE = "\033[97m";

    // Styles
    public const BOLD = "\033[1m";
    public const DIM = "\033[2m";
    public const ITALIC = "\033[3m";
    public const UNDERLINE = "\033[4m";
    public const BLINK = "\033[5m";
    public const REVERSE = "\033[7m";
    public const HIDDEN = "\033[8m";
    public const STRIKETHROUGH = "\033[9m";

    public static function init(): void
    {
        self::$isWindows = DIRECTORY_SEPARATOR === '\\';
        self::detectColorSupport();
    }

    private static function detectColorSupport(): void
    {
        if (self::$isWindows) {
            self::$colorEnabled =
                getenv('ANSICON') !== false ||
                getenv('ConEmuANSI') === 'ON' ||
                getenv('TERM_PROGRAM') === 'Terminus' ||
                (function_exists('sapi_windows_vt100_support') && sapi_windows_vt100_support(STDOUT));
        } else {
            self::$colorEnabled =
                posix_isatty(STDOUT) &&
                (getenv('TERM') !== 'dumb' || getenv('COLORTERM') !== false);
        }
    }

    public static function apply(string $text, string ...$styles): string
    {
        if (!self::$colorEnabled || empty($styles)) {
            return $text;
        }

        return implode('', $styles) . $text . self::RESET;
    }

    public static function red(string $text): string
    {
        return self::apply($text, self::RED);
    }

    public static function green(string $text): string
    {
        return self::apply($text, self::GREEN);
    }

    public static function yellow(string $text): string
    {
        return self::apply($text, self::YELLOW);
    }

    public static function blue(string $text): string
    {
        return self::apply($text, self::BLUE);
    }

    public static function magenta(string $text): string
    {
        return self::apply($text, self::MAGENTA);
    }

    public static function cyan(string $text): string
    {
        return self::apply($text, self::CYAN);
    }

    public static function bold(string $text): string
    {
        return self::apply($text, self::BOLD);
    }

    public static function error(string $text): string
    {
        return self::apply($text, self::BRIGHT_RED, self::BOLD);
    }

    public static function success(string $text): string
    {
        return self::apply($text, self::BRIGHT_GREEN, self::BOLD);
    }

    public static function warning(string $text): string
    {
        return self::apply($text, self::BRIGHT_YELLOW, self::BOLD);
    }

    public static function info(string $text): string
    {
        return self::apply($text, self::BRIGHT_BLUE);
    }
}
