<?php

namespace Paliari\Doctrine\Expressions\Operations;

use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\VO\FilterVO;

class GroupByExpr extends AbstractExpr
{
    public const NAME = 'group_by';

    public function create(QueryBuilder $qb, FilterVO $vo): mixed
    {
        $qb->addGroupBy($vo->field);

        return null;
    }
}
