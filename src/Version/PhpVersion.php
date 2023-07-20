<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Version;

use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use Ghostwriter\Option\OptionInterface;

final class PhpVersion implements PhpVersionInterface
{
    public function __construct(
        private readonly string $version,
        private readonly VersionParser $versionParser = new VersionParser()
    ) {
    }

    public function getPhpVersion(): OptionInterface
    {
    }

    public function getVersion(): string
    {
        return $this->versionParser->normalize($this->version);
    }

    public function satisfies(string $constraints): bool
    {
        return Semver::satisfies($this->getVersion(), $constraints);
    }
}
