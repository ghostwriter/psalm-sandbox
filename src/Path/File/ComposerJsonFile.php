<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Path\File;

use Composer\InstalledVersions;
use Ghostwriter\Json\Json;
use Ghostwriter\Option\None;
use Ghostwriter\Option\OptionInterface;
use Ghostwriter\Option\Some;
use Ghostwriter\PsalmPluginTester\Version\PhpVersion;
use Ghostwriter\PsalmPluginTester\Version\PsalmVersion;
use RuntimeException;

final class ComposerJsonFile implements FileInterface
{
    use FileTrait;

    public function __construct(
        private readonly string $path,
    ) {
        if (! is_file($this->path)) {
            throw new RuntimeException(sprintf('File "%s" does not exist', $this->path));
        }
    }

    //    public function getPhpVersion(): PhpVersionInterface;
    //
    //    public function getPsalmVersion(): PsalmVersionInterface;

    /**
     * @return OptionInterface<PhpVersion>
     */
    public function getPhpVersion(): OptionInterface
    {
        $composerLockContents = file_get_contents($this->path);

        if ($composerLockContents === false) {
            throw new RuntimeException(sprintf('Could not read composer json file: "%s"', $this->path));
        }

        $composerLockData = $this->read();

        $platform = $composerLockData['platform'] ?? [];

        $phpVersion = $platform['php'] ?? null;

        if (is_string($phpVersion)) {
            return Some::create(new PhpVersion($phpVersion));
        }

        return None::create();
    }

    /**
     * @return OptionInterface<PsalmVersion>
     */
    public function getPsalmVersion(): OptionInterface
    {
        if (! InstalledVersions::isInstalled('vimeo/psalm')) {
            return None::create();
        }

        $psalmVersion = InstalledVersions::getVersion('vimeo/psalm');

        if (is_string($psalmVersion)) {
            return Some::create(new PsalmVersion($psalmVersion));
        }

        return None::create();
    }

    /**
     * @return array{
     *     'errors': array{'file': string, 'line': int, 'type': string, 'message': string},
     *     'platform': array{'php': string},
     *     'require': array{'php': string, 'vimeo/psalm': string},
     *     'require-dev': array{'vimeo/psalm': string},
     *     'autoload': array{'psr-0'|'psr-4':array{'Vendor\\Namespace\\':string}|'file':array{'path':string}}
     * }
     */
    private function read(): array
    {
        $contents = file_get_contents($this->path);

        if ($contents === false) {
            throw new RuntimeException(sprintf('Could not read expectation file: "%s"', $this->path));
        }

        return Json::decode($contents);
    }
}
