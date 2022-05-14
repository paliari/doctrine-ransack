<?php

namespace Paliari\Doctrine\Factories;

use Doctrine\ORM\QueryBuilder;
use JetBrains\PhpStorm\Pure;
use Paliari\Doctrine\Helpers\EntityHelper;
use Paliari\Doctrine\Helpers\QueryBuilderHelper;

class QueryBuilderHelperFactory
{
    public function __construct(protected EntityHelper $entityHelper)
    {
    }

    #[Pure]
    public function create(QueryBuilder $qb, string $alias = 't'): QueryBuilderHelper
    {
        return new QueryBuilderHelper($this->entityHelper, $qb, $alias);
    }
}
