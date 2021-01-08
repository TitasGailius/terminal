<?php

namespace TitasGailius\Terminal;

use Symfony\Component\Process\Process;
use TitasGailius\Terminal\Contracts\Factory;

/**
 * @staticMixin
 */
class Terminal implements Factory
{
    /**
     * Indicate whether the terminal are captured.
     *
     * @var boolean
     */
    protected static $fake = false;

    /**
     * Use a fake Terminal.
     *
     * @return void
     */
    public static function fake(array $commands = [])
    {
        static::$fake = true;

        Fakes\BuilderFake::setCommands($commands);
    }

    /**
     * Reset the fake Terminal.
     *
     * @return void
     */
    public static function reset()
    {
        static::$fake = false;

        Fakes\BuilderFake::setCommands([]);
        Fakes\BuilderFake::setCaptured([]);
    }

    /**
     * Create a fake response.
     *
     * @param  mixed $lines
     * @return \TitasGailius\Terminal\Fakes\ResponseFake
     */
    public static function response($lines = null, $process = null)
    {
        if ($lines instanceof Process) {
            [$lines, $process] = [$process, $lines];
        }

        if (is_null($process)) {
            $process = new Process([]);
        }

        return new Fakes\ResponseFake($process, static::lines($lines));
    }

    /**
     * Parse given lines.
     *
     * @param  mixed $lines
     * @return OutputLine[]
     */
    public static function lines($lines, string $type = Process::OUT)
    {
        return array_map(function ($line) use ($type) {
            return static::line($line, $type);
        }, is_array($lines) ? $lines : [$lines]);
    }

    /**
     * Create new output line(s)
     *
     * @param  mixed  $content
     * @param  string  $type
     * @return OutputLine|OutputLine[]
     */
    public static function line($content, string $type = Process::OUT)
    {
        if ($content instanceof OutputLine) {
            return $content;
        }

        if (is_array($content)) {
            return static::lines($content);
        }

        return new OutputLine(
            (string) $type,
            (string) $content
        );
    }

    /**
     * Create a new error line.
     *
     * @param  string  $content
     * @return \TitasGailius\Terminal\OutputLine
     */
    public static function error(string $content)
    {
        return static::line($content, Process::ERR);
    }

    /**
     * Get an instance of the Process builder class.
     *
     * @return \TitasGailius\Terminal\Builder|\TitasGailius\Terminal\Fakes\BuilderFake
     */
    public static function builder()
    {
        if (static::$fake) {
            return new Fakes\BuilderFake;
        }

        return new Builder;
    }

    /**
     * Dynamically pass method calls to a new Builder instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \TitasGailius\Terminal\Builder|\TitasGailius\Terminal\Fakes\BuilderFake
     */
    public static function __callStatic(string $method, array $parameters)
    {
        return call_user_func([static::builder(), $method], ...$parameters);
    }
}
