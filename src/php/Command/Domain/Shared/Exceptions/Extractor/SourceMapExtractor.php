<?php

declare(strict_types=1);

namespace Phel\Command\Domain\Shared\Exceptions\Extractor;

use Phel\Command\Domain\Shared\Exceptions\Extractor\ReadModel\SourceMapInformation;

final class SourceMapExtractor implements SourceMapExtractorInterface
{
    public function extractFromFile(string $filename): SourceMapInformation
    {
        $f = fopen($filename, 'rb');
        $phpPrefix = fgets($f);
        $filenameComment = fgets($f);
        $sourceMapComment = fgets($f) ?: '';

        return new SourceMapInformation($filenameComment, $sourceMapComment);
    }
}
