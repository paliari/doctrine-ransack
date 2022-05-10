<?php

namespace Paliari\Doctrine;

use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\Expressions\Operations\GroupByExpr;
use Paliari\Doctrine\Expressions\Operations\OrderByExpr;
use Paliari\Doctrine\VO\FilterVO;
use Paliari\Doctrine\VO\ParamFilterVO;
use Paliari\Doctrine\VO\RansackOrderByVO;
use Paliari\Doctrine\VO\RansackParamsVO;

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
    public function apply(RansackParamsVO $paramsVO): QueryBuilderManger
    {
        foreach ($this->where($paramsVO->where) as $filter) {
            $this->qbManager->getQueryBuilder()->andWhere($filter);
        }
        $this->groupBy($paramsVO->groupBy);
        $this->orderBy($paramsVO->orderBy);

        return $this->qbManager;
    }

    /**
     * @throws RansackException
     */
    protected function where(array $where): array
    {
        $qb = $this->qbManager->getQueryBuilder();
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
            $this->filter($this->modelName, $paramFilterVO);
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
            $this->filter($this->modelName, $paramFilterVO);
        }
    }

    /**
     * @throws RansackException
     */
    protected function parseFilter(string $filterKey, $filterValue)
    {
        $paramFilterVO = $this->config->getParamFilterParser()->parse($filterKey, $filterValue);
        $keys = explode('_or_', $paramFilterVO->key);
        if (count($keys) > 1) {
            $args = [];
            foreach ($keys as $key) {
                $vo = new ParamFilterVO($paramFilterVO->toArray());
                $vo->key = $key;
                $args[] = $this->filter($this->modelName, $vo);
            }

            return call_user_func_array([$this->qbManager->getQueryBuilder()->expr(), 'orX'], $args);
        }

        return $this->filter($this->modelName, $paramFilterVO);
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
