<?php

namespace TitasGailius\Terminal\Tests;

use Mockery;
use DateTime;
use DateInterval;
use TitasGailius\Terminal\Builder;
use TitasGailius\Terminal\Terminal;
use Symfony\Component\Process\Process;
use TitasGailius\Terminal\Fakes\BuilderFake;

class FakeTerminalTest extends TestCase
{
    use ExecutesCommands;

    /**
     * Test that a "fake" terminal returns an instance of BuilderFake
     *
     * @return void
     */
    public function testFake()
    {
        Terminal::fake();

        $this->assertEquals(new BuilderFake, Terminal::builder());
    }

    /**
     * Test that Terminal can capture and assert executions.
     *
     * @return void
     */
    public function testCaptureAndAssertExecuted()
    {
        Terminal::fake();

        Terminal::execute($expected = 'echo "Hello, World"');

        Terminal::assertExecuted($expected);
    }

    public function testResponseLinesAsStrings()
    {
        Terminal::fake([
            'command with a single response line' => 'Line 1',
            'command with an array of response lines' => ['Line 1', 'Line 2'],
        ]);

        $this->execute('command with a single response line')
            ->assertOk()
            ->assertOutput('Line 1');

        $this->execute('command with an array of response lines')
            ->assertOk()
            ->assertLines(['Line 1', 'Line 2']);
    }

    public function testResponseLinesAsOutputLines()
    {
        Terminal::fake([
            'command with a single response line' => $singleLine = Terminal::line('Line 1'),

            'command with an array of response lines' => $multipleLines = [
                Terminal::line('Line 1'),
                Terminal::error('Line 2'),
            ],
        ]);

        $this->execute('command with a single response line')
            ->assertOutput('Line 1')
            ->assertLines(['Line 1'])
            ->assertLines([$singleLine])
            ->assertOk();

        $this->execute('command with an array of response lines')
            ->assertLines($multipleLines)
            ->assertOk();
    }

    public function testMockedProcess()
    {
        $process = Mockery::mock(Process::class, function ($mock) {
            $mock->shouldReceive('mockedMethod')
                ->once()
                ->andReturn(true);
        });

        Terminal::fake([
            'failing command' => Terminal::response($process),
        ]);

        $this->assertTrue(
            $this->execute('failing command')->mockedMethod()
        );
    }

    public function testFailingResponse()
    {
        Terminal::fake([
            'failing command' => Terminal::response()->shouldFail()
        ]);

        $this->execute('failing command')
            ->assertFailed();
    }

    public function testEmptyResponse()
    {
        Terminal::fake();

        $this->execute('command with an array of response lines')
            ->assertEmpty()
            ->assertOk();
    }
}