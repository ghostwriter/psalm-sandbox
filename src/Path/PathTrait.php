<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Path;

trait PathTrait
{
    public function __toString(): string
    {
        return $this->path;
    }

    public function exists(): bool
    {
        return file_exists($this->path);
    }
}
