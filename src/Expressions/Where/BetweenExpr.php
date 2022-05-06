<?php

namespace Paliari\Doctrine\Expressions\Where;

use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\VO\FilterVO;

class BetweenExpr extends AbstractExpr
{
    public const NAME = 'between';

    public function create(QueryBuilder $qb, FilterVO $vo): string
    {
        $key = $this->fieldKey($vo->field, static::NAME);
        $keyX = "{$key}_x";
        $keyY = "{$key}_y";
        [$valueX, $valueY] = (array)$vo->value;
        $qb->setParameter($keyX, $valueX, $vo->type);
        $qb->setParameter($keyY, $valueY, $vo->type);

        return $qb->expr()->between($vo->field, ":$keyX", ":$keyY");
    }
}
