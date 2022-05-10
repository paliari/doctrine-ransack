<?php

namespace Paliari\Doctrine\Expressions\Operations;

use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\VO\FilterVO;

class NullExpr extends AbstractExpr
{
    public const NAME = 'null';

    public function create(QueryBuilder $qb, FilterVO $vo): string
    {
        return $qb->expr()->isNull($vo->field);
    }
}
