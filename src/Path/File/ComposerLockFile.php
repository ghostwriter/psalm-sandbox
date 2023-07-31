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

final class ComposerLockFile implements FileInterface
{
    use FileTrait;

    public function getFile(): string
    {
        return $this->path;
    }

    /**
     * @return OptionInterface<PhpVersion>
     */
    public function getPhpVersion(): OptionInterface
    {
        if (! is_file($this->path)) {
            throw new RuntimeException(sprintf('File "%s" does not exist', $this->path));
        }

        $composerLockContents = file_get_contents($this->path);

        if ($composerLockContents === false) {
            throw new RuntimeException(sprintf('Could not read composer lock file: "%s"', $this->path));
        }

        $composerLockData = Json::decode($composerLockContents);

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
        $none = None::create();
        if (! InstalledVersions::isInstalled('vimeo/psalm')) {
            return $none;
        }

        $psalmVersion = InstalledVersions::getVersion('vimeo/psalm');

        if (is_string($psalmVersion)) {
            return Some::create(new PsalmVersion($psalmVersion));
        }

        return $none;
    }
}
