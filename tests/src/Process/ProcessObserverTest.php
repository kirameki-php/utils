<?php declare(strict_types=1);

namespace Tests\Kirameki\Process;

use Kirameki\Process\ExitCode;
use Kirameki\Process\ProcessBuilder;
use Kirameki\Process\ProcessObserver;
use Kirameki\Process\SignalEvent;
use function dump;
use function range;
use function usleep;
use const CLD_EXITED;
use const SIGCHLD;
use const SIGCONT;
use const SIGSTOP;

final class ProcessObserverTest extends TestCase
{
    public function test_ignore_CLD_STOPPED(): void
    {
        $process = new ProcessBuilder(['bash', 'exit.sh', '--sleep', '0.1'])
            ->inDirectory($this->getScriptsDir())
            ->start();

        $process->signal(SIGSTOP);
        usleep(10_000);
        $process->signal(SIGCONT);
        $result = $process->wait();

        $this->assertFalse($process->isRunning());
        $this->assertTrue($result->succeeded());
    }

    public function test_multiple_processes(): void
    {
        $processes = [];
        foreach (range(1, 300) as $i) {
            $process = new ProcessBuilder(['echo', '$$'])->start();
            $processes[$process->info->pid] = $process;
        }

        foreach ($processes as $process) {
            $process->wait();
        }

        $this->assertTrue(true);
    }

    public function test_signal_received_before_exit_callback_registered(): void
    {
        $observer = new class extends ProcessObserver {
            public function __construct()
            {
                parent::__construct();
            }

            public function sendSignal(SignalEvent $event): void
            {
                $this->handleSignal($event);
            }

            /**
             * @return array<int, int>
             */
            public function getExitBeforeRegistered(): array
            {
                return $this->exitedBeforeRegistered;
            }
        };

        $observer::observe();
        $pid = 12345; // Simulated PID
        $exitCode = null;

        $info = ['pid' => $pid, 'status' => ExitCode::SUCCESS, 'code' => CLD_EXITED];
        $observer->sendSignal(new SignalEvent(SIGCHLD, $info, true));

        $this->assertSame([12345 => 0], $observer->getExitBeforeRegistered());

        $observer->onExit($pid, function(int $code) use (&$exitCode) { $exitCode = $code; });
        $this->assertSame(0, $exitCode);
        $this->assertSame(0, $observer::getProcessCount());
    }
}
