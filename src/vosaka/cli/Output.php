<?php

declare(strict_types=1);

namespace vosaka\cli;

use Generator;
use venndev\vosaka\core\Future;
use venndev\vosaka\core\Result;

/**
 * Output handler with Generator support
 */
class Output
{
    public function __construct(
        private bool $quiet = false,
        private bool $verbose = false,
        private bool $debug = false,
    ) {}

    public function write(string $message): Result
    {
        $fn = function () use ($message) {
            if (!$this->quiet) {
                echo $message;
            }
            yield $message;
        };
        return Future::new($fn());
    }

    public function writeln(string $message = ''): Result
    {
        return $this->write($message . PHP_EOL);
    }

    public function error(string $message): Result
    {
        return $this->writeln(Style::error("error: ") . $message);
    }

    public function success(string $message): Result
    {
        return $this->writeln(Style::success("✓ ") . $message);
    }

    public function warning(string $message): Result
    {
        return $this->writeln(Style::warning("warning: ") . $message);
    }

    public function info(string $message): Result
    {
        if ($this->verbose) {
            return $this->writeln(Style::info("info: ") . $message);
        }
        return Future::new(Utils::emptyGenerator());
    }

    public function debug(string $message): Result
    {
        if ($this->debug) {
            return $this->writeln(Style::apply("[DEBUG] ", Style::MAGENTA) . $message);
        }
        return Future::new(Utils::emptyGenerator());
    }

    public function section(string $title): Result
    {
        return $this->writeln(Style::bold(Style::apply($title, Style::CYAN)));
    }

    public function table(array $headers, array $rows): Result
    {
        $fn = function () use ($headers, $rows): Generator {
            $widths = [];

            // Calculate column widths
            foreach ($headers as $i => $header) {
                $widths[$i] = mb_strlen($header);
            }

            foreach ($rows as $row) {
                foreach ($row as $i => $cell) {
                    $widths[$i] = max($widths[$i] ?? 0, mb_strlen((string)$cell));
                }
            }

            // Print headers
            $headerRow = '│';
            foreach ($headers as $i => $header) {
                $headerRow .= ' ' . str_pad($header, $widths[$i]) . ' │';
            }

            $separator = '├' . implode('┼', array_map(fn($w) => str_repeat('─', $w + 2), $widths)) . '┤';
            $top = '┌' . implode('┬', array_map(fn($w) => str_repeat('─', $w + 2), $widths)) . '┐';
            $bottom = '└' . implode('┴', array_map(fn($w) => str_repeat('─', $w + 2), $widths)) . '┘';

            yield from $this->writeln($top)->unwrap();
            yield from $this->writeln(Style::bold($headerRow))->unwrap();
            yield from $this->writeln($separator)->unwrap();

            // Print rows
            foreach ($rows as $row) {
                $rowLine = '│';
                foreach ($row as $i => $cell) {
                    $rowLine .= ' ' . str_pad((string)$cell, $widths[$i]) . ' │';
                }
                yield from $this->writeln($rowLine)->unwrap();
            }

            yield from $this->writeln($bottom)->unwrap();
        };

        return Future::new($fn());
    }
}
