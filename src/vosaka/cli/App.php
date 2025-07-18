<?php

declare(strict_types=1);

namespace vosaka\cli;

use Generator;
use ReflectionClass;
use venndev\vosaka\core\Future;
use venndev\vosaka\core\Result;

/**
 * CLI Application builder
 */
class App
{
    private array $args = [];
    private array $subcommands = [];
    private ?Command $command = null;
    private array $positionalArgs = [];

    public function __construct(
        private string $name = 'app',
    ) {
        Style::init();
    }

    public static function new(string $name = 'app'): App
    {
        return new self($name);
    }

    public function version(string $version): App
    {
        if ($this->command) {
            $this->command->version = $version;
        }
        return $this;
    }

    public function author(string $author): App
    {
        if ($this->command) {
            $this->command->author = $author;
        }
        return $this;
    }

    public function about(string $about): App
    {
        if ($this->command) {
            $this->command->about = $about;
        }
        return $this;
    }

    public function arg(Arg $arg): App
    {
        $key = $arg->long ?? $arg->short ?? 'arg_' . count($this->args);
        $this->args[$key] = $arg;

        if ($arg->index !== null) {
            $this->positionalArgs[$arg->index] = $arg;
        }

        return $this;
    }

    public function subcommand(string $name, callable $configure): App
    {
        $subApp = new self($name);
        $configure($subApp);
        $this->subcommands[$name] = $subApp;
        return $this;
    }

    public function fromClass(string $className): App
    {
        $reflection = new ReflectionClass($className);

        // Get Command attribute
        $attrs = $reflection->getAttributes(Command::class);
        if (!empty($attrs)) {
            $this->command = $attrs[0]->newInstance();
            $this->name = $this->command->name;
        }

        // Get Arg properties
        foreach ($reflection->getProperties() as $property) {
            $argAttrs = $property->getAttributes(Arg::class);
            if (!empty($argAttrs)) {
                $arg = $argAttrs[0]->newInstance();
                if (!$arg->long && !$arg->short) {
                    $arg->long = $this->camelToKebab($property->getName());
                }
                $this->arg($arg);
            }
        }

        // Get SubCommand methods
        foreach ($reflection->getMethods() as $method) {
            $subAttrs = $method->getAttributes(SubCommand::class);
            if (!empty($subAttrs)) {
                $sub = $subAttrs[0]->newInstance();
                $this->subcommands[$sub->name] = [
                    'method' => $method->getName(),
                    'class' => $className,
                    'meta' => $sub,
                ];
            }
        }

        return $this;
    }

    public function parse(): Result
    {
        $fn = function (): Generator {
            global $argv;
            $args = array_slice($argv, 1);

            return yield from $this->parseArgs($args)->unwrap();
        };
        return Future::new($fn());
    }

    public function parseFrom(array $args): Result
    {
        $fn = function () use ($args): Generator {
            return yield from $this->parseArgs($args)->unwrap();
        };
        return Future::new($fn());
    }

    private function parseArgs(array $args): Result
    {
        $fn = function () use ($args): Generator {
            $values = [];
            $occurrences = [];
            $positionalIndex = 0;

            $i = 0;
            while ($i < count($args)) {
                $arg = $args[$i];

                // Check for subcommand
                if (!str_starts_with($arg, '-') && isset($this->subcommands[$arg])) {
                    $subArgs = array_slice($args, $i + 1);

                    if ($this->subcommands[$arg] instanceof self) {
                        $subMatches = yield from $this->subcommands[$arg]->parseFrom($subArgs);
                        return new ArgMatches($values, $occurrences, [$arg, $subMatches]);
                    } else {
                        // Handle method-based subcommand
                        return new ArgMatches($values, $occurrences, [$arg, $subArgs]);
                    }
                }

                // Long option --name=value or --name value
                if (str_starts_with($arg, '--')) {
                    $parts = explode('=', substr($arg, 2), 2);
                    $name = $parts[0];
                    $value = $parts[1] ?? null;

                    $argDef = $this->findArg($name);
                    if (!$argDef) {
                        yield from $this->error("Unknown option: --$name");
                        return;
                    }

                    if ($argDef->takes_value && $value === null) {
                        $i++;
                        if ($i >= count($args)) {
                            yield from $this->error("Option --$name requires a value");
                            return;
                        }
                        $value = $args[$i];
                    }

                    $values[$name] = $argDef->multiple
                        ? [...($values[$name] ?? []), $value]
                        : $value;

                    $occurrences[$name] = ($occurrences[$name] ?? 0) + 1;
                }
                // Short option -n value or -abc
                elseif (str_starts_with($arg, '-') && strlen($arg) > 1) {
                    $chars = str_split(substr($arg, 1));

                    foreach ($chars as $j => $char) {
                        $argDef = $this->findArgByShort($char);
                        if (!$argDef) {
                            yield from $this->error("Unknown option: -$char");
                            return;
                        }

                        $name = $argDef->long ?? $char;

                        if ($argDef->takes_value) {
                            if ($j < count($chars) - 1) {
                                // Rest of the string is the value
                                $value = substr($arg, $j + 2);
                            } else {
                                // Next argument is the value
                                $i++;
                                if ($i >= count($args)) {
                                    yield from $this->error("Option -$char requires a value");
                                    return;
                                }
                                $value = $args[$i];
                            }

                            $values[$name] = $argDef->multiple
                                ? [...($values[$name] ?? []), $value]
                                : $value;

                            break;
                        } else {
                            $values[$name] = true;
                        }

                        $occurrences[$name] = ($occurrences[$name] ?? 0) + 1;
                    }
                }
                // Positional argument
                else {
                    if (isset($this->positionalArgs[$positionalIndex])) {
                        $argDef = $this->positionalArgs[$positionalIndex];
                        $name = $argDef->long ?? $argDef->value_name ?? "arg$positionalIndex";

                        if ($argDef->multiple) {
                            $values[$name] = [...($values[$name] ?? []), $arg];
                        } else {
                            $values[$name] = $arg;
                            $positionalIndex++;
                        }
                    } else {
                        yield from $this->error("Unexpected argument: $arg");
                        return;
                    }
                }

                $i++;
            }

            // Check required args
            foreach ($this->args as $name => $argDef) {
                if ($argDef->required && !isset($values[$name])) {
                    // Check env fallback
                    if ($argDef->env && getenv($argDef->env) !== false) {
                        $values[$name] = getenv($argDef->env);
                    } elseif ($argDef->default !== null) {
                        $values[$name] = $argDef->default;
                    } else {
                        yield from $this->error("Required argument missing: $name");
                        return;
                    }
                }
            }

            return new ArgMatches($values, $occurrences);
        };

        return Future::new($fn());
    }

    private function findArg(string $long): ?Arg
    {
        foreach ($this->args as $arg) {
            if ($arg->long === $long) {
                return $arg;
            }
        }
        return null;
    }

    private function findArgByShort(string $short): ?Arg
    {
        foreach ($this->args as $arg) {
            if ($arg->short === $short) {
                return $arg;
            }
        }
        return null;
    }

    private function error(string $message): Generator
    {
        $output = new Output();
        yield from $output->error($message);
        yield from $this->help()->unwrap();
        exit(1);
    }

    public function help(): Result
    {
        $fn = function (): Generator {
            $output = new Output();

            // Usage
            yield from $output->writeln(Style::bold("USAGE:"))->unwrap();
            $usage = "    $this->name";

            if (!empty($this->args)) {
                $usage .= " [OPTIONS]";
            }

            if (!empty($this->positionalArgs)) {
                foreach ($this->positionalArgs as $arg) {
                    $name = $arg->value_name ?? 'ARG';
                    $usage .= $arg->required ? " <$name>" : " [$name]";
                }
            }

            if (!empty($this->subcommands)) {
                $usage .= " <SUBCOMMAND>";
            }

            yield from $output->writeln($usage)->unwrap();
            yield from $output->writeln()->unwrap();

            // Description
            if ($this->command?->about) {
                yield from $output->writeln($this->command->about)->unwrap();
                yield from $output->writeln()->unwrap();
            }

            // Options
            if (!empty($this->args)) {
                yield from $output->writeln(Style::bold("OPTIONS:"))->unwrap();

                foreach ($this->args as $arg) {
                    if ($arg->hide || $arg->index !== null) continue;

                    $option = "    ";
                    if ($arg->short) {
                        $option .= "-$arg->short";
                        if ($arg->long) {
                            $option .= ", ";
                        }
                    }
                    if ($arg->long) {
                        $option .= "--$arg->long";
                    }

                    if ($arg->takes_value) {
                        $valueName = $arg->value_name ?? 'VALUE';
                        $option .= " <$valueName>";
                    }

                    $option = str_pad($option, 30);
                    $option .= $arg->help ?? '';

                    if ($arg->default !== null) {
                        $option .= Style::apply(" [default: $arg->default]", Style::DIM);
                    }

                    yield from $output->writeln($option)->unwrap();
                }

                yield from $output->writeln()->unwrap();
            }

            // Subcommands
            if (!empty($this->subcommands)) {
                yield from $output->writeln(Style::bold("SUBCOMMANDS:"))->unwrap();

                foreach ($this->subcommands as $name => $sub) {
                    $about = '';
                    if ($sub instanceof self && $sub->command?->about) {
                        $about = $sub->command->about;
                    } elseif (is_array($sub) && $sub['meta']->about) {
                        $about = $sub['meta']->about;
                    }

                    $line = "    " . str_pad($name, 20) . $about;
                    yield from $output->writeln($line)->unwrap();
                }

                yield from $output->writeln()->unwrap();
            }
        };

        return Future::new($fn());
    }

    private function camelToKebab(string $str): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $str));
    }
}
