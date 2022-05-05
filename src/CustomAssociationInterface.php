<?php

namespace Paliari\Doctrine;

use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\VO\FkVO;

interface CustomAssociationInterface
{
    public function __invoke(QueryBuilder $qb, string $modelName, string $alias, string $field): ?FkVO;
}
