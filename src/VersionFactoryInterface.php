<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use Ghostwriter\PsalmPluginTester\Version\PhpVersionInterface;
use Ghostwriter\PsalmPluginTester\Version\PsalmVersionInterface;

interface VersionFactoryInterface
{
    public function createPhpVersion(string $version): PhpVersionInterface;

    public function createPsalmVersion(string $version): PsalmVersionInterface;
}
