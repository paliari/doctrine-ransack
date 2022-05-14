<?php

namespace Tests;

use Doctrine\ORM\Query\Expr\Join;
use Paliari\Doctrine\CustomAssociationInterface;
use Paliari\Doctrine\VO\JoinVO;
use Paliari\Doctrine\VO\RelationVO;
use Person;
use User;

class CustomAssociation implements CustomAssociationInterface
{
    public function __invoke(string $entityName, string $alias, string $field): ?RelationVO
    {
        if (User::class === $entityName && 'custom' == $field) {
            $relationVO = new RelationVO();
            $relationVO->entityName = $entityName;
            $relationVO->fieldName = $field;
            $relationVO->targetEntity = Person::class;
            $joinVO = new JoinVO();
            $joinVO->join = Person::class;
            $joinVO->alias = "{$alias}_$field";
            $joinVO->conditionType = Join::WITH;
            $joinVO->condition = "$alias.email = $joinVO->alias.email";
            $relationVO->join = $joinVO;

            return $relationVO;
        }

        return null;
    }
}
