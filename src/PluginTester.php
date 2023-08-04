<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use CallbackFilterIterator;
use Composer\InstalledVersions;
use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use FilesystemIterator;
use Generator;
use Ghostwriter\Container\Container;
use PHPUnit\Framework\Assert;
use Psalm\Internal\RuntimeCaches;
use SplFileInfo;

final class PluginTester
{
    private readonly Container $container;

    private bool $suppressProgress;

    public function __construct(
        private readonly VersionParser $versionParser = new VersionParser(),
    ) {
        set_time_limit(-1);
        // 8GiB Memory Limit
        ini_set('memory_limit', (string) (8 * 1024 * 1024 * 1024));
        // show all errors
        error_reporting(-1);
        ini_set('display_errors', '1');
        ini_set('display_startup_errors', '1');

        defined('PSALM_VERSION') || define('PSALM_VERSION', InstalledVersions::getPrettyVersion('vimeo/psalm'));
        defined('PHP_PARSER_VERSION') || define('PHP_PARSER_VERSION', InstalledVersions::getPrettyVersion('nikic/php-parser'));

        $this->container = Container::getInstance();
    }

    public function havePackageVersion(string $package, string $version, string $operator): bool
    {
        return Comparator::compare(
            $this->versionParser->normalize(
                InstalledVersions::getPrettyVersion($package)
            ),
            $operator,
            $this->versionParser->normalize($version)
        );
    }

    public function isPackageNewerThan(string $package, string $version): bool
    {
        return $this->havePackageVersion($package, '>', $version);
    }

    public function isPackageOlderThan(string $package, string $version): bool
    {
        return $this->havePackageVersion($package, '<', $version);
    }

    /**
     * $this->isPackageVersion("vendor/package", ">", "1.0.0", "to use new features").
     *
     * @return void
     */
    public function isPackageVersion(string $package, string $operator, string $version, string $reason)
    {
        $result = match (true) {
            $operator === '>' => $this->isPackageNewerThan($package, $version),
            $operator === '<' => $this->isPackageOlderThan($package, $version),
            default => Assert::fail(sprintf('Unknown operator: %s', $operator))
        };
        if (! $result) {
            Assert::fail(sprintf('This scenario requires %s %s %s because of %s', $package, $operator, $version, $reason));
        }
    }

    public function packageSatisfiesVersionConstraint(string $package, string $constraint): bool
    {
        if (! InstalledVersions::isInstalled($package)) {
            // Assert::fail(sprintf("Package %s is not installed", $package));
            return false;
        }

        if (! InstalledVersions::satisfies($this->versionParser, $package, $constraint)) {
            // Assert::fail(sprintf("Package '%s' (%s) is not installed", $package, $constraint));
            return false;
        }

        $currentVersion = $this->versionParser->normalize(
            InstalledVersions::getPrettyVersion($package)
        );

        if (str_starts_with($currentVersion, 'dev-')) {
            $currentVersion = '9999999-dev';
        }

        $result = Semver::satisfies($currentVersion, $constraint);

        return $result;
    }

    public function testPlugin(
        string $pluginClass,
        Fixture $fixture,
        string $phpVersion = Version::PHP_CURRENT_VERSION
    ): PluginTestResult {
        RuntimeCaches::clearAll();

        $analyzer = new Analyzer(
            $fixture,
            $pluginClass,
            $phpVersion
        );

        // ob_start();

        // $output = ob_get_clean();

        $analyzer->check($fixture->getSourceDirectory());

        return new PluginTestResult(
            $fixture,
            $analyzer,
            $pluginClass,
            $phpVersion
        );
    }

    /**
     * @psalm-return Generator<string, list{Fixture}, mixed, never>
     */
    public static function yieldFixture(string $path): Generator
    {
        if (! is_dir($path)) {
            Assert::fail(sprintf('Fixture directory "%s" does not exist', $path));
        }

        $levels = 0;
        $vendorDirectory = realpath($path . '/vendor');
        while ($vendorDirectory === false) {
            $vendorDirectory = realpath(dirname($path, ++$levels) . '/vendor');
        }

        if ($vendorDirectory === '') {
            Assert::fail('Could not find vendor directory');
        }

        $fixtureDirectory = new SplFileInfo($path);

        yield $fixtureDirectory->getBasename() => [
            new Fixture(
                $fixtureDirectory->getRealPath(),
                $vendorDirectory
            ),
        ];
    }

    /**
     * @psalm-return Generator<string, list{Fixture}, mixed, never>
     */
    public static function yieldFixtures(string $path): Generator
    {
        $levels = 0;

        $vendorDirectory = realpath($path . '/vendor');
        while ($vendorDirectory === false) {
            $vendorDirectory = realpath(dirname($path, ++$levels) . '/vendor');
        }

        if ($vendorDirectory === '') {
            Assert::fail('Could not find vendor directory');
        }

        foreach (new CallbackFilterIterator(
            new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS),
            static fn (SplFileInfo $current): bool => $current->isDir()
        ) as $fixtureDirectory
        ) {
            yield $fixtureDirectory->getBasename() => [
                new Fixture(
                    $fixtureDirectory->getRealPath(),
                    $vendorDirectory
                ),
            ];
        }
    }

    private static function findVendorDirectory(string $path): string
    {
        $vendorDirectory = realpath($path . '/vendor');
        if ($vendorDirectory !== false) {
            return $vendorDirectory;
        }

        do {
            $vendorDirectory = realpath(dirname($path) . '/vendor');
        } while ($vendorDirectory === false);

        if ($vendorDirectory === '') {
            Assert::fail('Could not find vendor directory');
        }

        return $vendorDirectory;
    }
}
