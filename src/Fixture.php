<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use PHPUnit\Framework\Assert;
use Psalm\Internal\Provider\FakeFileProvider;

use function basename;
use function file_get_contents;

final class Fixture extends FakeFileProvider
{
    private readonly string $name;

    public function __construct(
        private readonly string $sourceDirectory,
        private readonly string $vendorDirectory,
    ) {
        foreach ($this->getFilesInDir($sourceDirectory, ['php']) as $fixtureFile) {
            $contents = file_get_contents($fixtureFile);
            if ($contents === false) {
                Assert::fail('Could not read fixture file: ' . $fixtureFile);
            }

            $this->registerFile($fixtureFile, $contents);
        }

        $this->name = basename($sourceDirectory);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSourceDirectory(): string
    {
        return $this->sourceDirectory;
    }

    public function getVendorDirectory(): string
    {
        return $this->vendorDirectory;
    }
}
