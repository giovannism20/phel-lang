<?php

declare(strict_types=1);

namespace PhelTest\Integration\Repl;

use Generator;
use Phel\Command\Repl\ColorStyle;
use Phel\Command\ReplCommand;
use Phel\Compiler\Analyzer\Environment\GlobalEnvironment;
use Phel\Compiler\CompilerFactory;
use Phel\Compiler\Emitter\OutputEmitter\Munge;
use Phel\Exceptions\Extractor\FilePositionExtractor;
use Phel\Exceptions\Extractor\SourceMapExtractor;
use Phel\Exceptions\Printer\ExceptionArgsPrinter;
use Phel\Exceptions\TextExceptionPrinter;
use Phel\Printer\Printer;
use Phel\Runtime\RuntimeFactory;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

final class ReplIntegrationTest extends TestCase
{
    private const PROMPT = 'phel(1)> ';

    /**
     * @dataProvider providerIntegration
     */
    public function testIntegration(array $inputs, string $expectedOutput): void
    {
        $io = new ReplTestIo();
        $repl = $this->setupFreshRepl($io);

        $io->setInputs($inputs);
        $repl->run();
        $replOutput = $io->getOutputString();

        self::assertEquals($expectedOutput, $replOutput);
    }

    public function providerIntegration(): Generator
    {
        $fixturesDir = realpath(__DIR__ . '/Fixtures');

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($fixturesDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!preg_match('/\.test$/', $file->getRealPath())) {
                continue;
            }

            $filename = str_replace($fixturesDir . '/', '', $file->getRealPath());
            $fileContent = file_get_contents($file->getRealpath());
            $expectedOutput = preg_replace('/....\(\d\)> (?<phel_code>.+)/', 'delete_me', $fileContent);

            $expectedOutput = implode(PHP_EOL, array_filter(
                explode(PHP_EOL, $expectedOutput),
                static fn (string $line): bool => $line !== 'delete_me'
            ));

            yield $filename => [$this->getInputs($fileContent), $expectedOutput];
        }
    }

    private function getInputs(string $testFileContent): array
    {
        $inputs = [];
        foreach (explode(PHP_EOL, $testFileContent) as $line) {
            preg_match('/....\(\d\)> (?<phel_code>.+)/', $line, $out);
            if (!empty($out)) {
                $inputs[] = $out['phel_code'];
            }
        }
        return $inputs;
    }

    private function setupFreshRepl(ReplTestIo $io): ReplCommand
    {
        $compilerFactory = new CompilerFactory();

        $globalEnv = new GlobalEnvironment();
        $rt = RuntimeFactory::initializeNew($globalEnv);
        $rt->addPath('phel\\', [__DIR__ . '/../../../../src/phel/']);
        //$rt->loadNs("phel\\core");

        $exceptionPrinter = new TextExceptionPrinter(
            new ExceptionArgsPrinter(Printer::readable()),
            ColorStyle::noStyles(),
            new Munge(),
            new FilePositionExtractor(new SourceMapExtractor())
        );

        return new ReplCommand(
            $io,
            $compilerFactory->createEvalCompiler($globalEnv),
            $exceptionPrinter,
            ColorStyle::noStyles(),
            Printer::nonReadable()
        );
    }
}
