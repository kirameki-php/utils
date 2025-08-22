<?php declare(strict_types=1);

namespace Tests\Kirameki\Process;

use Kirameki\Process\ProcessBuilder;

final class ProcessBuilderTest extends TestCase
{
    public function test_wait(): void
    {
        $process = new ProcessBuilder(['bash', 'exit.sh', '--sleep', '0.01'])
            ->inDirectory($this->getScriptsDir())
            ->start();

        $process->writeToStdin("hello");
        $process->writeToStdin("world");

        $result = $process->wait();

        $this->assertSame("hello\nworld\n", $result->getStdin());
    }

    public function test_onStarted(): void
    {
        $called = 0;
        $process = new ProcessBuilder(['bash', 'exit.sh'])
            ->inDirectory($this->getScriptsDir())
            ->onStarted(function () use (&$called) { $called += 1; })
            ->start();

        $result = $process->wait();

        $this->assertTrue($result->succeeded());
        $this->assertSame(1, $called);
    }

    public function test_onFinished(): void
    {
        $called = 0;
        $process = new ProcessBuilder(['bash', 'exit.sh'])
            ->inDirectory($this->getScriptsDir())
            ->onFinished(function () use (&$called) { $called += 1; })
            ->start();

        $result = $process->wait();

        $this->assertTrue($result->succeeded());
        $this->assertSame(1, $called);
    }
}
