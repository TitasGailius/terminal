<?php

namespace TitasGailius\Terminal\Tests;

use Mockery;
use DateTime;
use DateInterval;
use TitasGailius\Terminal\Builder;
use TitasGailius\Terminal\Terminal;
use Symfony\Component\Process\Process;
use TitasGailius\Terminal\Fakes\BuilderFake;

class TerminalTest extends TestCase
{
    /**
     * Test that static methods are passed to the instance of a Builder class.
     *
     * @return void
     */
    public function testBuilderMethodProxy()
    {
        $this->assertEquals((new Builder)->timeout(25), Terminal::timeout(25));
        $this->assertEquals((new Builder)->command('rm -rf vendor'), Terminal::command('rm -rf vendor'));
        $this->assertEquals((new Builder)->getCommand(), Terminal::getCommand());
        $this->assertEquals((new Builder)->in('storage/framework'), Terminal::in('storage/framework'));
        $this->assertEquals((new Builder)->withEnvironmentVariables(['foo' => 'bar']), Terminal::withEnvironmentVariables(['foo' => 'bar']));
        $this->assertEquals((new Builder)->output(function () {}), Terminal::output(function () {}));
        $this->assertEquals((new Builder)->inBackground(true), Terminal::inBackground(true));
        $this->assertEquals((new Builder)->retries(2, 100), Terminal::retries(2, 100));
        $this->assertEquals((new Builder)->process(), Terminal::process());
    }

    /**
     * Test that extend method is passed to the Builder.
     *
     * @return void
     */
    public function testExtend()
    {
        Terminal::extend('defaultTimeout', function ($terminal) {
            return $terminal->timeout(15);
        });

        $this->assertEquals((new Builder)->timeout(15), Terminal::defaultTimeout());
    }

    /**
     * Test that "builder" method returns a new instance of Builder.
     *
     * @return void
     */
    public function testBuilder()
    {
        $this->assertEquals(new Builder, Terminal::builder());
    }
}
