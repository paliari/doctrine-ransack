<?php

namespace Paliari\Doctrine\Expressions;

use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\CustomAssociationInterface;
use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\VO\FilterFkVO;
use Paliari\Doctrine\VO\RelationVO;
use Paliari\Doctrine\VO\JoinVO;
use Paliari\Doctrine\VO\ParamFilterVO;

class FilterFkManager
{
    protected ?CustomAssociationInterface $customAssociation = null;

    /**
     * @throws RansackException
     */
    public function extract(QueryBuilder $qb, string $modelName, string $alias, ParamFilterVO $vo): FilterFkVO
    {
        if (strpos($vo->key, '.')) {
            return $this->extractFksPoint($qb, $modelName, $alias, $vo);
        }

        return $this->extractFksUnderline($qb, $modelName, $alias, $vo);
    }

    public function setCustomAssociation(?CustomAssociationInterface $customAssociation): void
    {
        $this->customAssociation = $customAssociation;
    }

    /**
     * @throws RansackException
     */
    protected function extractFksPoint(QueryBuilder $qb, string $modelName, string $alias, ParamFilterVO $vo): FilterFkVO
    {
        $fks = explode('.', $vo->key);
        $field = end($fks);
        $fks = array_slice($fks, 0, -1);
        $fkAlias = $alias;
        $fkModelName = $modelName;
        $filterFk = new FilterFkVO();
        foreach ($fks as $fk) {
            if ($fkVO = $this->getTargetEntity($qb, $fkModelName, $fkAlias, $fk)) {
                $fkAlias = $fkVO->join->alias;
                $fkModelName = $fkVO->targetEntity;
                $filterFk->fks[] = $fkVO;
            } else {
                throw new RansackException("Target Model '$fk' not found!");
            }
        }
        $filterFk->field = $this->getField($qb, $fkModelName, $field);
        $filterFk->type = $this->getTypeOfField($qb, $fkModelName, $field);

        return $filterFk;
    }

    /**
     * @throws RansackException
     */
    protected function extractFksUnderline(QueryBuilder $qb, string $modelName, string $alias, ParamFilterVO $vo): FilterFkVO
    {
        $keys = [];
        $a = explode('_', $vo->key);
        $fkAlias = $alias;
        $fkModelName = $modelName;
        $filterFk = new FilterFkVO();
        foreach ($a as $i => $key) {
            $keys[] = $key;
            $fk = implode('_', $keys);
            if ($relationVO = $this->getTargetEntity($qb, $fkModelName, $fkAlias, $fk)) {
                $fkAlias = $relationVO->join->alias;
                $fkModelName = $relationVO->targetEntity;
                $filterFk->fks[] = $relationVO;
                $field = implode('_', array_slice($a, $i + 1));
                if ($field = $this->getField($qb, $fkModelName, $field)) {
                    $filterFk->field = $field;
                    $filterFk->type = $this->getTypeOfField($qb, $fkModelName, $field);

                    return $filterFk;
                }
                $keys = [];
            }
        }
        throw new RansackException("Field '$vo->key' not found!");
    }

    protected function getTargetEntity(QueryBuilder $qb, string $modelName, string $alias, string $field): ?RelationVO
    {
        $mapping = $qb->getEntityManager()->getClassMetadata($modelName)->getAssociationMappings();
        if ($targetEntity = $mapping[$field]['targetEntity'] ?? null) {
            $relationVO = new RelationVO();
            $relationVO->modelName = $modelName;
            $relationVO->fieldName = $field;
            $relationVO->targetEntity = $targetEntity;
            $joinVO = new JoinVO();
            $joinVO->join = "$alias.$field";
            $joinVO->alias = "{$alias}_$field";
            $relationVO->join = $joinVO;

            return $relationVO;
        }
        if ($this->customAssociation) {
            return call_user_func($this->customAssociation, $qb, $modelName, $alias, $field);
        }

        return null;
    }

    protected function getField(QueryBuilder $qb, string $modelName, string $field): ?string
    {
        $classMetadata = $qb->getEntityManager()->getClassMetadata($modelName);
        if ($classMetadata->hasField($field)) {
            return $classMetadata->getFieldName($field);
        }

        return null;
    }

    protected function getTypeOfField(QueryBuilder $qb, string $modelName, string $field): ?string
    {
        return $qb->getEntityManager()->getClassMetadata($modelName)->getTypeOfField($field);
    }
}
