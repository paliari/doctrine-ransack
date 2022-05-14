<?php

namespace Paliari\Doctrine\Helpers;

use Doctrine\ORM\QueryBuilder;
use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\VO\JoinVO;

class QueryBuilderHelper
{
    protected array $joins = [];

    public function __construct(
        protected EntityHelper $entityHelper,
        protected QueryBuilder $qb,
        protected string $alias = 't',
    )
    {
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->qb;
    }

    /**
     * alias to $qb->leftJoin to not duplicate in the same QueryBuilder
     */
    public function tryLeftJoin(
        JoinVO|string $join,
        string $alias = '',
        $conditionType = null,
        $condition = null,
        $indexBy = null,
    ): QueryBuilder
    {
        if ($join instanceof JoinVO) {
            $alias = $join->alias;
            $conditionType = $join->conditionType;
            $condition = $join->condition;
            $indexBy = $join->indexBy;
            $join = $join->join;
        }
        $key = implode('-', array_filter(['left', $join, $alias]));
        if (!isset($this->joins[$key])) {
            $this->qb->leftJoin($join, $alias, $conditionType, $condition, $indexBy);
            $this->joins[$key] = true;
        }

        return $this->qb;
    }

    /**
     * @throws RansackException
     */
    public function includes(array $includes = []): static
    {
        $this->qb->resetDQLPart('select');

        return $this->includeSelect($this->getRootEntity(), $includes, $this->alias);
    }

    protected function getRootEntity(): string
    {
        return $this->qb->getRootEntities()[0] ?? '';
    }

    /**
     * @throws RansackException
     */
    protected function includeSelect(string $entityName, array $includes, string $alias): static
    {
        $only = $includes['only'] ?? [];
        $this->qb->addSelect($this->prepareIncludeSelect($alias, $only));
        if (isset($includes['include'])) {
            foreach ($includes['include'] as $joinName => $joinIncludes) {
                if (is_string($joinIncludes)) {
                    $joinName = $joinIncludes;
                    $joinIncludes = [];
                }
                if ($relationVO = $this->entityHelper->getRelation($entityName, $alias, $joinName)) {
                    $this->tryLeftJoin($relationVO->join);
                    $this->includeSelect($relationVO->targetEntity, $joinIncludes, $relationVO->join->alias);
                } else {
                    throw new RansackException("Relation '$joinName' not found!");
                }
            }
        }

        return $this;
    }

    protected function prepareIncludeSelect(string $alias, array $only): string
    {
        if ($only) {
            if (false === array_search('id', $only)) {
                array_unshift($only, 'id');
            }
            $only = implode(', ', $only);

            return "partial $alias.{{$only}}";
        }

        return $alias;
    }
}
