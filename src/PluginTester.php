<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use CallbackFilterIterator;
use Composer\InstalledVersions;
use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use DirectoryIterator;
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
    private readonly string $plugin;

    private bool $suppressProgress;

    private bool $useBaseline = false;

    private readonly string $vendorDirectory;

    /**
     * @param class-string<PluginEntryPointInterface|PluginFileExtensionsInterface|PluginInterface> $pluginClass
     */
    public function __construct(
        private readonly string $pluginClass,
        private readonly Shell $shell = new Shell(),
        private readonly VersionParser $versionParser = new VersionParser()
    ) {
        $this->suppressProgress = $this->packageSatisfiesVersionConstraint('vimeo/psalm', '>=3.4.0');

        $plugin = realpath((new ReflectionClass($this->pluginClass))->getFileName());

        if ($plugin === false) {
            Assert::fail(sprintf('Plugin class "%s" does not exist', $this->pluginClass));
        }

        $this->plugin = $plugin;
        $this->vendorDirectory = dirname($this->getPsalmPath());
    }

    public function getPluginClass(): string
    {
        return $this->pluginClass;
    }

    public function getPsalmPath(): string
    {
        $psalm = (new ExecutableFinder())->find(
            'vendor/bin/psalm',
            null,
            [
                dirname(__DIR__) . DIRECTORY_SEPARATOR, // installed as a project
                dirname(__DIR__, 4) . DIRECTORY_SEPARATOR, // installed as a dependency
            ]
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

        $currentVersion =  $this->versionParser->normalize(
            InstalledVersions::getPrettyVersion($package)
        );

        if (str_starts_with($currentVersion, 'dev-')) {
            $currentVersion = '9999999-dev';
        }

        $result = Semver::satisfies($currentVersion, $constraint);

        return $result;
    }

    public function test(Fixture $fixture, string $phpVersion = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION): PluginTestResult
    {
        RuntimeCaches::clearAll();

        defined('PSALM_VERSION') || define('PSALM_VERSION', InstalledVersions::getPrettyVersion('vimeo/psalm'));
        defined('PHP_PARSER_VERSION') || define('PHP_PARSER_VERSION', InstalledVersions::getPrettyVersion('nikic/php-parser'));

        //        $fixtureRootDirectory = $fixture->getPath();

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

        return (new PluginTestResult(
            $this->pluginClass,
            $this->plugin,
            $fixture,
            $projectAnalyzer,
        ))->assertExpectations();
    }

    //    test plugin with psalm config
    //    test plugin with psalm config and psalm version
    //    test plugin with psalm config and php version [7.2 - 8.3]
    /**
     * @return Generator<string,Fixture>
     */
    public static function yieldFixtures(string $pluginClass, string $path): Generator
    {
        /** @var SplFileInfo $fixtureDirectory */
        foreach (new CallbackFilterIterator(
            new DirectoryIterator($path),
            static fn (SplFileInfo $current): bool => $current->isDir() && ! $current->isDot()
        ) as $fixtureDirectory) {
            $fixture = new Fixture($pluginClass, $fixtureDirectory->getPathname());

            yield $fixture->getName() => [$fixture];
        }
    }
}
