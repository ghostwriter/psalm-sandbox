<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Version;

use Ghostwriter\Option\OptionInterface;
use Ghostwriter\PsalmPluginTester\VersionInterface;

interface PhpVersionInterface extends VersionInterface
{
    /**
     * @return OptionInterface<VersionInterface>
     */
    public function getPhpVersion(): OptionInterface;
}
