<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Path\File;

use Ghostwriter\PsalmPluginTester\Path\PathTrait;

trait FileTrait
{
    use PathTrait;

    public function __construct(
        private readonly string $path,
    ) {
        if (! is_file($this->path)) {
            throw new RuntimeException(sprintf('File "%s" does not exist', $this->file));
        }
    }

    public function getFile(): string
    {
        return $this->path;
    }
}
