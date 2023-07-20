<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

trait VersionTrait
{
    public function __construct(
        private readonly string $version,
    ) {
    }

    final public function getPhpVersion(): VersionInterface|null
    {
        return $this->version;
    }

    final public function getVersion(): string
    {
        return $this->version;
    }
}
