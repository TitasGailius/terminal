<?php

namespace TitasGailius\Terminal\Tests;

use Mockery;
use DateTime;
use DateInterval;
use TitasGailius\Terminal\Builder;
use TitasGailius\Terminal\Response;
use Symfony\Component\Process\Process;

class BuilderTest extends TestCase
{
    /**
     * Test that "withEnvironmentVariables" method correctly sets environment variables.
     *
     * @return void
     */
    public function testWithEnvironmentVariables()
    {
        $process = (new Builder)->withEnvironmentVariables([
            'APP_ENV' => 'production',
        ])->process();

        $this->assertEquals(['APP_ENV' => 'production'], $process->getEnv());
    }

    /**
     * Test that "in" method correctly sets current working directory.
     *
     * @return void
     */
    public function testIn()
    {
        $process = (new Builder)->in('my/directory')->process();

        $this->assertEquals('my/directory', $process->getWorkingDirectory());
    }

    /**
     * Test that "timeout" method accepts a DateInterval object.
     *
     * @return void
     */
    public function testTimeoutDateInterval()
    {
        $process = (new Builder)->timeout(
            new DateInterval('PT18S')
        )->process();

        $this->assertEquals(18, $process->getTimeout());
    }

    /**
     * Test that "timeout" method accepts a DateTime object.
     *
     * @return void
     */
    public function testTimeoutDateTime()
    {
        $process = (new Builder)->timeout(
            (new DateTime)->add(new DateInterval('PT19S'))
        )->process();

        $this->assertEquals(19, $process->getTimeout());
    }

    /**
     * Test that "timeout" method accepts an integer object.
     *
     * @return void
     */
    public function testTimeoutInteger()
    {
        $process = (new Builder)->timeout(20)->process();

        $this->assertEquals(20, $process->getTimeout());
    }

    /**
     * Test that "command" method correctly sets the executable command.
     *
     * @return void
     */
    public function testCommand()
    {
        $process = (new Builder)->command('rm -rf vendor')->process();

        $this->assertEquals('rm -rf vendor', $process->getCommandLine());
    }

    /**
     * Test that "execute" method does not override the executable command with null.
     *
     * @return void
     */
    public function testExecuteShouldNotOverrideCommandWithNull()
    {
        $builder = Mockery::mock(Builder::class, function ($mock) use ($process) {
            $mock->shouldAllowMockingProtectedMethods()
                ->makePartial()
                ->shouldReceive('runProcess')
                ->andReturn($process);
        });

        $builder->command('rm -rf vendor');
        $this->assertEquals('rm -rf vendor', $builder->process()->getCommandLine());

        $builder->execute();
        $this->assertEquals('rm -rf vendor', $builder->process()->getCommandLine());
    }

    /**
     * Test that "execute" method runs the process.
     *
     * @return void
     */
    public function testExecute()
    {
        $builder = $this->builderWithMockedProccess(function ($mock) {
            $mock->makePartial()
                ->shouldReceive('run')
                ->once()
                ->andReturn(0);
        });

        $response = $builder->execute();

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals($builder->process(), $response->process());
    }

    /**
     * Test that "execute" method starts the process.
     *
     * @return void
     */
    public function testExecuteInBackground()
    {
        $builder = $this->builderWithMockedProccess(function ($mock) {
            $mock->makePartial()
                ->shouldReceive('start')
                ->once()
                ->andReturn(0);
        });

        $builder->executeInBackground();
    }

    /**
     * Test that "output" method correctly sets the output handler.
     *
     * @return void
     */
    public function testOutput()
    {
        $firstOutput = function () {
            return 'Output received.';
        };

        $secondOutput = function () {
            return 'Output received.';
        };

        $builder = $this->builderWithMockedProccess(function ($mock) use ($firstOutput, $secondOutput) {
            $mock->shouldReceive('run')
                ->with($firstOutput)
                ->once();

            $mock->shouldReceive('run')
                ->with($secondOutput)
                ->once();
        });

        $builder->execute($firstOutput);
        $builder->output($secondOutput)->execute();
    }

    /**
     * Test that the builder can be extended with custom methods.
     *
     * @return void
     */
    public function testExtensions()
    {
        $this->assertFalse(method_exists(Builder::class, 'setDefaultTimeout'));

        Builder::extend('setDefaultTimeout', function ($terminal) {
            return $terminal->timeout(25);
        });

        $builder = new Builder;

        $this->assertEquals(60, $builder->process()->getTimeout());
        $this->assertEquals(25, $builder->setDefaultTimeout()->process()->getTimeout());
    }

    /**
     * Test that the "with" command prepares the command correctly.
     *
     * @return void
     */
    public function testData()
    {
        $process = (new Builder)->with([
            'foo' => 'World',
        ])->command('echo Hello, {{ $foo }}')->process();

        $this->assertEquals('echo Hello, "${:terminal_foo}"', $process->getCommandLine());
        $this->assertEquals(['terminal_foo' => 'World'], $process->getEnv());

        $process->run();

        $this->assertEquals("Hello, World\n", $process->getOutput());
    }

    public function testDataWithMissingBinding()
    {
        $process = (new Builder)->command('echo Hello, {{ $foo }}')->process();

        $this->assertEquals('echo Hello, "${:terminal_foo}"', $process->getCommandLine());
        $this->assertEquals(['terminal_foo' => ''], $process->getEnv());

        $process->run();

        $this->assertEquals("Hello, \n", $process->getOutput());
    }

    /**
     * Create a new builder instance with a mocked process instance.
     *
     * @param  callable $mocker
     * @return \TitasGailius\Terminal\Builder
     */
    protected function builderWithMockedProccess($mocker = null)
    {
        $process = Mockery::mock(Process::class, function ($mock) use ($mocker) {
            if (! is_null($mocker)) {
                $mocker($mock);
            }

            $mock->makePartial();
            $mock->shouldReceive('run')->andReturn(0);
            $mock->shouldReceive('start')->andReturn(0);
        });

        $builder = Mockery::mock(Builder::class, function ($mock) use ($process) {
            $mock->makePartial()
                ->shouldReceive('process')
                ->andReturn($process);
        });

        return $builder;
    }
}