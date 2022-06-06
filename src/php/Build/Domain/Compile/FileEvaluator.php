<?php

declare(strict_types=1);

namespace Phel\Build\Domain\Compile;

use Phel\Build\Domain\Extractor\NamespaceExtractor;
use Phel\Compiler\CompilerFacadeInterface;
use Phel\Compiler\Infrastructure\CompileOptions;

final class FileEvaluator
{
    private CompilerFacadeInterface $compilerFacade;

    private NamespaceExtractor $namespaceExtractor;

    public function __construct(
        CompilerFacadeInterface $compilerFacade,
        NamespaceExtractor $namespaceExtractor
    ) {
        $this->compilerFacade = $compilerFacade;
        $this->namespaceExtractor = $namespaceExtractor;
    }

    public function evalFile(string $src): CompiledFile
    {
        $options = (new CompileOptions())
            ->setSource($src)
            ->setIsEnabledSourceMaps(true);

        $this->compilerFacade->eval(file_get_contents($src), $options);

        $namespaceInfo = $this->namespaceExtractor->getNamespaceFromFile($src);

        return new CompiledFile(
            $src,
            '',
            $namespaceInfo->getNamespace()
        );
    }
}