<?php

namespace Paliari\Doctrine\Factories;

use Doctrine\ORM\QueryBuilder;
use JetBrains\PhpStorm\Pure;
use Paliari\Doctrine\QueryBuilderManger;

class QueryBuilderManagerFactory
{
    #[Pure]
    public function create(QueryBuilder $qb, string $alias = 't'): QueryBuilderManger
    {
        return new QueryBuilderManger($qb, $alias);
    }
}
