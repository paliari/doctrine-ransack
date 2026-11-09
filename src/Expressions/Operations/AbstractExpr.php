<?php

namespace Paliari\Doctrine\Expressions\Operations;

use Doctrine\ORM\QueryBuilder;

abstract class AbstractExpr implements ExprInterface
{
    protected function fieldKey(string $field, string $exprName): string
    {
        return str_replace('.', '_', $field) . "_$exprName";
    }

    /**
     * Use $suffix when the expression binds "{key}{$suffix}" parameters instead of "{key}" itself.
     */
    protected function parameterKey(QueryBuilder $qb, string $field, string $exprName, string $suffix = ''): string
    {
        $base = $this->fieldKey($field, $exprName);
        $key = $base;
        for ($i = 1; null !== $qb->getParameter($key . $suffix); $i++) {
            $key = "{$base}_$i";
        }

        return $key;
    }
}
