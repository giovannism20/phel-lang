<?php

declare(strict_types=1);

namespace Phel;

use Phel\Analyzer\AnalyzeArray;
use Phel\Analyzer\AnalyzeBracketTuple;
use Phel\Analyzer\AnalyzeLiteral;
use Phel\Analyzer\AnalyzeSymbol;
use Phel\Analyzer\AnalyzeTable;
use Phel\Analyzer\AnalyzeTuple;
use Phel\Ast\Node;
use Phel\Exceptions\AnalyzerException;
use Phel\Lang\Keyword;
use Phel\Lang\Phel;
use Phel\Lang\PhelArray;
use Phel\Lang\Symbol;
use Phel\Lang\Table;
use Phel\Lang\Tuple;

final class Analyzer
{
    private GlobalEnvironment $globalEnvironment;

    public function __construct(GlobalEnvironment $globalEnvironment)
    {
        $this->globalEnvironment = $globalEnvironment;
    }

    public function getGlobalEnvironment(): GlobalEnvironment
    {
        return $this->globalEnvironment;
    }

    /** @param Phel|scalar|null $x */
    public function analyze($x, ?NodeEnvironment $env = null): Node
    {
        if (null === $env) {
            $env = NodeEnvironment::empty();
        }

        if ($this->isLiteral($x)) {
            return (new AnalyzeLiteral())($x, $env);
        }

        if ($x instanceof Symbol) {
            return (new AnalyzeSymbol($this))($x, $env);
        }

        if ($x instanceof Tuple && $x->isUsingBracket()) {
            return (new AnalyzeBracketTuple($this))($x, $env);
        }

        if ($x instanceof PhelArray) {
            return (new AnalyzeArray($this))($x, $env);
        }

        if ($x instanceof Table) {
            return (new AnalyzeTable($this))($x, $env);
        }

        if ($x instanceof Tuple) {
            return (new AnalyzeTuple($this))($x, $env);
        }

        throw new AnalyzerException('Unhandled type: ' . var_export($x, true));
    }

    private function isLiteral($x): bool
    {
        return is_string($x)
            || is_float($x)
            || is_int($x)
            || is_bool($x)
            || $x === null
            || $x instanceof Keyword;
    }
}
