<?php

namespace Paliari\Doctrine\Expressions\Where;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\VO\FilterVO;

class NotEqExpr extends AbstractExpr
{
    public const NAME = 'not_eq';

    public function create(QueryBuilder $qb, FilterVO $vo): Comparison
    {
        $key = $this->fieldKey($vo->field, static::NAME);
        $qb->setParameter($key, $vo->value, $vo->type);

        return $qb->expr()->neq($vo->field, ":$key");
    }
}