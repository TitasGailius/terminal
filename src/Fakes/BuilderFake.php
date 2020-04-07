<?php

namespace TitasGailius\Terminal\Fakes;

use PHPUnit\Framework\Assert;
use TitasGailius\Terminal\Builder;
use TitasGailius\Terminal\Terminal;
use TitasGailius\Terminal\OutputLine;
use Symfony\Component\Process\Process;

class BuilderFake extends Builder
{
    /**
     * Captured executed terminal commands.
     *
     * @var array
     */
    protected static $captured = [];

    /**
     * Fake commands.
     *
     * @var array
     */
    protected static $commands = [];

    /**
     * Capture a given command.
     *
     * @param  \TitasGailius\Terminal\Builder  $builder
     * @return void
     */
    public static function capture(Builder $builder)
    {
        static::$captured[] = $builder;
    }

    /**
     * Set fake commands.
     *
     * @param  array  $commands
     * @return void
     */
    public static function setCommands($commands)
    {
        static::$commands = [];

        foreach ($commands as $command => $response) {
            if (is_numeric($command)) {
                [$command, $response] = [$response, null];
            }

            static::$commands[$command] = static::parseResponse($command, $response);
        }
    }

    /**
     * Parse response for a given command.
     *
     * @param  string  $command
     * @param  mixed  $builder
     * @return \TitasGailius\Terminal\Fakes\ResponseFake
     */
    public static function parseResponse(string $command, $response)
    {
        if ($response instanceof ResponseFake) {
            return $response;
        }

        return Terminal::response($response, Process::fromShellCommandline($command));
    }

    /**
     * Determine if the terminal should run a given command.
     *
     * @param  string  $command
     * @return bool
     */
    public static function shouldRun(string $command)
    {
        return ! isset(static::$commands[$command]);
    }

    /**
     * Run a given process.
     *
     * @param  \Symfony\Component\Process\Process  $process
     * @return \TitasGailius\Terminal\Contracts\Response
     */
    protected function runProcess(Process $process)
    {
        Terminal::capture($this);

        $command = Terminal::toString($this->command);

        return static::$commands[$command] ?? Terminal::response();
    }

    /**
     * Get the executable command.
     *
     * @return string|array $command
     */
    protected function parseCommand($command)
    {
        if (is_array($command)) {
            return implode(' ', $command);
        }

        return $command;
    }

    /**
     * Assert if a given command was executed.
     *
     * @param  mixed  $command
     * @return void
     */
    public static function assertExecuted($command, int $times = 1)
    {
        $command = Terminal::toString($command);

        $count = count(array_filter(static::$captured, is_callable($command) ?: function ($captured) use ($command) {
            return Terminal::toString($captured->command) == $command;
        }));

        Assert::assertTrue($count === $times, sprintf(
            'The expected command [%s] was executed %s times instead of %s times.',
            $command, $count, $times
        ));
    }
}
