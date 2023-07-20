<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Path\File;

use Ghostwriter\PsalmPluginTester\Path\PathInterface;

interface FileInterface extends PathInterface
{
    public function getFile(): string;
}
