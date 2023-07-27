<?php

declare(strict_types=1);

namespace Ghostwriter\PsalmPluginTester\Path\File;

use Ghostwriter\Option\None;
use Ghostwriter\Option\OptionInterface;
use Ghostwriter\Option\Some;
use Ghostwriter\PsalmPluginTester\Version\PhpVersion;
use RuntimeException;
use SimpleXMLElement;
use Throwable;

final class PsalmXmlFile implements FileInterface
{
    use FileTrait;

    private const DEFAULT_PSALM_CONFIG = "<?xml version=\"1.0\"?>\n"
    . "<psalm errorLevel=\"1\" %s>\n"
    . "  <projectFiles>\n"
    . "    <directory name=\".\"/>\n"
    . "  </projectFiles>\n"
    . "</psalm>\n";
    //    use PathTrait;

    public function getFile(): string
    {
        return $this->path;
    }

    /**
     * @return OptionInterface<PhpVersion>
     */
    public function getPhpVersion(): OptionInterface
    {
        $psalmConfigContents = file_get_contents($this->path);
        if ($psalmConfigContents === false) {
            throw new RuntimeException(sprintf('Could not read psalm config file: "%s"', $this->path));
        }

        try {
            $psalmConfigXML = new SimpleXMLElement($psalmConfigContents);
        } catch (Throwable $e) {
            throw new RuntimeException(
                sprintf('Could not parse the XML data in psalm config file: "%s"', $this->path),
                0,
                $e
            );
        }

        // <psalm phpVersion="8.0">
        $phpVersion = $psalmConfigXML->phpVersion;

        if (is_string($phpVersion)) {
            return Some::create(new PhpVersion($phpVersion));
        }

        return None::create();
    }
}
