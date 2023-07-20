<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Path\Directory;

use Composer\InstalledVersions;
use Ghostwriter\Option\None;
use Ghostwriter\Option\OptionInterface;
use Ghostwriter\PsalmPluginTester\Path\File\ComposerJsonFile;
use Ghostwriter\PsalmPluginTester\Path\File\ComposerLockFile;
use Ghostwriter\PsalmPluginTester\Path\File\ExpectationsJsonFile;
use Ghostwriter\PsalmPluginTester\Path\File\PsalmXmlFile;
use Ghostwriter\PsalmPluginTester\PsalmExpectation;
use Ghostwriter\PsalmPluginTester\Version\PsalmVersion;
use PHPUnit\Framework\Assert;
use RuntimeException;

final class ProjectRootDirectory implements DirectoryInterface
{
    use DirectoryTrait;

    private const DEFAULT_PSALM_CONFIG = <<<XML
<?xml version="1.0"?>
<psalm errorLevel="1">
    <projectFiles>
        <directory name="." />
        <ignoreFiles>
            <directory name="./../" />
        </ignoreFiles>
    </projectFiles>
    <plugins>
        <pluginClass class="Psalm\PhpUnitPlugin\Plugin" />
    </plugins>
</psalm>
XML;

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
     * @return OptionInterface<PsalmXmlFile>
     */
    public function getPsalmConfigurationFile(): OptionInterface
    {
        $option = None::create();

        $psalmConfig = $this->path . '/psalm.xml.dist';
        if (file_exists($psalmConfig)) {
            return $option->orElse(
                static fn (): PsalmXmlFile => new PsalmXmlFile($psalmConfig)
            );
        }

        $psalmConfig = $this->path . '/psalm.xml';
        if (file_exists($psalmConfig)) {
            return $option->orElse(
                static fn (): PsalmXmlFile => new PsalmXmlFile($psalmConfig)
            );
        }

        $psalmConfig = tempnam(sys_get_temp_dir(), basename($this->path));

        $vendorDirectory = realpath(dirname(__FILE__, 4) . '/vendor');
        if($vendorDirectory === false) {
            Assert::fail(sprintf('Could not find vendor directory: "%s"', $vendorDirectory));
        }


        $result = file_put_contents($psalmConfig, sprintf(self::DEFAULT_PSALM_CONFIG, $vendorDirectory));
        if ($result === false) {
            Assert::fail(sprintf('Could not write psalm config file: "%s"', $psalmConfig));
        }

        return $option->orElse(
            static fn (): PsalmXmlFile => new PsalmXmlFile($psalmConfig)
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
