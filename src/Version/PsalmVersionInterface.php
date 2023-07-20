<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Version;

use Ghostwriter\Option\OptionInterface;
use Ghostwriter\PsalmPluginTester\VersionInterface;

interface PsalmVersionInterface extends VersionInterface
{
    /**
     * @return OptionInterface<VersionInterface>
     */
    public function getPsalmVersion(): OptionInterface;
}
