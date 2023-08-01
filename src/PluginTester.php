<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use CallbackFilterIterator;
use Composer\InstalledVersions;
use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use DirectoryIterator;
use Fiber;
use Generator;
use Ghostwriter\PsalmPluginTester\Path\Directory\Fixture;
use PHPUnit\Framework\Assert;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\FileProvider;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\PluginFileExtensionsInterface;
use Psalm\Plugin\PluginInterface;
use Psalm\Report;
use Psalm\Report\ReportOptions;
use ReflectionClass;
use SplFileInfo;
use Symfony\Component\Process\ExecutableFinder;
use Throwable;

final class PluginTester
{

    private bool $suppressProgress;

    private bool $useBaseline = false;

    private readonly string $vendorDirectory;
    private readonly string $composerBinDirectory;

    /**
     * @param class-string<PluginEntryPointInterface|PluginFileExtensionsInterface|PluginInterface> $pluginClass
     */
    public function __construct(
        private readonly VersionParser $versionParser = new VersionParser()
    ) {
        defined('PSALM_VERSION') || define('PSALM_VERSION', InstalledVersions::getPrettyVersion('vimeo/psalm'));
        defined('PHP_PARSER_VERSION') || define('PHP_PARSER_VERSION', InstalledVersions::getPrettyVersion('nikic/php-parser'));

        $this->suppressProgress = $this->packageSatisfiesVersionConstraint('vimeo/psalm', '>=3.4.0');

        $composerBinDirectory = $GLOBALS['_composer_bin_dir'] ?? null;

        if ($composerBinDirectory === null) {
            Assert::fail('Could not find composer bin directory from $_composer_bin_dir');
        }
        $this->composerBinDirectory = $composerBinDirectory;

        $vendorDirectory = realpath(
            dirname($composerBinDirectory)
        );

        if ($vendorDirectory === false) {
            Assert::fail('Could not find vendor directory');
        }

        $this->vendorDirectory = $vendorDirectory;
    }

    public function getPluginClass(): string
    {
        return $this->pluginClass;
    }

    public function getPsalmPath(): string
    {
        $psalm = (new ExecutableFinder())->find(
            'psalm',
            null,
            [$this->composerBinDirectory]
        );

        if ($psalm === null) {
            Assert::fail('Psalm is not installed.');
        }

        return $psalm;
    }

    public function havePackageVersion(string $package, string $version, string $operator)
    {
        return Comparator::compare(
            $this->versionParser->normalize(
                InstalledVersions::getPrettyVersion($package)
            ),
            $operator,
            $this->versionParser->normalize($version)
        );
    }

    public function isPackageNewerThan(string $package, string $version)
    {
        return $this->havePackageVersion($package, '>', $version);
    }

    public function isPackageOlderThan(string $package, string $version)
    {
        return $this->havePackageVersion($package, '<', $version);
    }

    /**
     * $this->isPackageVersion("vendor/package", ">", "1.0.0", "to use new features").
     *
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

    public function packageSatisfiesVersionConstraint(string $package, string $constraint)
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
        string $phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION
    ): PluginTestResult
    {
        $plugin = realpath((new ReflectionClass($pluginClass))->getFileName());
        if ($plugin === false) {
            Assert::fail(sprintf('Plugin class "%s" does not exist', $pluginClass));
        }

        RuntimeCaches::clearAll();

        try {
            $configuration = Config::loadFromXMLFile(
                $fixture->getPsalmConfig()->unwrap()->getFile(),
                $fixture->getPath()
            );
        } catch (Throwable $exception) {
            echo $exception->getMessage();
            exit(99);
        }

        $configuration->throw_exception = false;
        $configuration->use_docblock_types = true;
        $configuration->level = 1;
        $configuration->cache_directory = null;
        $configuration->setIncludeCollector(new IncludeCollector());

        $reportOptions = new ReportOptions();
        $reportOptions->use_color = false;
        $reportOptions->show_info = false;
        $reportOptions->format = Report::TYPE_JSON;
        $reportOptions->pretty = true;
        $reportOptions->output_path = './actual.json';

        $projectAnalyzer = new ProjectAnalyzer(
            $configuration,
            new Providers(new FileProvider()),
            $reportOptions,
        );

        return new PluginTestResult(
            $pluginClass,
            $plugin,
            $fixture,
            $projectAnalyzer,
        );
    }

    /**
     * @return Generator<string,Fixture>
     */
    public static function yieldFixtures(string $pluginClass, string $path): iterable
    {
        $fiber = new Fiber(
            static function (string $pluginClass, string $path) {
                /** @var SplFileInfo $fixtureDirectory */
                foreach (
                    new CallbackFilterIterator(
                        new DirectoryIterator($path),
                        static fn (
                            SplFileInfo $current
                        ): bool => $current->isDir() && ! $current->isDot()
                    ) as $fixtureDirectory) {
                    Fiber::suspend(
                        new Fixture($pluginClass, $fixtureDirectory->getRealPath())
                    );
                }
            }
        );

        $running = false;

        do {
            $running = $running || $fiber->isStarted();

            $fixture = $running ? $fiber->resume($path) : $fiber->start($pluginClass, $path);

            if (! $fixture instanceof Fixture) {
                continue;
            }

            yield from [
                $fixture->getName() => [$fixture],
            ];
        } while ($fiber->isSuspended());
    }
}
