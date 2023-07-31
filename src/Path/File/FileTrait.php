<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Path\File;

use Ghostwriter\PsalmPluginTester\Path\PathTrait;

trait FileTrait
{
    use PathTrait;

    public function getFile(): string
    {
        return $this->path;
    }
}
