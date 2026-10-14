<?php
/**
 * This file is part of TypePHP.
 *
 * Lowers if, elseif, and else statement chains.
 */

namespace TypePhp\Parser;

use PhpParser\Node;
use TypePhp\Analysis\PiConstraint;

trait ConditionalControlTrait
{
    protected function parseIf(Node\Stmt\If_ $v): string
    {
        $arms = [[$v->cond, $v->stmts]];
        foreach ($v->elseifs as $elseif) {
            $arms[] = [$elseif->cond, $elseif->stmts];
        }

        return $this->parseBeforeStmtLines()
            . $this->parseIfChain($arms, $v->else, 0) . PHP_EOL;
    }

    protected function parseIfChain(array $arms, ?Node\Stmt\Else_ $else, int $index): string
    {
        if (!isset($arms[$index])) {
            if (!$else || $this->isEmptyStmtList($else->stmts)) {
                return '';
            }
            return $this->parseStmts($else->stmts);
        }

        [$cond, $stmts] = $arms[$index];
        $code = $this->genConditionWithCapturedStmts($cond, 'if ');
        try {
            $this->pushPiConstraintScope($cond);
            $code .= $this->parseBlockStmts($stmts);
        } finally {
            $this->popPiConstraintScope();
        }
        $hasTail = isset($arms[$index + 1]) || ($else && !$this->isEmptyStmtList($else->stmts));
        if ($hasTail) {
            $code .= $this->getIndent() . '} else {' . PHP_EOL;
            $this->indentLevel++;
            $tail = $this->parseIfChain($arms, $else, $index + 1);
            $code .= $tail;
            $this->indentLevel--;
            if (!str_ends_with($tail, PHP_EOL)) {
                $code .= PHP_EOL;
            }
        }
        $code .= $this->getIndent() . '}';
        return $code;
    }

    protected function isEmptyStmtList(array $stmts): bool
    {
        foreach ($stmts as $stmt) {
            if (!$stmt instanceof Node\Stmt\Nop) {
                return false;
            }
        }
        return true;
    }

    /**
     * Open the e-SSA pi scope of a conditional arm.
     *
     * Pi constraints record the narrowing granted inside a branch by its
     * condition (e.g. `if ($x instanceof Foo)` proves $x carries Foo-or-child
     * for the whole arm). Only instanceof entries are consumed here — scalar
     * type-check narrowings remain unconsumed analysis output for EXP-02.
     * Variables flagged unsafe in the function-level SSA pre-pass are skipped.
     */
    protected function pushPiConstraintScope(Node $cond): void
    {
        $ssaBuilder = $this->context->ssaBuilder;
        if ($ssaBuilder === null || $this->context->hasGotoBlocks) {
            $this->context->piConstraintScopes[] = [];
            return;
        }

        try {
            $armIf = new Node\Stmt\If_($cond, ['stmts' => [], 'elseifs' => [], 'else' => null]);
            $trueVars = $ssaBuilder->buildPiConstraints($armIf)['trueVars'] ?? [];
        } catch (\Throwable) {
            // pi must never change compiler behavior outside devirtualization,
            // so any analysis failure degrades to an empty scope.
            $this->context->piConstraintScopes[] = [];
            return;
        }

        $scope = [];
        foreach ($trueVars as $varName => $pi) {
            if (!$pi instanceof PiConstraint || !$pi->isInstanceof) {
                continue;
            }
            if (isset($this->context->piUnsafeVars[$varName])) {
                continue;
            }
            // static/global rebinding may bypass the SSA definition tracking
            // entirely; the parse-time flags reject those variables outright.
            if ($this->hasScopeGlobalVar($varName) || $this->hasStaticVar($varName)) {
                continue;
            }
            $scope[$varName] = $pi;
        }
        $this->context->piConstraintScopes[] = $scope;
    }

    protected function popPiConstraintScope(): void
    {
        array_pop($this->context->piConstraintScopes);
    }

    /** Innermost instanceof narrowing for an object variable at the current parse point, or null. */
    protected function getPiNarrowedObject(string $object): ?PiConstraint
    {
        for ($i = count($this->context->piConstraintScopes) - 1; $i >= 0; $i--) {
            if (isset($this->context->piConstraintScopes[$i][$object])) {
                return $this->context->piConstraintScopes[$i][$object];
            }
        }
        return null;
    }

    /**
     * 逻辑比较的运算，必须返回 bool 类型.
     */
}
