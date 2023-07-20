<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Path\File;

use Composer\InstalledVersions;
use Ghostwriter\Json\Json;
use Ghostwriter\Option\None;
use Ghostwriter\Option\OptionInterface;
use Ghostwriter\Option\Some;
use Ghostwriter\PsalmPluginTester\PhpVersionInterface;
use Ghostwriter\PsalmPluginTester\Version\PhpVersion;
use Ghostwriter\PsalmPluginTester\Version\PsalmVersion;
use RuntimeException;

final class ComposerLockFile implements FileInterface
    //    , PhpVersionInterface, PsalmVersionInterface
{
    use FileTrait;

    public function __construct(
        private readonly string $file,
    ) {
        if (! is_file($this->file)) {
            throw new RuntimeException(sprintf('File "%s" does not exist', $this->file));
        }
    }

    public function getFile(): string
    {
        return $this->file;
    }

    /**
     * @return OptionInterface<PhpVersion>
     */
    public function getPhpVersion(): OptionInterface
    {
        $composerLockContents = file_get_contents($this->file);

        if ($composerLockContents === false) {
            throw new RuntimeException(sprintf('Could not read composer lock file: "%s"', $this->file));
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
        if (! InstalledVersions::isInstalled('vimeo/psalm')) {
            return None::create();
        }

        $psalmVersion = InstalledVersions::getVersion('vimeo/psalm');

        if (is_string($psalmVersion)) {
            return Some::create(new PsalmVersion($psalmVersion));
        }

        return None::create();
    }
}
