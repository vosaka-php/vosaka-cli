<?php

declare(strict_types=1);

namespace vosaka\cli;

use Generator;
use venndev\vosaka\core\Future;
use venndev\vosaka\core\Result;

abstract class Cli
{
    /**
     * Run the CLI application with the provided matches and output.
     *
     * @param ArgMatches $matches
     * @param Output $output
     * @return Generator
     */
    abstract function run(ArgMatches $matches, Output $output): Generator;

    public function wait(): Result
    {
        $fn = function (): Generator {
            $cliApp = App::new()->fromClass($this::class);
            $matches = yield from $cliApp->parse()->unwrap();
            yield from $this->run($matches, new Output());
        };
        return Future::new($fn());
    }
}
