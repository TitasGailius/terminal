<?php

namespace TitasGailius\Terminal\Tests;

use PHPUnit\Framework\Assert;
use TitasGailius\Terminal\Terminal;
use TitasGailius\Terminal\Contracts\Response;
use TitasGailius\Terminal\Fakes\ResponseFake;

trait ExecutesCommands
{
    /**
     * Execute a given command.
     *
     * @param  string $command
     * @return \TitasGailius\Terminal\Tests\ResponseAssert
     */
    public function execute(string $command)
    {
        return new ResponseAssert(Terminal::execute($command), $command);
    }
}

class ResponseAssert
{
    /**
     * Response.
     *
     * @var \TitasGailius\Terminal\Contracts\Response
     */
    public $response;

    /**
     * Command.
     *
     * @var string
     */
    public $command;

    /**
     * Instantiate a new ResponseAssert instance.
     *
     * @param \TitasGailius\Terminal\Contracts\Response  $response
     */
    public function __construct(Response $response, string $command)
    {
        $this->response = $response;
        $this->command = $command;
    }

    /**
     * Dynamiaclly pass method calls to the response instance.
     *
     * @param  string $method
     * @param  array $parameters
     * @return mixed
     */
    public function __call(string $method, array $parameters = [])
    {
        return call_user_func([$this->response, $method], ...$parameters);
    }

    /**
     * Assert output.
     *
     * @param  string  $expected
     * @param  string  $message
     * @return $this
     */
    public function assertOutput(string $expected, string $message = '')
    {
        Assert::assertEquals($expected, $this->output(), $message);

        return $this;
    }

    /**
     * Assert lines.
     *
     * @param  array  $expected
     * @return $this
     */
    public function assertLines(array $expected, string $message = '')
    {
        Assert::assertEquals(array_map([Terminal::class, 'line'], $expected), $this->lines(), $message);

        return $this;
    }

    /**
     * Assert ok.
     *
     * @param  string  $message
     * @return $this
     */
    public function assertOk(string $message = '')
    {
        Assert::assertTrue($this->ok(), $message);

        return $this;
    }

    /**
     * Assert failed.
     *
     * @param  string  $message
     * @return $this
     */
    public function assertFailed(string $message = '')
    {
        Assert::assertFalse($this->ok(), $message);

        return $this;
    }

    /**
     * Assert an empty response.
     *
     * @param  string  $message
     * @return $this
     */
    public function assertEmpty(string $message = '')
    {
        $this->assertOutput('');

        return $this;
    }

    /**
     * Assert that the current command was executed.
     *
     * @return $this
     */
    public function assertExecuted()
    {
        Terminal::executed($this->command);

        return $this;
    }
}