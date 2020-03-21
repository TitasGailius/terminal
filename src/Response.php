<?php

namespace TitasGailius\Terminal;

use BadMethodCallException;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use TitasGailius\Terminal\Contracts\Response as ResponseContract;

class Response implements ResponseContract
{
    /**
     * Process.
     *
     * @var \Symfony\Component\Process\Process
     */
    protected $process;

    /**
     * Instantiate a new response instance.
     *
     * @param \Symfony\Component\Process\Process $process
     */
    public function __construct(Process $process)
    {
        $this->process = $process;
    }

    /**
     * Return an array of outputed lines.
     *
     * @return array
     */
    public function lines()
    {
        $result = [];

        foreach ($this->getIterator() as $line) {
            $result[] = $line;
        }

        return $result;
    }

    /**
     * Throw an exception if the process was not successful.
     *
     * @return $this
     *
     * @throws \Symfony\Component\Process\Exception\ProcessFailedException
     */
    public function throw()
    {
        if ($this->successful()) {
            return $this;
        }

        throw new ProcessFailedException($this->process());
    }

    /**
     * Check if the process ended successfully.
     *
     * @return bool
     */
    public function ok()
    {
        return $this->successful();
    }

    /**
     * Check if the process ended successfully.
     *
     * @return bool
     */
    public function successful()
    {
        return $this->process()->isSuccessful();
    }

    /**
     * Get the process output.
     *
     * @return string
     */
    public function output()
    {
        return $this->process()->getOutput();
    }

    /**
     * Get the underlying process instance.
     *
     * @return \Symfony\Component\Process\Process
     */
    public function process()
    {
        return $this->process;
    }

    /**
     * Get output iterator.
     *
     * @return \Generator
     */
    public function getIterator()
    {
        foreach ($this->process() as $type => $line) {
            yield new OutputLine($type, $line);
        }
    }

    /**
     * Get the process output.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->output();
    }

    /**
     * Dynamically forward calls to the process instance.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters = [])
    {
        return call_user_func([$this->process(), $method], ...$parameters);
    }
}
