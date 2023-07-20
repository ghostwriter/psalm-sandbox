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

    private const DEFAULT_PSALM_AUTOLOADER = <<<'AUTOLOADER'
<?php

use Composer\Autoload\ClassLoader;

/** @var ClassLoader $autoloader */
$autoloader = require '%sautoload.php';
if (!$autoloader instanceof ClassLoader) {
    throw new RuntimeException('Autoloader not found');
}

$autoloader->add('', __DIR__);
AUTOLOADER . PHP_EOL;

    private const DEFAULT_PSALM_CONFIG = <<<'XML'
<?xml version="1.0"?>
<psalm errorLevel="1" autoloader="autoload.php">
    <projectFiles>
        <directory name="%s" />
        <ignoreFiles>
            <directory name="%s" />
        </ignoreFiles>
    </projectFiles>
    <plugins>
        <pluginClass class="%s" />
    </plugins>
</psalm>
XML . PHP_EOL;

    private readonly string $path;

    public function __construct(
        private readonly Fixture $fixture,
    ) {
        $this->path = $this->fixture->getPath();
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

        $vendorDirectory = getcwd() . DIRECTORY_SEPARATOR . 'vendor';

        if (! file_exists($vendorDirectory . DIRECTORY_SEPARATOR . 'autoload.php')) {
            $vendorDirectory = dirname(__FILE__, 2) . DIRECTORY_SEPARATOR . 'vendor';
        }

        if (! file_exists($vendorDirectory . DIRECTORY_SEPARATOR . 'autoload.php')) {
            Assert::fail(sprintf('Vendor directory "%s" does not exist', $vendorDirectory));
        }

        $getRelativePath = $this->getRelativePath($psalmConfig, realpath($this->path));

        $getRelativeVendorDirectory = $this->getRelativePath(realpath($this->path), realpath($vendorDirectory));

        $result = file_put_contents($psalmConfig, sprintf(
            self::DEFAULT_PSALM_CONFIG,
            $getRelativePath,
            $getRelativeVendorDirectory,
            $this->fixture->pluginClass()
        ));

        if ($result === false) {
            Assert::fail(sprintf('Could not write psalm config file: "%s"', $psalmConfig));
        }

        $autoloadFile = $this->path . '/autoload.php';
        $result = file_put_contents($autoloadFile, sprintf(
            self::DEFAULT_PSALM_AUTOLOADER,
            $getRelativeVendorDirectory
        ));
        if ($result === false) {
            Assert::fail(sprintf('Could not write autoload file: "%s"', $autoloadFile));
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

    private function getRelativePath(string $from, string $to): string
    {
        $fromParts = explode(DIRECTORY_SEPARATOR, rtrim($from, DIRECTORY_SEPARATOR));
        $toParts = explode(DIRECTORY_SEPARATOR, rtrim($to, DIRECTORY_SEPARATOR));

        while (count($fromParts) > 0 && count($toParts) > 0 && $fromParts[0] === $toParts[0]) {
            array_shift($fromParts);
            array_shift($toParts);
        }

        $numDirsUp = count($fromParts);

        $relativePath = str_repeat(match ($numDirsUp) {
            1 => '.' . DIRECTORY_SEPARATOR,
            default => '..' . DIRECTORY_SEPARATOR
        }, $numDirsUp) . implode(DIRECTORY_SEPARATOR, $toParts);

        return is_dir($to) ? rtrim($relativePath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR : $relativePath;
    }
}
