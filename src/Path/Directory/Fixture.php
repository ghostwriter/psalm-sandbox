<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Path\Directory;

use Ghostwriter\Option\None;
use Ghostwriter\Option\OptionInterface;
use Ghostwriter\Option\Some;
use Ghostwriter\PsalmPluginTester\Path\File\ComposerJsonFile;
use Ghostwriter\PsalmPluginTester\Path\File\ComposerLockFile;
use RuntimeException;
use Throwable;

final class Fixture
{
    public function __construct(
        private readonly string $path,
    ) {
    }

    public function getComposerJson(): ComposerJsonFile
    {
        return $this->getProjectRootDirectory()
            ->getComposerJson()
            ->unwrapOrElse(
                fn () => throw new RuntimeException(sprintf('composer.json not found in "%s"', $this->path))
            );
    }

    public function getComposerLock(): ComposerLockFile
    {
        return $this->getProjectRootDirectory()
            ->getComposerLock()
            ->unwrapOrElse(
                fn () => throw new RuntimeException(sprintf('composer.lock not found in "%s"', $this->path))
            );
    }

    public function getName(): string
    {
        return basename($this->path);
    }

    public function getPhpVersion(): string
    {
        return $this->getPsalmConfig()
            ->getPhpVersion()
            ->or($this->getComposerLock() ->getPhpVersion() ->or($this->getComposerJson() ->getPhpVersion()))
            ->unwrapOrElse(
                fn () => throw new RuntimeException(sprintf('No "php" version found in "%s"', $this->path))
            );
    }

    public function getProjectRootDirectory(): ProjectRootDirectory
    {
        return new ProjectRootDirectory($this->path);
    }

    public function getPsalmConfig(): OptionInterface
    {
        return $this->getProjectRootDirectory()->getPsalmConfigurationFile();
    }

    public function getPsalmVersion(): string
    {
        return None::create()
            ->or($this->getComposerLock()->getPsalmVersion())
            ->or($this->getComposerJson()->getPsalmVersion())
            ->unwrapOrElse(
                fn () => throw new RuntimeException(sprintf('No "psalm" version found in "%s"', $this->path))
            );
    }

    public function getPsalmVersionFromComposerJson(): string
    {
        return '';
    }

    public function getPsalmVersionFromComposerLock(): string
    {
        return '';
    }

    public function getPsalmVersionFromPsalmConfig(): string
    {
        return '';
    }

    /**
     * @return OptionInterface<VendorDirectory>
     */
    public function getVendorDirectory(): OptionInterface
    {
        if (! is_dir($this->path . '/vendor')) {
            return None::create();
        }

        return Some::create(new VendorDirectory($this->path . '/vendor'));
    }
}
