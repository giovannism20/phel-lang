<?php

declare(strict_types=1);

namespace Phel\Command;

use LogicException;
use Phel\Command\Export\DirectoryRemover;
use Phel\Command\Export\DirectoryRemoverInterface;
use Phel\Command\Export\ExportCommand;
use Phel\Command\Export\FunctionsToExportFinder;
use Phel\Command\Export\FunctionsToExportFinderInterface;
use Phel\Command\Format\FormatCommand;
use Phel\Command\Format\PathFilterInterface;
use Phel\Command\Format\PhelPathFilter;
use Phel\Command\Repl\ColorStyle;
use Phel\Command\Repl\ReplCommand;
use Phel\Command\Repl\ReplCommandSystemIo;
use Phel\Command\Run\RunCommand;
use Phel\Command\Shared\CommandIoInterface;
use Phel\Command\Shared\CommandSystemIo;
use Phel\Command\Shared\NamespaceExtractor;
use Phel\Command\Shared\NamespaceExtractorInterface;
use Phel\Command\Test\TestCommand;
use Phel\Compiler\Analyzer\Environment\GlobalEnvironmentInterface;
use Phel\Compiler\CompilerFactoryInterface;
use Phel\Formatter\FormatterFactoryInterface;
use Phel\Interop\Generator\WrapperGeneratorInterface;
use Phel\Interop\InteropFactoryInterface;
use Phel\Interop\ReadModel\ExportConfig;
use Phel\PhelConfigInterface;
use Phel\Printer\Printer;
use Phel\Runtime\Exceptions\TextExceptionPrinter;
use Phel\Runtime\RuntimeInterface;

final class CommandFactory implements CommandFactoryInterface
{
    private string $projectRootDir;
    private PhelConfigInterface $phelConfig;
    private CompilerFactoryInterface $compilerFactory;
    private FormatterFactoryInterface $formatterFactory;
    private InteropFactoryInterface $interopFactory;

    public function __construct(
        string $projectRootDir,
        PhelConfigInterface $phelConfig,
        CompilerFactoryInterface $compilerFactory,
        FormatterFactoryInterface $formatterFactory,
        InteropFactoryInterface $interopFactory
    ) {
        $this->projectRootDir = $projectRootDir;
        $this->compilerFactory = $compilerFactory;
        $this->phelConfig = $phelConfig;
        $this->formatterFactory = $formatterFactory;
        $this->interopFactory = $interopFactory;
    }

    public function createReplCommand(RuntimeInterface $runtime): ReplCommand
    {
        $runtime->loadFileIntoNamespace('user', __DIR__ . '/Repl/startup.phel');

        return new ReplCommand(
            new ReplCommandSystemIo($this->projectRootDir . '.phel-repl-history'),
            $this->compilerFactory->createEvalCompiler($runtime->getEnv()),
            TextExceptionPrinter::create(),
            ColorStyle::withStyles(),
            Printer::nonReadableWithColor()
        );
    }

    public function createRunCommand(RuntimeInterface $runtime): RunCommand
    {
        return new RunCommand(
            $runtime,
            $this->createNamespaceExtractor($runtime->getEnv())
        );
    }

    public function createTestCommand(RuntimeInterface $runtime): TestCommand
    {
        return new TestCommand(
            $this->projectRootDir,
            $runtime,
            $this->createNamespaceExtractor($runtime->getEnv()),
            $this->compilerFactory->createEvalCompiler($runtime->getEnv())
        );
    }

    public function createFormatCommand(): FormatCommand
    {
        return new FormatCommand(
            $this->formatterFactory->createFormatter(),
            $this->createCommandIo(),
            $this->createPathFilter(),
            TextExceptionPrinter::create()
        );
    }

    public function createExportCommand(RuntimeInterface $runtime): ExportCommand
    {
        return new ExportCommand(
            $this->createWrapperGenerator(),
            $this->createCommandIo(),
            $this->createFunctionsToExportFinder($runtime),
            $this->createDirectoryRemover()
        );
    }

    private function createCommandIo(): CommandIoInterface
    {
        return new CommandSystemIo();
    }

    private function createPathFilter(): PathFilterInterface
    {
        return new PhelPathFilter();
    }

    private function createFunctionsToExportFinder(RuntimeInterface $runtime): FunctionsToExportFinderInterface
    {
        return new FunctionsToExportFinder(
            $this->projectRootDir,
            $runtime,
            $this->createNamespaceExtractor($runtime->getEnv())
        );
    }

    private function createNamespaceExtractor(GlobalEnvironmentInterface $globalEnv): NamespaceExtractorInterface
    {
        return new NamespaceExtractor(
            $this->compilerFactory->createLexer(),
            $this->compilerFactory->createParser(),
            $this->compilerFactory->createReader($globalEnv),
            $this->createCommandIo(),
            $this->phelConfig->get('tests') ?? []
        );
    }

    private function createDirectoryRemover(): DirectoryRemoverInterface
    {
        return new DirectoryRemover();
    }

    private function createWrapperGenerator(): WrapperGeneratorInterface
    {
        $phelConfigExport = $this->phelConfig->get('export');

        if (!isset($phelConfigExport['target-directory'])) {
            throw new LogicException('"target-directory" phel config missing in composer.json');
        }
        if (!isset($phelConfigExport['prefix-namespace'])) {
            throw new LogicException('"prefix-namespace" phel config missing in composer.json');
        }

        $exportConfig = new ExportConfig(
            (string)$phelConfigExport['target-directory'],
            (string)$phelConfigExport['prefix-namespace']
        );

        return $this->interopFactory->createWrapperGenerator($exportConfig);
    }
}
