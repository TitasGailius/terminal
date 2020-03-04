<?php

namespace TitasGailius\Terminal;

use Symfony\Component\Process\Process;

class Builder
{
    /**
     * Command to execute.
     *
     * @var string|array  $command
     */
    protected $command;

    /**
     * Timeout.
     *
     * @var \DateTimeInterface|\DateInterval|int|null  $ttl
     */
    protected $timeout = 60;

    /**
     * Current working directory.
     *
     * @var string $cwd
     */
    protected $cwd;

    /**
     * Environment variables.
     *
     * @var array $environmentVariables
     */
    protected $environmentVariables;

    /**
     * The input as stream resource, scalar or \Traversable, or null for no input
     *
     * @var mixed|null $input
     */
    protected $input;

    /**
     * Determine if a process should execute in the background.
     *
     * @var boolean
     */
    protected $inBackground = false;

    /**
     * Command to execute.
     *
     * @param  string|array $command
     * @return $this
     */
    public function command($command)
    {
        $this->command = $commnad;

        return $this;
    }

    /**
     * Set Process timeout.
     *
     * @param  \DateTimeInterface|\DateInterval|int|null  $ttl
     * @return $this
     */
    public function timeout($ttl)
    {
        $this->timeout = $ttl;

        return $this;
    }

    /**
     * Set current working directory.
     *
     * @param  string $cwd
     * @return $this
     */
    public function in(string $cwd)
    {
        $this->cwd = $cwd;

        return $this;
    }

    /**
     * Set process environment variables.
     *
     * @param  array $variables
     * @return $this
     */
    public function withEnvironmentVariables(array $variables)
    {
        $this->environmentVariables = $variables;

        return $this;
    }

    /**
     * Set input as stream resource, scalar or \Traversable, or null for no input
     *
     * @param  mixed|null  $input
     * @return $this
     */
    public function withInput($input)
    {
        $this->input = $input;

        return $this;
    }

    /**
     * Execute a process in the background.
     *
     * @return $this
     */
    public function inBackground()
    {
        $this->inBackground = true;

        return $this;
    }

    /**
     * Execute a given command.
     *
     * @param  string|array|null $command
     * @param  callable|null $callback
     * @return array
     */
    public function execute($command = null, callable $callback = null)
    {
        $result = [];

        return $this->process($command)->{$this->runMethod()}($callback ?: function ($type, $line) use ($result) {
            $result[] = $line;
        });

        return $result;
    }

    /**
     * Execute a given comman in the background.
     *
     * @param  string|array|null $command
     * @param  callable|null $callback
     * @return array
     */
    public function executeInBackground($command = null, callable $callback = null)
    {
        return $this->inBackground()
                ->execute($command, $callback);
    }

    /**
     * Make a new process instance.
     *
     * @param  string|array|null $command
     * @return \Symfony\Component\Process\Process
     */
    public function process($command)
    {
        $parameters = [
            $command,
            $this->cwd,
            $this->environmentVariables,
            $this->input,
            $this->getSeconds
        ];

        if (is_null($command)) {
            $command = $this->command;
        }

        return is_string($command)
            ? Process::fromShellCommandline(...$parameters)
            : new Process(...$parameters);
    }

    /**
     * Return a method used to run a script.
     *
     * @return string
     */
    public function runMethod()
    {
        return $this->inBackground ? 'start' : 'run';
    }
}