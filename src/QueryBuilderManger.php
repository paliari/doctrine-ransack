<?php

namespace Paliari\Doctrine;

use Doctrine\ORM\QueryBuilder;

class QueryBuilderManger
{
    protected array $joins = [];

    public function __construct(protected QueryBuilder $qb, protected string $alias = 't')
    {
    }

    public function getQueryBuilder(): QueryBuilder
    {
        return $this->qb;
    }

    public function tryLeftJoin($join, $alias, $conditionType = null, $condition = null, $indexBy = null): QueryBuilder
    {
        $key = implode('-', array_filter(['left', $join, $alias, $conditionType, $condition, $indexBy]));
        if (!isset($this->joins[$key])) {
            $this->qb->leftJoin($join, $alias, $conditionType, $condition, $indexBy);
            $this->joins[$key] = true;
        }

        return $this->qb;
    }

    public function includes(array $includes = []): static
    {
        $this->qb->resetDQLPart('select');

        return $this->includeSelect($includes, $this->alias);
    }

    protected function includeSelect(array $includes, string $alias): static
    {
        $only = $includes['only'] ?? [];
        $this->qb->addSelect($this->prepareIncludeSelect($alias, $only));
        if (isset($includes['include'])) {
            foreach ($includes['include'] as $k => $v) {
                $isArray = is_array($v);
                $fk = $isArray ? $k : $v;
                $this->tryLeftJoin("$alias.$fk", "{$alias}_$fk");
                $this->includeSelect($isArray ? $v : [], "{$alias}_$fk");
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
