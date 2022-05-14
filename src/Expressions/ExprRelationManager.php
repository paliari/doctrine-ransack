<?php

namespace Paliari\Doctrine\Expressions;

use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\Helpers\EntityHelper;
use Paliari\Doctrine\VO\FilterRelationVO;
use Paliari\Doctrine\VO\ParamFilterVO;

class ExprRelationManager
{
    public function __construct(protected EntityHelper $entityHelper)
    {
    }

    /**
     * @throws RansackException
     */
    public function extract(string $entityName, string $alias, ParamFilterVO $vo): FilterRelationVO
    {
        if (strpos($vo->key, '.')) {
            return $this->extractRelationsPoint($entityName, $alias, $vo);
        }

        return $this->extractRelationsUnderline($entityName, $alias, $vo);
    }

    /**
     * @throws RansackException
     */
    protected function extractRelationsPoint(string $entityName, string $alias, ParamFilterVO $vo): FilterRelationVO
    {
        $keys = explode('.', $vo->key);
        $field = end($keys);
        $joins = array_slice($keys, 0, -1);
        $joinAlias = $alias;
        $joinEntityName = $entityName;
        $filterRelationVO = new FilterRelationVO();
        foreach ($joins as $joinName) {
            if ($relationVO = $this->entityHelper->getRelation($joinEntityName, $joinAlias, $joinName)) {
                $joinAlias = $relationVO->join->alias;
                $joinEntityName = $relationVO->targetEntity;
                $filterRelationVO->relations[] = $relationVO;
            } else {
                throw new RansackException("Target Model '$joinName' not found!");
            }
        }
        $filterRelationVO->field = $this->entityHelper->getField($joinEntityName, $field);
        $filterRelationVO->type = $this->entityHelper->getTypeOfField($joinEntityName, $field);

        return $filterRelationVO;
    }

    /**
     * @throws RansackException
     */
    protected function extractRelationsUnderline(string $entityName, string $alias, ParamFilterVO $vo): FilterRelationVO
    {
        $keys = [];
        $a = explode('_', $vo->key);
        $joinAlias = $alias;
        $joinEntityName = $entityName;
        $filterRelationVO = new FilterRelationVO();
        foreach ($a as $i => $key) {
            $keys[] = $key;
            $joinName = implode('_', $keys);
            if ($relationVO = $this->entityHelper->getRelation($joinEntityName, $joinAlias, $joinName)) {
                $joinAlias = $relationVO->join->alias;
                $joinEntityName = $relationVO->targetEntity;
                $filterRelationVO->relations[] = $relationVO;
                $field = implode('_', array_slice($a, $i + 1));
                if ($field = $this->entityHelper->getField($joinEntityName, $field)) {
                    $filterRelationVO->field = $field;
                    $filterRelationVO->type = $this->entityHelper->getTypeOfField($joinEntityName, $field);

                    return $filterRelationVO;
                }
                $keys = [];
            }
        }
        throw new RansackException("Field '$vo->key' not found!");
    }
}
