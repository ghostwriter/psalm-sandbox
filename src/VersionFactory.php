<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use Composer\Semver\VersionParser;
use Ghostwriter\PsalmPluginTester\Version\PhpVersion;
use Ghostwriter\PsalmPluginTester\Version\PsalmVersion;

final class VersionFactory implements VersionFactoryInterface
{
    public function __construct(
        private readonly VersionParser $versionParser = new VersionParser()
    ) {
    }

    public function createPhpVersion(string $version): PhpVersion
    {
        return new PhpVersion($version, $this->versionParser);
    }

    public function createPsalmVersion(string $version): PsalmVersion
    {
        return new PsalmVersion($version, $this->versionParser);
    }
}
