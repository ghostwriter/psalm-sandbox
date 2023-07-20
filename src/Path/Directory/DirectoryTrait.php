<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Path\Directory;

use Ghostwriter\PsalmPluginTester\Path\PathTrait;

trait DirectoryTrait
{
    use PathTrait;

    public function getDirectory(): string
    {
        return $this->path;
    }
}
