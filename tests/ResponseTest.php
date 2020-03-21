<?php

namespace TitasGailius\Terminal\Tests;

use Mockery;
use TitasGailius\Terminal\Response;
use TitasGailius\Terminal\OutputLine;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ResponseTest extends TestCase
{
    /**
     * Test that the response object proxies methods to the process instance.
     *
     * @return void
     */
    public function testProxyToProcessMethods()
    {
        $process = Mockery::mock(Process::class, function ($mock) {
            $mock->shouldReceive('run')
                ->once()
                ->andReturn(true);
        });

        (new Response($process))->run();
    }

    /**
     * Test that "ok" method returns "true" when process finished successfully.
     *
     * @return boid
     */
    public function testOkAndSuccessful()
    {
        $response = new Response(Mockery::mock(Process::class, function ($mock) {
            $mock->makePartial()
                ->shouldReceive('getExitCode')
                ->andReturn(0, 0, 1, 1);
        }));

        $this->assertTrue($response->ok());
        $this->assertTrue($response->successful());

        $this->assertFalse($response->ok());
        $this->assertFalse($response->successful());
    }

    /**
     * Throw an exception if the process failed.
     *
     * @return void
     */
    public function testThrow()
    {
        $response = new Response($process = Mockery::mock(Process::class, function ($mock) {
            $mock->makePartial()
                ->shouldReceive('getExitCode')
                ->andReturn(0, 1);

            $mock->shouldReceive('isOutputDisabled')
                ->andReturn(true);
        }));

        $this->assertEquals($response, $response->throw());

        $this->expectException(ProcessFailedException::class);

        $response->throw();
    }

    /**
     * Test that "output" methods call "getOutput" on the process instance.
     *
     * @return void
     */
    public function testOutput()
    {
        $expected = 'Command completed successfully.';

        $response = new Response(Mockery::mock(Process::class, function ($mock) use($expected) {
            $mock->makePartial()
                ->shouldReceive('getOutput')
                ->andReturn($expected);
        }));

        $this->assertEquals($expected, $response->output());
        $this->assertEquals($expected, (string) $response);
    }

    /**
     * Test that "lines" method converts the output to an array of printed lines.
     *
     * @return void
     */
    public function testLines()
    {
        $expectedLines = [
            new OutputLine(Process::OUT, 'Line 1'),
            new OutputLine(Process::OUT, 'Line 2'),
            new OutputLine(Process::OUT, 'Line 3'),
        ];

        $generator = function () use ($expectedLines) {
            foreach ($expectedLines as $line) {
                yield $line->type() => $line->content();
            }
        };

        $response = new Response(Mockery::mock(Process::class, function ($mock)  use ($generator) {
            $mock->shouldReceive('getIterator')
                ->andReturnUsing($generator);
        }));

        $this->assertEquals($expectedLines, $response->lines());
    }

    /**
     * Test that Reponse iterator converts the output to OutputLines.
     *
     * @return void
     */
    public function testIterator()
    {
        $expectedLines = [
            new OutputLine(Process::OUT, 'Line 1'),
            new OutputLine(Process::OUT, 'Line 2'),
            new OutputLine(Process::OUT, 'Line 3'),
        ];

        $generator = function () use ($expectedLines) {
            foreach ($expectedLines as $line) {
                yield $line->type() => $line->content();
            }
        };

        $response = new Response(Mockery::mock(Process::class, function ($mock)  use ($generator) {
            $mock->shouldReceive('getIterator')
                ->andReturnUsing($generator);
        }));

        foreach ($response as $index => $line) {
            $this->assertEquals($expectedLines[$index], $line);
        }
    }
}