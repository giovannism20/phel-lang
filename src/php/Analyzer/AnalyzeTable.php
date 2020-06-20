<?php

declare(strict_types=1);

namespace Phel\Analyzer;

use Phel\Ast\TableNode;
use Phel\Lang\Table;
use Phel\NodeEnvironment;

final class AnalyzeTable
{
    use WithAnalyzer;

    public function __invoke(Table $table, NodeEnvironment $env): TableNode
    {
        $keyValues = [];
        $kvEnv = $env->withContext(NodeEnvironment::CTX_EXPR);
        foreach ($table as $key => $value) {
            $keyValues[] = $this->analyzer->analyze($key, $kvEnv);
            $keyValues[] = $this->analyzer->analyze($value, $kvEnv);
        }

        return new TableNode($env, $keyValues, $table->getStartLocation());
    }
}