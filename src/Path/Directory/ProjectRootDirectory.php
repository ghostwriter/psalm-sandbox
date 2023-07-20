<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Path\Directory;

use Composer\InstalledVersions;
use Ghostwriter\Option\None;
use Ghostwriter\Option\OptionInterface;
use Ghostwriter\PsalmPluginTester\Path\File\ComposerJsonFile;
use Ghostwriter\PsalmPluginTester\Path\File\ComposerLockFile;
use Ghostwriter\PsalmPluginTester\Path\File\ExpectationsJsonFile;
use Ghostwriter\PsalmPluginTester\Path\File\PsalmConfig;
use Ghostwriter\PsalmPluginTester\PsalmExpectation;
use Ghostwriter\PsalmPluginTester\Version\PsalmVersion;
use RuntimeException;

final class ProjectRootDirectory implements DirectoryInterface
{
    use DirectoryTrait;

    public function __construct(
        private readonly string $path,
    ) {
        if (! is_dir($this->path)) {
            throw new RuntimeException(sprintf('Directory "%s" does not exist', $this->path));
        }
    }

    /**
     * @return OptionInterface<ComposerJsonFile>
     */
    public function getComposerJson(): OptionInterface
    {
        $option = None::create();

        $composerJson = $this->path . '/composer.json';
        if (! file_exists($composerJson)) {
            return $option;
        }

        return $option->orElse(static fn (): ComposerJsonFile => new ComposerJsonFile($composerJson));
    }

    /**
     * @return OptionInterface<ComposerLockFile>
     */
    public function getComposerLock(): OptionInterface
    {
        $option = None::create();

        $composerLock = $this->path . '/composer.lock';
        if (! file_exists($composerLock)) {
            return $option;
        }

        return $option->orElse(
            static fn (): ComposerLockFile => new ComposerLockFile($composerLock)
        );
    }

    public function getDirectory(): string
    {
        return $this->path;
    }

    /**
     * @return OptionInterface<PsalmExpectation>
     */
    public function getExpectationsJsonFile(): OptionInterface
    {
        $option = None::create();

        $expectations = $this->path . '/expectations.json';
        if (! file_exists($expectations)) {
            return $option;
        }

        return $option->orElse(
            static fn (): ExpectationsJsonFile => new ExpectationsJsonFile($expectations)
        );
    }

    /**
     * @return OptionInterface<PsalmConfig>
     */
    public function getPsalmConfigurationFile(): OptionInterface
    {
        $option = None::create();

        $psalmConfig = $this->path . '/psalm.xml';
        if (file_exists($psalmConfig)) {
            return $option->orElse(
                static fn (): PsalmConfig => new PsalmConfig($psalmConfig)
            );
        }

        $psalmConfigDist = $this->path . '/psalm.xml.dist';
        if (! file_exists($psalmConfigDist)) {
            return $option;
        }

        return $option->orElse(
            static fn (): PsalmConfig => new PsalmConfig($psalmConfigDist)
        );
    }

    /**
     * @return OptionInterface<PsalmVersion>
     */
    public function getPsalmVersion(): OptionInterface
    {
        return None::create()
            ->or($this->getComposerLock()->getPsalmVersion())
            ->or($this->getComposerJson()->getPsalmVersion())
            ->orElse(static fn () => InstalledVersions::getVersion('vimeo/psalm'))
            ->unwrapOrElse(
                fn () => throw new RuntimeException(sprintf(
                    'Could not determine psalm version from composer.json or composer.lock in "%s"',
                    $this->path
                ))
            );
    }
}
