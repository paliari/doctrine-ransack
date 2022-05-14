<?php

namespace Paliari\Doctrine;

use Paliari\Doctrine\VO\RelationVO;

interface CustomAssociationInterface
{
    public function __invoke(string $entityName, string $alias, string $field): ?RelationVO;
}
