<?php

namespace Paliari\Doctrine\Expressions\Operations;

use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\VO\FilterVO;

interface ExprInterface
{
    public function create(QueryBuilder $qb, FilterVO $vo): mixed;
}
