<?php

namespace TitasGailius\Terminal\Contracts;

use Symfony\Component\Process\Process;

interface Factory
{
    /**
     * Fake terminal.
     *
     * @return void
     */
    public static function fake(array $commands = []);

    /**
     * Reset the fake Terminal.
     *
     * @return void
     */
    public static function reset();

    /**
     * Create new output line(s)
     *
     * @param  mixed  $content
     * @param  string  $type
     * @return OutputLine|OutputLine[]
     */
    public static function line($content, string $type = Process::OUT);

    /**
     * Parse given lines.
     *
     * @param  mixed $lines
     * @return OutputLine[]
     */
    public static function lines($lines, string $type = Process::OUT);

    /**
     * Create a new error line.
     *
     * @param  string  $content
     * @return \TitasGailius\Terminal\OutputLine
     */
    public static function error(string $content);

    /**
     * Get an instance of the Process builder class.
     *
     * @return \TitasGailius\Terminal\Builder|\TitasGailius\Terminal\BuilderFake
     */
    public static function builder();
}
