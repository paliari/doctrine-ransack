<?php

namespace Paliari\Doctrine\Helpers;

use Doctrine\ORM\EntityManager;
use Paliari\Doctrine\CustomAssociationInterface;
use Paliari\Doctrine\VO\JoinVO;
use Paliari\Doctrine\VO\RelationVO;

class EntityHelper
{
    public function __construct(
        protected EntityManager $em,
        protected ?CustomAssociationInterface $customAssociation = null,
    )
    {
    }

    public function getRelation(string $entityName, string $alias, string $field): ?RelationVO
    {
        $mapping = $this->em->getClassMetadata($entityName)->getAssociationMappings();
        if ($targetEntity = $mapping[$field]['targetEntity'] ?? null) {
            $relationVO = new RelationVO();
            $relationVO->entityName = $entityName;
            $relationVO->fieldName = $field;
            $relationVO->targetEntity = $targetEntity;
            $joinVO = new JoinVO();
            $joinVO->join = "$alias.$field";
            $joinVO->alias = "{$alias}_$field";
            $relationVO->join = $joinVO;

            return $relationVO;
        }
        if ($this->customAssociation) {
            return call_user_func($this->customAssociation, $entityName, $alias, $field);
        }

        return null;
    }

    public function getField(string $entityName, string $field): ?string
    {
        $classMetadata = $this->em->getClassMetadata($entityName);
        if ($classMetadata->hasField($field)) {
            return $classMetadata->getFieldName($field);
        }

        return null;
    }

    public function getTypeOfField(string $entityName, string $field): ?string
    {
        return $this->em->getClassMetadata($entityName)->getTypeOfField($field);
    }
}
