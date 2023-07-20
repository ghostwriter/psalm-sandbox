<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use Symfony\Component\Process\Process;

final class ShellResult
{
    public function __construct(
        private readonly Process $process,
    ) {
    }

    public function getErrorOutput(): string
    {
        return $this->process->getErrorOutput();
    }

    public function getExitCode(): int
    {
        return $this->process->getExitCode();
    }

    public function getOutput(): string
    {
        return $this->process->getOutput();
    }

    public function getProcess(): Process
    {
        return $this->process;
    }
}
