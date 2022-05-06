<?php

namespace Paliari\Doctrine\Expressions\Where;

use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\VO\FilterVO;

class NotNullExpr extends AbstractExpr
{
    public const NAME = 'not_null';

    public function create(QueryBuilder $qb, FilterVO $vo): string
    {
        return $qb->expr()->isNotNull($vo->field);
    }
}
