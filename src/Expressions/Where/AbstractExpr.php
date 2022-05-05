<?php

namespace Paliari\Doctrine\Expressions\Where;

abstract class AbstractExpr implements ExprInterface
{
    protected function fieldKey(string $field, string $exprName): string
    {
        return str_replace('.', '_', $field) . "_$exprName";
    }
}
