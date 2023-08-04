<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use PHPUnit\Framework\Assert;
use Psalm\Internal\Provider\FakeFileProvider;

final class Fixture extends FakeFileProvider
{
    public function __construct(
        private readonly string $projectDirectory,
        private readonly string $vendorDirectory,
    ) {
        foreach ($this->getFilesInDir($projectDirectory, ['php']) as $fixtureFile) {
            $contents = file_get_contents($fixtureFile);
            if ($contents === false) {
                Assert::fail('Could not read fixture file: ' . $fixtureFile);
            }

            $this->registerFile($fixtureFile, $contents);
        }
    }

    public function getProjectDirectory(): string
    {
        return $this->projectDirectory;
    }

    public function getVendorDirectory(): string
    {
        return $this->vendorDirectory;
    }
}
