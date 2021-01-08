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
        $builder = new Builder;

        $builder->command('echo Hello, World');
        $this->assertEquals('echo Hello, World', $builder->process()->getCommandLine());

        $builder->execute();
        $this->assertEquals('echo Hello, World', $builder->process()->getCommandLine());
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
     * Test that the terminal can execute array commands.
     *
     * @return void
     */
    public function testExecuteArrayCommands()
    {
        $response = (new Builder)->run(['echo', 'Hello, World']);

        $this->assertEquals("Hello, World\n", (string) $response);
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
     * Test that missing methods are passed to the Proccess instance.
     *
     * @return void
     */
    public function testBuilderProxy()
    {
        $builder = $this->builderWithMockedProccess(function ($mock) {
            $mock->shouldReceive('getPid')
                ->once()
                ->andReturn(123);
        });

        $this->assertEquals(123, $builder->getPid());
    }

    /**
     * Test that it retries a failed command.
     *
     * @return void
     */
    public function testRetry()
    {
        $builder = $this->builderWithMockedProccess(function ($mock) {
            $mock->shouldReceive('isSuccessful')
                ->times(3)
                ->andReturn(false, false , true);
        });

        $builder->retries(3)
            ->execute('echo Hello, World');
    }

    /**
     * Test that terminal can convert commands to string.
     *
     * @return void
     */
    public function testToString()
    {
        $this->assertEquals(
            '\'echo\' \'Hello, World\'',
            (new Builder)->toString(['echo', 'Hello, World'])
        );

        $this->assertEquals(
            '\'echo\' \'Hello, World\'',
            (new Builder)->command(['echo', 'Hello, World'])->toString()
        );

        $this->assertEquals(
            'echo "Hello, World"',
            (new Builder)->toString('echo "Hello, World"')
        );
    }

    /**
     * Test that termina can handle Symfony' OutputInterface.
     *
     * @return void
     */
    public function testOutputAsSymfonyOutputInterface()
    {
        $output = Mockery::mock('Symfony\Component\Console\Output\OutputInterface', function ($mock) {
            $mock->shouldReceive('write')
                ->once()
                ->with("Hello\n")
                ->andReturn(null);
        });

        (new Builder)->output($output)->run('echo Hello');
    }

    /**
     * Test that termina can handle Symfony' OutputInterface.
     *
     * @return void
     */
    public function testOutputAsLaravelCommad()
    {
        $output = Mockery::mock('Illuminate\Console\Command', function ($mock) {
            $mock->shouldReceive('getOutput->write')
                ->once()
                ->with("Hello\n")
                ->andReturn(null);
        });

        (new Builder)->output($output)->run('echo Hello');
    }

    /**
     * Get invalid outputs.
     *
     * @return array
     */
    public function invalidOutputs(): array
    {
        return [
            [123],
            ['string'],
            [new \stdClass],
        ];
    }

    /**
     * Test Terminal output validation.
     *
     * @dataProvider invalidOutputs
     */
    public function testOutputValidation($output)
    {
        $this->expectException('InvalidArgumentException');
        (new Builder)->output($output)->run('echo Hello');
    }

    /**
     * Test Terminal TTY mode.
     *
     * @return void
     */
    public function testTtyMode()
    {
        if (! Process::isTtySupported()) {
            $this->markTestSkipped('There is no TTY support.');
        }

        $process = (new Builder)->tty(false)->process();
        $this->assertFalse($process->isTty());

        $process = (new Builder)->tty(true)->process();
        $this->assertTrue($process->isTty());

        $process = (new Builder)->disableTty()->process();
        $this->assertFalse($process->isTty());

        $process = (new Builder)->enableTty()->process();
        $this->assertTrue($process->isTty());
    }

    /**
     * Test builder idle timeout.
     *
     * @dataProvider twentySeconds
     */
    public function testIdleTimeout($twentySeconds)
    {
        $process = (new Builder)->idleTimeout($twentySeconds)->process();
        $this->assertEquals(20, $process->getIdleTimeout());
    }

    /**
     * Get invalid outputs.
     *
     * @return array
     */
    public function twentySeconds(): array
    {
        return [
            [20],
            [20.0],
            [new DateInterval('PT20S')],

            // @todo: Think of a better way to test DateTime instance.
            // The current code causes a race-condition. By the time `getIdelTimeout`
            // is called a second might have passed resulting in 19 seconds instead of 20.
            // [(new DateTime)->add(new DateInterval('PT20S'))],
        ];
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
