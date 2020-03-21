<?php

namespace TitasGailius\Terminal\Fakes;

class ResponseFakeBuilder
{
    /**
     * Response process.
     *
     * @var \Symfony\Component\Process\Process
     */
    protected $process;

    /**
     * Response lines.
     *
     * @var array
     */
    protected $lines;

    /**
     * Response exit code.
     *
     * @var int
     */
    protected $exitCode = 0;

    /**
     * Process command.
     *
     * @var array
     */
    protected $command = [];

    /**
     * Set response lines.
     *
     * @param  array  $lines
     * @return $this
     */
    public function withLines(array $lines = [])
    {
        $this->lines = $lines;
    }

    /**
     * Set response process.
     *
     * @param  \Symfony\Component\Process\Process $process
     * @return $this
     */
    public function withProcess(Process $process)
    {
        $this->process = $process;

        return $this;
    }

    /**
     * Set "non-zero" exit code.
     *
     * @return $this
     */
    public function shouldFail()
    {
        $this->exitCode = 1;

        return $this;
    }

    /**
     * Set "zero" exit code.
     *
     * @return $this
     */
    public function shouldSucceed()
    {
        $this->exitCode = 0;

        return $this;
    }

    /**
     * Set process command.
     *
     * @param  string  $command
     * @return $this
     */
    public function withCommand(array $command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Make a response instance.
     *
     * @param  string|null  $command
     * @return \TitasGailius\Terminal\Fakes\ResponseFake
     */
    public function make($command = null)
    {
        if (! is_null($command)) {
            $this->withCommand($command);
        }

        return new ResponseFake(
            $this->process ?: new Process($this->getCommand()),
            $this->lines,
            $this->exitCode
        );
    }
}