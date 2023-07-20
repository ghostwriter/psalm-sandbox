<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Path\Directory;

use Ghostwriter\PsalmPluginTester\Path\PathInterface;

interface DirectoryInterface extends PathInterface
{
    public function getDirectory(): string;
}
