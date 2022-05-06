<?php

namespace Paliari\Doctrine;

use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\VO\FilterVO;
use Paliari\Doctrine\VO\ParamFilterVO;
use Paliari\Doctrine\VO\WhereParamsVO;

class RansackFilter
{
    public function __construct(
        protected RansackConfig $config,
        protected QueryBuilderManger $qbManager,
        protected string $modelName,
        protected string $alias = 't',
    )
    {
    }

    /**
     * @throws RansackException
     */
    public function apply(WhereParamsVO $paramsVO): QueryBuilderManger
    {
        $modelName = $this->modelName;
        $qb = $this->qbManager->getQueryBuilder();
        foreach ($paramsVO->where as $k => $v) {
            if (!$this->isBlank($v) || preg_match('/_null$/', $k)) {
                $paramFilterVO = $this->config->getParamFilterParser()->parse($k, $v);
                $keys = explode('_or_', $paramFilterVO->key);
                if (count($keys) > 1) {
                    $args = [];
                    foreach ($keys as $key) {
                        $vo = new ParamFilterVO($paramFilterVO->toArray());
                        $vo->key = $key;
                        $args[] = $this->filter($modelName, $vo);
                    }
                    $qb->andWhere(call_user_func_array([$qb->expr(), 'orX'], $args));
                } elseif ($expr = $this->filter($modelName, $paramFilterVO)) {
                    $qb->andWhere($expr);
                }
            }
        }

        return $this->qbManager;
    }

    /**
     * @throws RansackException
     */
    protected function filter(string $modelName, ParamFilterVO $vo)
    {
        if ($field = $this->getField($modelName, $vo->key)) {
            $vo->key = $field;
            $type = $this->getTypeOfField($modelName, $field);

            return $this->createExpr($this->alias, $vo, $type);
        } else {
            return $this->filtersFks($modelName, $vo);
        }
    }

    /**
     * @throws RansackException
     */
    protected function filtersFks(string $modelName, ParamFilterVO $vo)
    {
        $qb = $this->qbManager->getQueryBuilder();
        $alias = $this->alias;
        $filterFk = $this->config->getFilterFkManager()->extract($qb, $modelName, $alias, $vo);
        foreach ($filterFk->fks as $fk) {
            $join = $fk->join;
            $this->qbManager->tryLeftJoin(
                $join->join,
                $join->alias,
                $join->conditionType,
                $join->condition,
                $join->indexBy,
            );
            $alias .= "_$fk->fieldName";
        }
        $vo->key = $filterFk->field;

        return $this->createExpr($alias, $vo, $filterFk->type);
    }

    /**
     * @throws RansackException
     */
    protected function createExpr(string $alias, ParamFilterVO $paramVO, $type): mixed
    {
        $filterVO = new FilterVO();
        $filterVO->field = "$alias.$paramVO->key";
        $filterVO->type = $type;
        $filterVO->value = $paramVO->value;
        $expr = $this->config->getExprFactory()->get($paramVO->exprName);

        return $expr->create($this->qbManager->getQueryBuilder(), $filterVO);
    }

    protected function getField(string $modelName, string $field): ?string
    {
        $classMetadata = $this->qbManager
            ->getQueryBuilder()
            ->getEntityManager()
            ->getClassMetadata($modelName);
        if ($classMetadata->hasField($field)) {
            return $classMetadata->getFieldName($field);
        }

        return null;
    }

    protected function getTypeOfField(string $modelName, string $field): ?string
    {
        return $this->qbManager
            ->getQueryBuilder()
            ->getEntityManager()
            ->getClassMetadata($modelName)
            ->getTypeOfField($field);
    }

    protected function isBlank($value): bool
    {
        if ('0' == $value) {
            return false;
        }

        return empty($value);
    }
}
