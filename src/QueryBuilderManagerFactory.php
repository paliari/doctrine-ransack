<?php

namespace Paliari\Doctrine;

use Doctrine\ORM\QueryBuilder;
use JetBrains\PhpStorm\Pure;

class QueryBuilderManagerFactory
{
    #[Pure]
    public function create(QueryBuilder $qb, string $alias = 't'): QueryBuilderManger
    {
        return new QueryBuilderManger($qb, $alias);
    }
}
