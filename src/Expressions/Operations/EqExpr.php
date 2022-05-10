<?php

namespace Paliari\Doctrine\Expressions\Operations;

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\VO\FilterVO;

class EqExpr extends AbstractExpr
{
    public const NAME = 'eq';

    public function create(QueryBuilder $qb, FilterVO $vo): Comparison
    {
        $key = $this->fieldKey($vo->field, static::NAME);
        $qb->setParameter($key, $vo->value, $vo->type);

        return $qb->expr()->eq($vo->field, ":$key");
    }
}
