<?php

namespace TitasGailius\Terminal\Fakes;

use TitasGailius\Terminal\Response;
use TitasGailius\Terminal\OutputLine;
use Symfony\Component\Process\Process;

class ResponseFake extends Response
{
    /**
     * Response lines.
     *
     * @var array
     */
    protected $lines;

    /**
     * Exit code.
     *
     * @var int
     */
    protected $exitCode;

    /**
     * Instantiate a new process instance.
     *
     * @param  \Symfony\Component\Process\Process  $process
     * @param  array  $lines
     * @param  int  $exitCode
     */
    public function __construct(Process $process, array $lines = [], int $exitCode = 0)
    {
        parent::__construct($process);

        $this->lines = $lines;
        $this->exitCode = $exitCode;
    }

    /**
     * Check if the process ended successfully.
     *
     * @return bool
     */
    public function successful()
    {
        return $this->exitCode === 0;
    }

    /**
     * Get the process output.
     *
     * @return string
     */
    public function output()
    {
        return implode(PHP_EOL, array_map(function ($line) {
            return (string) $line;
        }, $this->lines));
    }

    /**
     * Get output iterator.
     *
     * @return \Generator
     */
    public function getIterator()
    {
        foreach ($this->lines as $line) {
            yield $line;
        }
    }

    /**
     * Indicate the the current response has failed.
     *
     * @return $this
     */
    public function shouldFail()
    {
        $this->exitCode = 1;

        return $this;
    }
}
