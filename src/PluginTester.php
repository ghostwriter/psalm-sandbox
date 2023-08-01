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
use Ghostwriter\Container\Container;
use PHPUnit\Framework\Assert;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\Providers;
use Psalm\Internal\RuntimeCaches;
use Psalm\Report;
use Psalm\Report\ReportOptions;
use ReflectionClass;
use SplFileInfo;

final class PluginTester
{
    private readonly Container $container;

    private bool $suppressProgress;

    public function __construct(
        private readonly VersionParser $versionParser = new VersionParser(),
    ) {
        defined('PSALM_VERSION') || define('PSALM_VERSION', InstalledVersions::getPrettyVersion('vimeo/psalm'));

        defined('PHP_PARSER_VERSION') || define('PHP_PARSER_VERSION', InstalledVersions::getPrettyVersion('nikic/php-parser'));

        $this->container = Container::getInstance();

        $this->suppressProgress = $this->packageSatisfiesVersionConstraint('vimeo/psalm', '>=3.4.0');

        //        var_dump($GLOBALS, $_composer_bin_dir);
        //        $composerBinDirectory = $GLOBALS['_composer_bin_dir'] ?? getenv('COMPOSER_BIN_DIR') ?: __DIR__;
        //
        //        if ($composerBinDirectory === null) {
        //            Assert::fail('Could not find composer bin directory from $_composer_bin_dir');
        //        }
        //        $this->composerBinDirectory = $composerBinDirectory;
        //
        //        $vendorDirectory = realpath(
        //            dirname($composerBinDirectory)
        //        );
        //
        //        if ($vendorDirectory === false) {
        //            Assert::fail('Could not find vendor directory');
        //        }
        //
        //        $this->vendorDirectory = $vendorDirectory;
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
        string $phpVersion = Version::PHP_CURRENT_VERSION
    ): PluginTestResult {
        $plugin = realpath((new ReflectionClass($pluginClass))->getFileName());
        if ($plugin === false) {
            Assert::fail(sprintf('Plugin class "%s" does not exist', $pluginClass));
        }

        RuntimeCaches::clearAll();

        $configuration = Config::loadFromXML(
            $fixture->getPath(),
            sprintf(
                <<<'PSALM_CONFIG'
<?xml version="1.0"?>
<psalm errorLevel="1" phpVersion="%s">
    <projectFiles>
        <directory name="./" />
    </projectFiles>
    <plugins>
        <pluginClass class="%s" />
    </plugins>
</psalm>
PSALM_CONFIG,
                $phpVersion,
                $pluginClass
            )
        );

        $configuration->collectPredefinedConstants();
        $configuration->collectPredefinedFunctions();

        $configuration->allow_includes = false;
        $configuration->base_dir = $fixture->getPath();
        $configuration->cache_directory = null;
        $configuration->check_for_throws_docblock = true;
        $configuration->ensure_array_int_offsets_exist = true;
        $configuration->ensure_array_string_offsets_exist = true;
        $configuration->ignore_internal_falsable_issues = true;
        $configuration->ignore_internal_nullable_issues = true;
        $configuration->level = 1;
        $configuration->memoize_method_calls = false;
        $configuration->remember_property_assignments_after_call = true;
        $configuration->setIncludeCollector(new IncludeCollector());
        $configuration->show_mixed_issues = true;
        $configuration->throw_exception = false;
        $configuration->use_docblock_types = true;
        $configuration->use_phpdoc_method_without_magic_or_parent = false;
        $configuration->use_phpdoc_property_without_magic_or_parent = false;

        foreach ($configuration->php_extensions as &$enabled) {
            $enabled = true;
        }

        $reportOptions = new ReportOptions();
        $reportOptions->use_color = false;
        $reportOptions->show_info = false;
        $reportOptions->format = Report::TYPE_JSON;
        $reportOptions->pretty = true;
        $reportOptions->output_path = './actual.json';

        $projectAnalyzer = new ProjectAnalyzer(
            $configuration,
            new Providers(
                $fixture
            ),
            $reportOptions,
        );

        $projectAnalyzer->setPhpVersion($phpVersion, 'cli');

        $codebase = $projectAnalyzer->getCodebase();
        $codebase->config->initializePlugins($projectAnalyzer);
        $codebase->collect_references = true;

        $configuration->visitPreloadedStubFiles($codebase);

        $codebase->store_node_types = true;

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
    public static function yieldFixtures(string $path): Generator
    {
        foreach (
            new CallbackFilterIterator(
                new DirectoryIterator($path),
                static fn (
                    SplFileInfo $current
                ): bool => $current->isDir() && ! $current->isDot()
            ) as $fixtureDirectory) {
            if (! $fixtureDirectory instanceof SplFileInfo) {
                continue;
            }
            yield from [
                $fixtureDirectory->getBasename() => [new Fixture($fixtureDirectory->getRealPath())],
            ];
        }
    }
}
