<?php

namespace Paliari\Doctrine\Expressions\Where;

use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\VO\FilterVO;

class OrderByExpr extends AbstractExpr
{
    public const NAME = 'order_by';

    public function create(QueryBuilder $qb, FilterVO $vo): mixed
    {
        $qb->addOrderBy($vo->field, $vo->value);

        return null;
    }
}
