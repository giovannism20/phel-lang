<?php

declare(strict_types=1);

namespace Phel\Command\Domain\Finder;

use Phel\Command\Domain\CodeDirectories;

final class DirectoryFinder implements DirectoryFinderInterface
{
    public function __construct(
        private string $applicationRootDir,
        private CodeDirectories $codeDirectories,
        private VendorDirectoriesFinderInterface $vendorDirectoriesFinder,
    ) {
    }

    /**
     * @return list<string>
     */
    public function getSourceDirectories(): array
    {
        return $this->toAbsoluteDirectories($this->codeDirectories->getSourceDirs());
    }

    /**
     * @return list<string>
     */
    public function getTestDirectories(): array
    {
        return $this->toAbsoluteDirectories($this->codeDirectories->getTestDirs());
    }

    /**
     * @return list<string>
     */
    public function getVendorSourceDirectories(): array
    {
        return $this->vendorDirectoriesFinder->findPhelSourceDirectories();
    }

    public function getOutputDirectory(): string
    {
        return $this->applicationRootDir . '/' . $this->codeDirectories->getOutputDir();
    }

    /**
     * @return list<string>
     */
    private function toAbsoluteDirectories(array $relativeDirectories): array
    {
        return array_map(
            function (string $dir): string {
                return realpath($dir) !== false
                    ? $dir
                    : $this->applicationRootDir . '/' . $dir;
            },
            $relativeDirectories,
        );
    }
}
