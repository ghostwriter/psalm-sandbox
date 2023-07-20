<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester;

use Composer\InstalledVersions;
use Composer\Semver\Comparator;
use Composer\Semver\Semver;
use Composer\Semver\VersionParser;
use FilesystemIterator;
use Generator;
use Ghostwriter\PsalmPluginTester\Path\Directory\Fixture;
use Psalm\Plugin\PluginEntryPointInterface;
use Psalm\Plugin\PluginFileExtensionsInterface;
use Psalm\Plugin\PluginInterface;
use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use ReflectionClass;
use RuntimeException;
use SplFileInfo;
use Symfony\Component\Process\ExecutableFinder;

final class PluginTester
{
    private bool $suppressProgress;
    private readonly string $plugin;

    /**
     * @param class-string<PluginEntryPointInterface|PluginFileExtensionsInterface|PluginInterface> $pluginClass
     */
    public function __construct(
        private readonly string $pluginClass,
        private readonly Shell $shell = new Shell(),
        private readonly VersionParser $versionParser = new VersionParser()
    ) {
        $this->suppressProgress = $this->packageSatisfiesVersionConstraint('vimeo/psalm', '>=3.4.0');

        $plugin = (new ReflectionClass($this->pluginClass))->getFileName();

        if ($plugin === false) {
            throw new RuntimeException(sprintf('Plugin class "%s" does not exist', $this->pluginClass));
        }

        $this->plugin = $plugin;
      }
    public function getPluginClass(): string
    {
        return $this->pluginClass;
    }

    public function getPsalmPath(): string
    {
        $psalm = (new ExecutableFinder())->find(
            'psalm',
            './../../../vendor/bin/psalm'
        );

        if ($psalm === null) {
            throw new RuntimeException('Psalm is not installed.');
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
            default => throw new RuntimeException(sprintf('Unknown operator: %s', $operator))
        };
        if (! $result) {
            throw new RuntimeException(sprintf('This scenario requires %s %s %s because of %s', $package, $operator, $version, $reason));
        }
    }

    public function packageSatisfiesVersionConstraint(string $package, string $constraint)
    {
        if (! InstalledVersions::isInstalled($package)) {
            return false;
        }

        if (! InstalledVersions::satisfies($this->versionParser, $package, $constraint)) {
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

    public function test(Fixture $fixture): PluginTestResult
    {
        return (new PluginTestResult(
            $this->pluginClass,
            $fixture,
            $this->shell->execute(
                $this->getPsalmPath(),
                [
                    '--output-format=json',
                    '--no-cache',
                    '--no-diff',
                    ...$this->suppressProgress ? ['--no-progress'] : [],
                    '--plugin=' . $this->plugin,
                ],
                $fixture->getProjectRootDirectory()->getDirectory()
            )
        ))->assertExpectations();
    }
    /**
     * @param string $path
     * @return Generator<string,Fixture>
     */
    public static function yieldFixtures(string $path): Generator
    {
        /** @var SplFileInfo $fixtureDirectory */
        foreach (new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS),
            static fn (SplFileInfo $current): bool => $current->isDir()
        ) as $fixtureDirectory) {
            $fixture = new Fixture($fixtureDirectory->getPathname());

            yield $fixture->getName() => [$fixture];
        }
    }
}
