<?php

namespace Paliari\Doctrine\Expressions\Where;

use Doctrine\ORM\Query\Expr\Func;
use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\VO\FilterVO;

class NotInExpr extends AbstractExpr
{
    public const NAME = 'not_in';

    public function create(QueryBuilder $qb, FilterVO $vo): Func
    {
        $key = $this->fieldKey($vo->field, static::NAME);
        $conn = $qb->getEntityManager()->getConnection();
        $values = array_map(fn($v) => $conn->convertToDatabaseValue($v, $vo->type), (array)$vo->value);
        $qb->setParameter($key, $values, $vo->type);

        return $qb->expr()->notIn($vo->field, ":$key");
    }
}
