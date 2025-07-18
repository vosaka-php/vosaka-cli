<?php

declare(strict_types=1);

namespace vosaka\cli;

use Generator;
use venndev\vosaka\core\Future;
use venndev\vosaka\core\Result;

/**
 * Progress bar
 */
class ProgressBar
{
    private int $current = 0;
    private float $startTime;
    private int $width = 40;

    public function __construct(
        private Output $output,
        private int $total,
        private string $message = '',
    ) {
        $this->startTime = microtime(true);
    }

    public function start(): Result
    {
        $fn = function (): Generator {
            $this->current = 0;
            yield from $this->display()->unwrap();
        };
        return Future::new($fn());
    }

    public function advance(int $step = 1): Result
    {
        $fn = function () use ($step): Generator {
            $this->current = min($this->current + $step, $this->total);
            yield from $this->display()->unwrap();
        };
        return Future::new($fn());
    }

    public function finish(): Result
    {
        $fn = function (): Generator {
            $this->current = $this->total;
            yield from $this->display()->unwrap();
            yield from $this->output->writeln()->unwrap();
        };
        return Future::new($fn());
    }

    private function display(): Result
    {
        $fn = function (): Generator {
            $percent = $this->total > 0 ? $this->current / $this->total : 0;
            $filled = (int)($percent * $this->width);
            $empty = $this->width - $filled;

            $bar = str_repeat('█', $filled) . str_repeat('░', $empty);

            $elapsed = microtime(true) - $this->startTime;
            $eta = $percent > 0 ? ($elapsed / $percent) - $elapsed : 0;

            $line = sprintf(
                "\r%s [%s] %3d%% %s ETA: %s",
                $this->message,
                Style::green($bar),
                (int)($percent * 100),
                $this->formatTime($elapsed),
                $this->formatTime($eta)
            );

            echo $line;
            if ($this->current >= $this->total) {
                echo Style::success(' ✓');
            }

            yield;
        };

        return Future::new($fn());
    }

    private function formatTime(float $seconds): string
    {
        if ($seconds < 60) {
            return sprintf('%ds', (int)$seconds);
        }
        return sprintf('%dm%ds', (int)($seconds / 60), (int)$seconds % 60);
    }
}
