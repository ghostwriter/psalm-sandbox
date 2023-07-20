<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Version;

use Composer\Semver\Semver;
use Ghostwriter\Option\None;
use Ghostwriter\Option\OptionInterface;

final class PsalmVersion implements PsalmVersionInterface
{
    public function __construct(
        private readonly string $version,
    ) {
    }

    public function getPsalmVersion(): OptionInterface
    {
        return None::create();
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function satisfies(string $constraints): bool
    {
        return Semver::satisfies($this->getVersion(), $constraints);
    }
}
