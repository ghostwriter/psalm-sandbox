<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Path\Directory;

use RuntimeException;

final class VendorDirectory implements DirectoryInterface
{
    use DirectoryTrait;

    public function __construct(
        private readonly string $path,
    ) {
        if (! is_dir($this->path)) {
            throw new RuntimeException(sprintf('Directory "%s" does not exist', $this->path));
        }
    }
}
