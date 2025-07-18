<?php

declare(strict_types=1);

namespace vosaka\cli;

use Generator;
use venndev\vosaka\core\Future;
use venndev\vosaka\core\Result;
use venndev\vosaka\time\Sleep;

/**
 * Spinner animation
 */
class Spinner
{
    private array $frames = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
    private int $current = 0;

    public function __construct(
        private Output $output,
        private string $message = 'Loading...',
    ) {}

    public function spin(): Result
    {
        $fn = function (): Generator {
            echo "\033[?25l"; // Hide cursor

            while (true) {
                echo "\r" . Style::cyan($this->frames[$this->current]) . " $this->message";
                $this->current = ($this->current + 1) % count($this->frames);

                yield Sleep::us(100000);
            }
        };
        return Future::new($fn());
    }

    public function stop(string $message = ''): Result
    {
        $fn = function () use ($message): Generator {
            echo "\r\033[K"; // Clear line
            echo "\033[?25h"; // Show cursor

            if ($message) {
                yield from $this->output->writeln($message)->unwrap();
            }
        };
        return Future::new($fn());
    }
}
