<?php

require '../vendor/autoload.php';

use venndev\vosaka\time\Sleep;
use venndev\vosaka\VOsaka;
use vosaka\cli\Arg;
use vosaka\cli\ArgMatches;
use vosaka\cli\Cli;
use vosaka\cli\Command;
use vosaka\cli\Output;
use vosaka\cli\Parser;
use vosaka\cli\ProgressBar;
use vosaka\cli\Spinner;
use vosaka\cli\Style;
use vosaka\cli\SubCommand;

#[Command(
    name: "myapp",
    version: "1.0.0",
    author: "Your Name <you@example.com>",
    about: "An example CLI application"
)]
class ExampleCli extends Cli
{
    #[Arg(
        short: "v",
        long: "verbose",
        help: "Enable verbose output",
        takes_value: false,
    )]
    public bool $verbose = false;

    #[Arg(
        short: "c",
        long: "config",
        help: "Config file path",
        value_name: "FILE",
        env: "MYAPP_CONFIG",
    )]
    public ?string $config = null;

    #[Arg(
        short: "n",
        long: "count",
        help: "Number of iterations",
        default: "10",
    )]
    #[Parser(parser: "int")]
    public int $count = 10;

    #[Arg(
        index: 0,
        value_name: "INPUT",
        help: "Input file",
        required: true,
    )]
    public string $input;

    #[Arg(
        index: 1,
        value_name: "OUTPUT",
        help: "Output files",
        multiple: true,
    )]
    public array $outputs = [];

    public function run(ArgMatches $matches, Output $output): Generator
    {
        yield from $output->info("Starting application...")->unwrap();

        if ($sub = $matches->subcommand()) {
            [$name, $subMatches] = $sub;

            match ($name) {
                'serve' => yield from $this->serve($subMatches, $output),
                'build' => yield from $this->build($subMatches, $output),
                default => yield from $output->error("Unknown subcommand: $name")->unwrap(),
            };

            return;
        }

        // Main command logic
        $input = $matches->get('input');
        yield from $output->writeln("Processing input: $input")->unwrap();

        $progress = new ProgressBar($output, $this->count, "Processing");
        yield from $progress->start()->unwrap();

        for ($i = 0; $i < $this->count; $i++) {
            yield from $progress->advance()->unwrap();
            yield Sleep::us(100000);
        }

        yield from $progress->finish()->unwrap();
        yield from $output->success("Done!")->unwrap();
    }

    #[SubCommand(
        name: "serve",
        about: "Start the development server",
        aliases: ["s", "server"],
    )]
    public function serve($matches, Output $output): Generator
    {
        $spinner = new Spinner($output, "Starting server...");

        $spinGen = $spinner->spin()->unwrap();
        for ($i = 0; $i < 30; $i++) {
            $spinGen->current();
            $spinGen->next();
            yield Sleep::ms(100);
        }

        yield from $spinner->stop(Style::success("✓ Server started on http://localhost:8080"))->unwrap();
    }

    #[SubCommand(
        name: "build",
        about: "Build the project",
    )]
    public function build($matches, Output $output): Generator
    {
        yield from $output->section("Building project...")->unwrap();

        $steps = [
            "Cleaning output directory" => 500000,
            "Compiling sources" => 1000000,
            "Optimizing assets" => 800000,
            "Generating documentation" => 600000,
            "Creating archives" => 400000,
        ];

        foreach ($steps as $step => $duration) {
            yield from $output->write("  $step... ")->unwrap();
            yield Sleep::us($duration);
            yield from $output->writeln(Style::green("✓"))->unwrap();
        }

        yield from $output->success("Build completed successfully!")->unwrap();
    }
}

VOsaka::spawn((new ExampleCli())->wait()->unwrap());
VOsaka::run();
