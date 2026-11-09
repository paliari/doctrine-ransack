<?php

namespace Paliari\Doctrine\Expressions\Operations;

use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\VO\FilterVO;

class BetweenExpr extends AbstractExpr
{
    public const NAME = 'between';

    /**
     * @throws RansackException
     */
    public function create(QueryBuilder $qb, FilterVO $vo): string
    {
        $values = is_array($vo->value) ? array_values($vo->value) : [$vo->value];
        if (2 !== count($values)) {
            $count = count($values);
            throw new RansackException("Between expects 2 values, got $count!");
        }
        [$valueX, $valueY] = $values;
        $key = $this->parameterKey($qb, $vo->field, static::NAME, '_x');
        $keyX = "{$key}_x";
        $keyY = "{$key}_y";
        $qb->setParameter($keyX, $valueX, $vo->type);
        $qb->setParameter($keyY, $valueY, $vo->type);

        return $qb->expr()->between($vo->field, ":$keyX", ":$keyY");
    }
}
