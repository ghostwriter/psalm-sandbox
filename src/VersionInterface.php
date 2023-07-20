<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

interface VersionInterface
{
    public function getVersion(): string;

    public function satisfies(string $constraints): bool;
}
