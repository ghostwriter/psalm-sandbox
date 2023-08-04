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
use Psalm\Codebase;
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

        $configuration = self::configureConfig(
            Config::loadFromXML($fixture->getPath(), sprintf(
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
            )),
            $fixture
        );

        $reportOptions = self::configureReportOptions(new ReportOptions());

        $projectAnalyzer = new ProjectAnalyzer($configuration, new Providers($fixture), $reportOptions);
        self::configureProjectAnalyzer($projectAnalyzer, $phpVersion);

        $codebase = $projectAnalyzer->getCodebase();



        // $codebase = $projectAnalyzer->getCodebase();
        //        $codebase->config->initializePlugins($projectAnalyzer);
        //        $codebase->config->visitPreloadedStubFiles($codebase);
        //        $codebase->config->visitStubFiles($codebase);
        //        $codebase->config->visitComposerAutoloadFiles($projectAnalyzer);

        //        $codebase->allow_backwards_incompatible_changes = true;
        //        $projectAnalyzer->setPhpVersion($options['php-version']);
        //        Config::getInstance()->addPluginPath($current_dir . $plugin_path);
        gc_collect_cycles();
        gc_disable();
        $projectAnalyzer->checkDir($fixture->getPath());
        // $projectAnalyzer->check($fixture->getPath());
        gc_enable();
        gc_collect_cycles();

        return new PluginTestResult(
            $fixture,
            $projectAnalyzer,
        );
    }

    /**
     * @return Generator<string,Fixture>
     */
    public static function yieldFixture(string $path): Generator
    {
        if (! is_dir($path)) {
            Assert::fail(sprintf('Fixture directory "%s" does not exist', $path));
        }

        $fixtureDirectory = new SplFileInfo($path);

        yield $fixtureDirectory->getBasename() => [new Fixture($fixtureDirectory->getRealPath())];
    }

    /**
     * @return Generator<string,Fixture>
     */
    public static function yieldFixtures(string $path): Generator
    {
        foreach (new CallbackFilterIterator(
            new FilesystemIterator($path, FilesystemIterator::SKIP_DOTS),
            static fn (SplFileInfo $current): bool => $current->isDir()
        ) as $fixtureDirectory) {
            yield $fixtureDirectory->getBasename() => [new Fixture($fixtureDirectory->getRealPath())];
        }
    }

    private static function configureCodebase(
        Codebase $codebase
    ): void {
        // $codebase->enterServerMode();
        $codebase->collectLocations();
        // $codebase->reportUnusedVariables();
        // $codebase->reportUnusedCode();
        $codebase->store_node_types = true;
    }

    private static function configureConfig(
        Config $configuration,
        Fixture $fixture
    ): Config {
        $vendorDirectory = self::findVendorDirectory(__DIR__);
        $configuration->setComposerClassLoader(
            require $vendorDirectory . '/autoload.php'
        );

        $configuration->setIncludeCollector(new IncludeCollector());
        $configuration->collectPredefinedConstants();
        $configuration->collectPredefinedFunctions();

        $configuration->check_for_throws_docblock = true;
        $configuration->ensure_array_int_offsets_exist = true;
        $configuration->ensure_array_string_offsets_exist = true;
        $configuration->infer_property_types_from_constructor = true;
        $configuration->ignore_internal_falsable_issues = true;
        $configuration->ignore_internal_nullable_issues = true;
        $configuration->level = 1;
        $configuration->memoize_method_calls = true;
        $configuration->remember_property_assignments_after_call = true;
        $configuration->show_mixed_issues = false;
        $configuration->throw_exception = false;
        $configuration->use_docblock_types = true;
        $configuration->include_php_versions_in_error_baseline = true;
        $configuration->use_phpdoc_method_without_magic_or_parent = false;
        $configuration->use_phpdoc_property_without_magic_or_parent = false;

        foreach ($configuration->php_extensions as &$enabled) {
            $enabled = true;
        }

        // $configuration->allow_includes = false;
        $configuration->base_dir = $fixture->getPath();
        $configuration->cache_directory = null;
        $configuration->global_cache_directory = null;


        $configuration->find_unused_baseline_entry = true;
        // $configuration->find_unused_code = true;
        // $configuration->find_unused_variables = true;
        $configuration->find_unused_psalm_suppress = true;

        return $configuration;
    }

    private static function configureProjectAnalyzer(
        ProjectAnalyzer $projectAnalyzer,
        string $phpVersion = Version::PHP_CURRENT_VERSION
    ): void {
        $projectAnalyzer->setPhpVersion($phpVersion, 'tests');
        $projectAnalyzer->trackTaintedInputs();
        $projectAnalyzer->trackUnusedSuppressions();

        $codebase = $projectAnalyzer->getCodebase();

        self::configureCodebase($codebase);

        $projectAnalyzer->show_issues = true;
    }

    private static function configureReportOptions(
        ReportOptions $reportOptions
    ): ReportOptions {
        $reportOptions->in_ci = false;
        $reportOptions->use_color = false;
        $reportOptions->show_info = ! false;
        $reportOptions->format = Report::TYPE_JSON;
        $reportOptions->pretty = true;
        $reportOptions->output_path = './actual.json';

        return $reportOptions;
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

    private static function createReportOptions(): ReportOptions
    {
        static $reportOptions = null;

        if ($reportOptions !== null) {
            return $reportOptions;
        }

        $reportOptions = new ReportOptions();
        $reportOptions->in_ci = false;
        $reportOptions->use_color = false;
        $reportOptions->show_info = true;
        $reportOptions->format = Report::TYPE_JSON;
        $reportOptions->pretty = true;
        $reportOptions->output_path = './actual.json';

        return $reportOptions;
    }
}
