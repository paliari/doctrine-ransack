<?php

namespace Paliari\Doctrine\Expressions\Operations;

use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\VO\FilterVO;

class BlankExpr extends AbstractExpr
{
    public const NAME = 'blank';

    public function create(QueryBuilder $qb, FilterVO $vo): string
    {
        $expr = $qb->expr();

        return $expr->orX($expr->isNull($vo->field), $expr->eq($vo->field, "''"));
    }
}
