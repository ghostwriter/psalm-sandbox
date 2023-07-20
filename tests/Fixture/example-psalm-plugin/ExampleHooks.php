<?php

declare(strict_types=1);

namespace Ghostwriter\ExamplePsalmPlugin;

use PhpParser\Node\Stmt\Echo_;
use Psalm\CodeLocation;
use Psalm\Issue\ArgumentTypeCoercion;
use Psalm\IssueBuffer;
use Psalm\Plugin\EventHandler\AfterStatementAnalysisInterface;
use Psalm\Plugin\EventHandler\Event\AfterStatementAnalysisEvent;
use Psalm\Type\Atomic\TLiteralString;
use Psalm\Type\Atomic\TString;

final class ExampleHooks implements AfterStatementAnalysisInterface
{
    /**
     * Called after a statement has been checked
     */
    public static function afterStatementAnalysis(AfterStatementAnalysisEvent $event): bool|null
    {
        $statements = $event->getStmt();
        $statementsSource = $event->getStatementsSource();
        if ($statements instanceof Echo_) {
            foreach ($statements->exprs as $expression) {
                $expressionType = $statementsSource->getNodeTypeProvider()->getType($expression);

                if (!$expressionType || $expressionType->hasMixed()) {
                    IssueBuffer::maybeAdd(
                        new ArgumentTypeCoercion(
                            'Echo requires an unescaped string, ' . $expressionType . ' provided',
                            new CodeLocation($statementsSource, $expression),
                            'echo',
                        ),
                        $statementsSource->getSuppressedIssues(),
                    );
                    continue;
                }

                foreach ($expressionType->getAtomicTypes() as $type) {
                    if (! $type instanceof TString && $type instanceof TLiteralString) {
                        continue;
                    }

                    IssueBuffer::maybeAdd(
                        new ArgumentTypeCoercion(
                            'Echo requires an unescaped string, ' . $expressionType . ' provided',
                            new CodeLocation($statementsSource, $expression),
                            'echo',
                        ),
                        $statementsSource->getSuppressedIssues(),
                    );
                }
            }
        }

        return null;
    }
}
