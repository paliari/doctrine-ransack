<?php

namespace Paliari\Doctrine\Helpers;

use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\Expressions\Operations\GroupByExpr;
use Paliari\Doctrine\Expressions\Operations\OrderByExpr;
use Paliari\Doctrine\RansackConfig;
use Paliari\Doctrine\VO\FilterVO;
use Paliari\Doctrine\VO\ParamFilterVO;
use Paliari\Doctrine\VO\RansackOrderByVO;
use Paliari\Doctrine\VO\RansackParamsVO;

class RansackFilterHelper
{
    public function __construct(
        protected RansackConfig $config,
        protected QueryBuilderHelper $qbHelper,
        protected string $entityName,
        protected string $alias = 't',
    )
    {
    }

    /**
     * @throws RansackException
     */
    public function apply(RansackParamsVO $paramsVO): QueryBuilderHelper
    {
        foreach ($this->where($paramsVO->where) as $filter) {
            $this->qbHelper->getQueryBuilder()->andWhere($filter);
        }
        $this->groupBy($paramsVO->groupBy);
        $this->orderBy($paramsVO->orderBy);

        return $this->qbHelper;
    }

    /**
     * @throws RansackException
     */
    protected function where(array $where): array
    {
        $qb = $this->qbHelper->getQueryBuilder();
        $filters = [];
        foreach ($where as $k => $v) {
            if (!$this->isBlank($v) || preg_match('/_null$/', $k)) {
                if (in_array($k, ['or', 'and'])) {
                    $methodName = $k . 'X'; // orX | andX
                    $args = $this->where($v);
                    $filters[] = call_user_func_array([$qb->expr(), $methodName], $args);
                } elseif ($expr = $this->parseFilter($k, $v)) {
                    $filters[] = $expr;
                }
            }
        }

        return $filters;
    }

    /**
     * @throws RansackException
     */
    protected function groupBy(array $groupBy): void
    {
        foreach ($groupBy as $key) {
            $paramFilterVO = new ParamFilterVO();
            $paramFilterVO->key = $key;
            $paramFilterVO->exprName = GroupByExpr::NAME;
            $this->filter($this->entityName, $paramFilterVO);
        }
    }

    /**
     * @param RansackOrderByVO[] $orderBy
     *
     * @throws RansackException
     */
    protected function orderBy(array $orderBy)
    {
        foreach ($orderBy as $vo) {
            $paramFilterVO = new ParamFilterVO();
            $paramFilterVO->key = $vo->field;
            $paramFilterVO->value = $vo->order;
            $paramFilterVO->exprName = OrderByExpr::NAME;
            $this->filter($this->entityName, $paramFilterVO);
        }
    }

    /**
     * @throws RansackException
     */
    protected function parseFilter(string $filterKey, $filterValue)
    {
        $paramFilterVO = $this->config->getParamExprParser()->parse($filterKey, $filterValue);
        $keys = explode('_or_', $paramFilterVO->key);
        if (count($keys) > 1) {
            $args = [];
            foreach ($keys as $key) {
                $vo = new ParamFilterVO($paramFilterVO->toArray());
                $vo->key = $key;
                $args[] = $this->filter($this->entityName, $vo);
            }

            return call_user_func_array([$this->qbHelper->getQueryBuilder()->expr(), 'orX'], $args);
        }

        return $this->filter($this->entityName, $paramFilterVO);
    }

    /**
     * @throws RansackException
     */
    protected function filter(string $entityName, ParamFilterVO $vo)
    {
        if ($field = $this->config->getEntityHelper()->getField($entityName, $vo->key)) {
            $vo->key = $field;
            $type = $this->config->getEntityHelper()->getTypeOfField($entityName, $field);

            return $this->createExpr($this->alias, $vo, $type);
        } else {
            return $this->filtersRelations($entityName, $vo);
        }
    }

    /**
     * @throws RansackException
     */
    protected function filtersRelations(string $entityName, ParamFilterVO $vo)
    {
        $alias = $this->alias;
        $filterRelationVO = $this->config->getExprRelationManager()->extract($entityName, $alias, $vo);
        foreach ($filterRelationVO->relations as $relationVO) {
            $join = $relationVO->join;
            $this->qbHelper->tryLeftJoin(
                $join->join,
                $join->alias,
                $join->conditionType,
                $join->condition,
                $join->indexBy,
            );
            $alias .= "_$relationVO->fieldName";
        }
        $vo->key = $filterRelationVO->field;

        return $this->createExpr($alias, $vo, $filterRelationVO->type);
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

        return $expr->create($this->qbHelper->getQueryBuilder(), $filterVO);
    }

    protected function isBlank($value): bool
    {
        if ('0' == $value) {
            return false;
        }

        return empty($value);
    }
}
