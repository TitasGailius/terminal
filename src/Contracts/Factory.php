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
     * Create a new output line instance (or array of instances)
     *
     * @param  mixed  $content
     * @param  string  $type
     * @return \TitasGailius\Terminal\OutputLine|\TitasGailius\Terminal\OutputLine[]
     */
    public static function line($content, string $type = Process::OUT);
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