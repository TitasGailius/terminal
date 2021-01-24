<?php

namespace TitasGailius\Terminal;

use DateTime;
use Exception;
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
     * The callback that is run whenever there is some output available.
     *
     * @var callable $output
     */
    protected $output;

    /**
     * The input as stream resource, scalar or \Traversable, or null for no input.
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
     * TTY mode.
     *
     * @var boolean|null
     */
    protected $tty;

    /**
     * Max time since last output.
     *
     * @var mixed
     */
    protected $idleTimeout;

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
     * @param  mixed  $output
     * @return $this
     */
    public function output($output)
    {
        $this->output = $this->parseOutput($output);

        return $this;
    }

    /**
     * Parse a given output.
     *
     * @param  mixed  $output
     * @return callable
     */
    protected function parseOutput($output)
    {
        if (is_callable($output)) {
            return $output;
        }

        if ($output instanceof \Symfony\Component\Console\Output\OutputInterface) {
            return $this->wrapOutput([$output, 'write']);
        }

        if ($output instanceof \Illuminate\Console\Command) {
            return $this->wrapOutput([$output->getOutput(), 'write']);
        }

        throw new InvalidArgumentException(sprintf(
            'Terminal output must be a %s, an instance of "%s" or an instance of "%s" but "%s" was given.',
            'callable', 'Symfony\Component\Console\Output\OutputInterface', 'Illuminate\Console\Command',
            ($type = gettype($output)) === 'object' ? get_class($output) : $type
        ));
    }

    /**
     * Wrap output callback.
     *
     * @param  callable  $callback
     * @return callable
     */
    protected function wrapOutput(callable $callback): callable
    {
        return function ($type, $data) use ($callback) {
            return call_user_func($callback, $data);
        };
    }

    /**
     * Set input.
     *
     * @param  mixed  $input
     * @return $this
     */
    public function input($input)
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
     * Retry an operation a given number of times.
     *
     * @param  int  $times
     * @param  int  $sleep
     * @return $this
     */
    public function retries(int $times, int $sleep = 0)
    {
        $this->retries = [$times, $sleep];

        return $this;
    }

    /**
     * Bind command data.
     *
     * @param  mixed  $key
     * @param  mixed  $value
     * @return $this
     */
    public function with($key, $value = null)
    {
        $this->with = array_merge($this->with, is_array($key) ? $key : [$key => $value]);

        return $this;
    }

    /**
     * Enable or disable the TTY mode.
     *
     * @param  bool  $tty
     * @return $this
     */
    public function tty(bool $tty)
    {
        $this->tty = $tty;

        return $this;
    }

    /**
     * Enable TTY mode.
     *
     * @return $this
     */
    public function enableTty()
    {
        return $this->tty(true);
    }

    /**
     * Disable TTY mode.
     *
     * @return $this
     */
    public function disableTty()
    {
        return $this->tty(false);
    }

    /**
     * Set max time since last output.
     *
     * @param  mixed  $timeout
     * @return $this
     */
    public function idleTimeout($timeout)
    {
        $this->idleTimeout = $timeout;

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

        return $this->retry($times, function ($attempts) {
            return $this->runProcess($this->process())->throw();
        }, $sleep);
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
    public function runProcess(Process $process)
    {
        $process->{$this->inBackground ? 'start' : 'run'}($this->output);

        return new Response($process);
    }

    /**
     * Retry an operation a given number of times.
     *
     * @param  int  $times
     * @param  callable  $callback
     * @param  int  $sleep
     * @return mixed
     *
     * @throws \Exception
     */
    protected function retry($times, callable $callback, $sleep = 0)
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

        $process = is_string($command)
            ? Process::fromShellCommandline(...$parameters)
            : new Process(...$parameters);

        if (! is_null($this->tty)) {
            $process->setTty($this->tty);
        }

        if (! is_null($this->idleTimeout)) {
            $process->setIdleTimeout($this->getSeconds($this->idleTimeout));
        }

        return $process;
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
     * Get the current command as string.
     *
     * @param  string|array|null  $command
     * @return string
     */
    public function toString($command = null)
    {
        if (! is_null($command)) {
            $this->command($command);
        }

        if (is_string($this->command)) {
            return $this->command;
        }

        return $this->process()->getCommandLine();
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

        if (method_exists($process = $this->process(), $method)) {
            return $process->{$method}(...$parameters);
        }

        throw new BadMethodCallException(sprintf(
            'Call to undefined method %s::%s()', static::class, $method
        ));
    }
}
