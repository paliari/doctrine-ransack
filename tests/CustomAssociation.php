<?php

namespace Tests;

use Doctrine\ORM\Query\Expr\Join;
use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\CustomAssociationInterface;
use Paliari\Doctrine\VO\RelationVO;
use Paliari\Doctrine\VO\JoinVO;
use Person;
use User;

class CustomAssociation implements CustomAssociationInterface
{
    public function __invoke(QueryBuilder $qb, string $modelName, string $alias, string $field): ?RelationVO
    {
        if (User::class === $modelName && 'custom' == $field) {
            $relationVO = new RelationVO();
            $relationVO->modelName = $modelName;
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
