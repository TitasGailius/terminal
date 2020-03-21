<?php

namespace TitasGailius\Terminal\Tests;

use Mockery;
use TitasGailius\Terminal\Response;
use TitasGailius\Terminal\OutputLine;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class OutputLineTest extends TestCase
{
    /**
     * Test that "__toString" method returns contents of the output line.
     *
     * @return void
     */
    public function testToString()
    {
        $line = new OutputLine(Process::ERR, $expected = 'Hello, World');

        $this->assertEquals($expected, (string) $line);
    }

    /**
     * Test that "content" method returns contents of the output line.
     *
     * @return void
     */
    public function testContent()
    {
        $line = new OutputLine(Process::ERR, $expected = 'Hello, World');

        $this->assertEquals($expected, $line->content());
    }

    /**
     * Test taht "type" method returns the type of the output line.
     *
     * @return void
     */
    public function testType()
    {
        $line = new OutputLine(Process::ERR, $expected = 'Hello, World');

        $this->assertEquals(Process::ERR, $line->type());
    }

    /**
     * Test that "error" method work correctly.
     *
     * @return void
     */
    public function testError()
    {
        $error = new OutputLine(Process::ERR, 'Error Line.');
        $success = new OutputLine(Process::OUT, 'Info Line.');

        $this->assertTrue($error->error());
        $this->assertFalse($success->error());
    }

    /**
     * Test that "ok" method work correctly.
     *
     * @return void
     */
    public function testOk()
    {
        $error = new OutputLine(Process::ERR, 'Error Line.');
        $success = new OutputLine(Process::OUT, 'Info Line.');

        $this->assertTrue($success->ok());
        $this->assertFalse($error->ok());
    }
}