<?php

namespace TitasGailius\Terminal;

use DateTime;
use DateInterval;
use BadMethodCallException;
use InvalidArgumentException;
use Symfony\Component\Process\Process;

class Builder
{
    /**
     * Command to execute.
     *
     * @var string|array  $command
     */
    protected $command = [];

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
    protected $output;

    /**
     * Determine if a process should execute in the background.
     *
     * @var boolean
     */
    protected $inBackground = false;

    /**
     * Retry configuration for the command.
     *
     * @var array|null
     */
    protected $retries = [1, 0];

    /**
     * Command data bindings.
     *
     * @var array
     */
    protected $with = [];

    /**
     * Builder extensions.
     *
     * @var array
     */
    protected static $extensions = [];

    /**
     * Extend the builder with a custom method.
     *
     * @param  string  $method
     * @param  callable  $callback
     * @return void
     */
    public static function extend(string $method, callable $callback)
    {
        static::$extensions[$method] = $callback;
    }

    /**
     * Command to execute.
     *
     * @param  string|array $command
     * @return $this
     */
    public function command($command)
    {
        $this->command = $command;

        return $this;
    }

    /**
     * Get the executable command.
     *
     * @return string|array $command
     */
    public function getCommand()
    {
        return $this->command;
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
     * @param  array $environmentVariables
     * @return $this
     */
    public function withEnvironmentVariables(array $environmentVariables)
    {
        $this->environmentVariables = $environmentVariables;

        return $this;
    }

    /**
     * Set output handler.
     *
     * @param  callable  $output
     * @return $this
     */
    public function output(callable $output)
    {
        $this->output = $output;

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
     * Retry an operation a given number of times.
     *
     * @param  int  $times
     * @param  int  $sleep
     * @return $this
     */
    public function retries(int $times, int $sleep)
    {
        $this->retries = [$times, $sleep];

        return $this;
    }

    /**
     * Bind command data.
     *
     * @param  mied  $key
     * @param  mixed  $value
     * @return $this
     */
    public function with($key, $value = null)
    {
        $this->with = array_merge($this->with, is_array($key) ? $key : [$key => $value]);

        return $this;
    }

    /**
     * Execute a given command.
     *
     * @param  mixed $command
     * @param  callable|null $output
     * @return \TitasGailius\Terminal\Response
     */
    public function run($command = null, callable $output = null)
    {
        return $this->execute($command, $output);
    }

    /**
     * Execute a given command.
     *
     * @param  mixed $command
     * @param  callable|null $output
     * @return \TitasGailius\Terminal\Response
     */
    public function execute($command = null, callable $output = null)
    {
        if (is_callable($command)) {
            [$command, $output] = [null, $command];
        }

        if (! is_null($command)) {
            $this->command($command);
        }

        if (! is_null($output)) {
            $this->output($output);
        }

        [$times, $sleep] = $this->retries;

        if ($times <= 1) {
            return $this->runProcess($this->process());
        }

        return $this->retry($times, $sleep, function () use ($process) {
            return $this->runProcess($process)->throw();
        });
    }

    /**
     * Execute a given comman in the background.
     *
     * @param  mixed $command
     * @param  callable|null $callback
     * @return \TitasGailius\Terminal\Response
     */
    public function executeInBackground($command = null, callable $callback = null)
    {
        return $this->inBackground()
                ->execute($command, $callback);
    }

    /**
     * Run a given process.
     *
     * @param  \Symfony\Component\Process\Process  $process
     * @return \TitasGailius\Terminal\Response
     */
    protected function runProcess(Process $process)
    {
        $process->{$this->inBackground ? 'start' : 'run'}($this->output);

        return new Response($process);
    }

    /**
     * Retry an operation a given number of times.
     *
     * @param  int  $times
     * @param  int  $sleep
     * @param  callable  $callback
     * @return mixed
     *
     * @throws \Exception
     */
    public function retry($times, $sleep = 0, callable $callback)
    {
        $attempts = 0;

        beginning:
        $attempts++;
        $times--;

        try {
            return $callback($attempts);
        } catch (Exception $e) {
            if ($times < 1) {
                throw $e;
            }

            if ($sleep) {
                usleep($sleep * 1000);
            }

            goto beginning;
        }
    }

    /**
     * Make a new process instance.
     *
     * @return \Symfony\Component\Process\Process
     */
    public function process()
    {
        $parameters = [
            $command = $this->prepareCommand($this->command),
            $this->cwd,
            $this->environmentVariables,
            $this->input,
            $this->getSeconds($this->timeout)
        ];

        return is_string($command)
            ? Process::fromShellCommandline(...$parameters)
            : new Process(...$parameters);
    }

    /**
     * Prepare a given command.
     *
     * @param  mixed  $command
     * @return string
     */
    protected function prepareCommand($command)
    {
        if (! is_string($command)) {
            return $command;
        }

        return preg_replace_callback('/\{\{\s?\$(\w+)\s?\}\}/u', function ($matches) use ($command) {
            $this->environmentVariables[$key = 'terminal_'.$matches[1]] = $this->with[$matches[1]] ?? '';

            return sprintf('"${:%s}"', $key);
        }, $command);
    }

    /**
     * Get timeout seconds.
     *
     * @param int|\DateTime $timeout
     * @return int
     */
    protected function getSeconds($timeout)
    {
        if ($timeout instanceof DateInterval) {
            $timeout = (new DateTime)->add($timeout);
        }

        if ($timeout instanceof DateTime) {
            return $timeout->getTimestamp() - (new DateTime)->getTimestamp();
        }

        return $timeout;
    }

    /**
     * Dynamically forward calls to the process instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters)
    {
        if (isset(static::$extensions[$method])) {
            return static::$extensions[$method]($this);
        }

        if (method_exists($this->process, $method)) {
            return $this->process()->{$method}(...$parameters);
        }

        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', static::class, $method
        ));
    }
}