<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use PHPUnit\Framework\Assert;
use Psalm\Codebase;
use Psalm\Config;
use Psalm\Internal\Analyzer\ProjectAnalyzer;
use Psalm\Internal\IncludeCollector;
use Psalm\Internal\Provider\Providers;
use Psalm\Progress\DebugProgress;
use Psalm\Progress\Progress;
use Psalm\Progress\VoidProgress;
use Psalm\Report;
use Psalm\Report\ReportOptions;
use ReflectionClass;

final class Analyzer extends ProjectAnalyzer
{
    public function __construct(
        private readonly Fixture $fixture,
        private readonly string $pluginClass,
        private readonly string $phpVersion
    ) {
        $debug = false;
        $config = self::makeConfig($this->fixture, $this->pluginClass, $this->phpVersion);
        $providers = new Providers($this->fixture);
        $progress = $debug ? new DebugProgress() : new VoidProgress();
        $codebase = self::makeCodebase($config, $providers, $progress);
        parent::__construct(
            $config,
            $providers,
            self::makeReportOptions(),
            [],
            10,
            $progress,
            $codebase
        );
        $this->show_issues = true;
        $this->setPhpVersion($this->phpVersion, 'tests');
    }

    private static function makeCodebase(
        Config $config,
        Providers $providers,
        Progress $progress
    ): Codebase {
        $codebase = new Codebase($config, $providers, $progress);

        $codebase->enterServerMode();
        $codebase->collectLocations();
        $codebase->store_node_types = true;
        $codebase->register_autoload_files = true;
        $codebase->track_unused_suppressions = true;
        $codebase->infer_types_from_usage = true;
        $codebase->alter_code = false;
        $codebase->collect_references = true;
        $codebase->collect_locations = true;
        $codebase->find_unused_code = true;
        $codebase->find_unused_variables = true;
        $codebase->reportUnusedCode('always');

        return $codebase;
    }

    private static function makeConfig(
        Fixture $fixture,
        string $pluginClass,
        string $phpVersion
    ): Config {
        static $configurations = [];

        $sourceDirectory = $fixture->getSourceDirectory();

        if (array_key_exists($sourceDirectory, $configurations)) {
            return $configurations[$phpVersion][$sourceDirectory];
        }

        $vendorDirectory = $fixture->getVendorDirectory();

        $configuration = Config::loadFromXML(
            $fixture->getSourceDirectory(),
            sprintf(
                <<<'PSALM_CONFIG'
                <?xml version="1.0"?>
                <psalm errorLevel="1" phpVersion="%s">
                    <projectFiles>
                        <directory name="%s" />
                        <ignoreFiles>
                            <directory name="%s" />
                        </ignoreFiles>
                    </projectFiles>
                </psalm>
                PSALM_CONFIG,
                $phpVersion,
                $sourceDirectory,
                $vendorDirectory
            )
        );

        $autoload = $vendorDirectory . '/autoload.php';
        if (file_exists($autoload)) {
            $configuration->setComposerClassLoader(require $autoload);
        }

        // add the pulgin to the config
        $plugin = realpath((new ReflectionClass($pluginClass))->getFileName());
        if ($plugin === false) {
            Assert::fail(sprintf('Plugin class "%s" does not exist', $pluginClass));
        }

        // $configuration->addPluginPath($plugin);
        // $configuration->plugin_paths = [$plugin];
        $configuration->addPluginClass($pluginClass);
        $configuration->setIncludeCollector(new IncludeCollector());

        // $configuration->collectPredefinedConstants();
        // $configuration->collectPredefinedFunctions();

        $configuration->allow_includes = false;
        $configuration->run_taint_analysis = false;
        $configuration->ignore_internal_falsable_issues = false;
        $configuration->ignore_internal_nullable_issues = false;
        $configuration->base_dir = $fixture->getSourceDirectory();
        $configuration->resolve_from_config_file = true;
        $configuration->cache_directory = null;
        $configuration->global_cache_directory = null;
        $configuration->use_docblock_types = true;
        $configuration->use_docblock_property_types = true;
        $configuration->disable_suppress_all = true;
        $configuration->check_for_throws_docblock = true;
        $configuration->check_for_throws_in_global_scope = true;
        $configuration->find_unused_baseline_entry = true;
        $configuration->find_unused_code = true;
        $configuration->find_unused_variables = true;
        $configuration->find_unused_psalm_suppress = true;
        $configuration->restrict_return_types = true;
        $configuration->check_for_throws_docblock = true;
        $configuration->ensure_array_int_offsets_exist = true;
        $configuration->ensure_array_string_offsets_exist = true;
        $configuration->infer_property_types_from_constructor = true;
        $configuration->ignore_internal_falsable_issues = true;
        $configuration->ignore_internal_nullable_issues = true;
        $configuration->use_phpstorm_meta_path = true;
        $configuration->strict_binary_operands = true;
        $configuration->seal_all_methods = true;
        $configuration->seal_all_properties = true;
        $configuration->ensure_array_string_offsets_exist = true;
        $configuration->ensure_array_int_offsets_exist = true;
        $configuration->include_php_versions_in_error_baseline = true;

        $configuration->level = 1;
        $configuration->memoize_method_calls = true;
        $configuration->remember_property_assignments_after_call = true;
        $configuration->show_mixed_issues = true;
        $configuration->throw_exception = false;
        $configuration->use_docblock_types = true;
        $configuration->include_php_versions_in_error_baseline = true;
        $configuration->use_phpdoc_method_without_magic_or_parent = false;
        $configuration->use_phpdoc_property_without_magic_or_parent = false;

        foreach ($configuration->php_extensions as &$enabled) {
            $enabled = true;
        }

        return $configurations[$phpVersion][$sourceDirectory] = $configuration;
    }

    private static function makeReportOptions(): ReportOptions
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
        $reportOptions->output_path = null;

        return $reportOptions;
    }
}
