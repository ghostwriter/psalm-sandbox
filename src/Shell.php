<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

final class Shell
{
    public function execute(string $command, array $arguments, string|null $workingDirectory): ShellResult
    {
        return $this->run(
            [
                (new ExecutableFinder())->find($command, $command),
                ...$arguments,
            ],
            $workingDirectory ?? getcwd()
        );
    }

    private function run(array $command, string $workingDirectory): ShellResult
    {
        $process = new Process($command, $workingDirectory);

        $process->run();

        return new ShellResult($process);
    }
}
