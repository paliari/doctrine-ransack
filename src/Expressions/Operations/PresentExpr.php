<?php

namespace Paliari\Doctrine\Expressions\Operations;

use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\VO\FilterVO;

class PresentExpr extends AbstractExpr
{
    public const NAME = 'present';

    public function create(QueryBuilder $qb, FilterVO $vo): string
    {
        $expr = $qb->expr();

        return $expr->andX($expr->isNotNull($vo->field), $expr->neq($vo->field, "''"));
    }
}
