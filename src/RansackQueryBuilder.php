<?php

namespace Paliari\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

/**
 * Class RansackQueryBuilder
 *
 * @method RansackQueryBuilder select(mixed $select = null)
 * @method RansackQueryBuilder addSelect(mixed $select = null)
 * @method RansackQueryBuilder where(mixed $predicates)
 * @method RansackQueryBuilder from(string $from, string $alias, string $indexBy = null)
 * @method RansackQueryBuilder join(string $join, string $alias, string $conditionType = null, string $condition = null, string $indexBy = null)
 * @method RansackQueryBuilder innerJoin(string $join, string $alias, string $conditionType = null, string $condition = null, string $indexBy = null)
 * @method RansackQueryBuilder leftJoin(string $join, string $alias, string $conditionType = null, string $condition = null, string $indexBy = null)
 * @method RansackQueryBuilder andWhere(mixed $where)
 * @method RansackQueryBuilder orWhere(mixed $where)
 * @method RansackQueryBuilder groupBy(mixed $groupBy)
 * @method RansackQueryBuilder addGroupBy(mixed $groupBy)
 * @method RansackQueryBuilder having(mixed $having)
 * @method RansackQueryBuilder andHaving(mixed $having)
 * @method RansackQueryBuilder orHaving(mixed $having)
 * @method RansackQueryBuilder orderBy(string $sort, string $order = null)
 * @method RansackQueryBuilder addOrderBy(string $sort, string $order = null)
 * @method RansackQueryBuilder setParameter(string $key, mixed $value, string $type = null)
 * @method RansackQueryBuilder setParameters(mixed $parameters)
 * @method RansackQueryBuilder setFirstResult(integer $firstResult)
 * @method RansackQueryBuilder setMaxResults(integer $maxResults)
 * @method RansackQueryBuilder distinct(bool $flag)
 * @method RansackQueryBuilder delete(string $delete = null, string $alias = null)
 * @method RansackQueryBuilder update(string $update = null, string $alias = null)
 * @method RansackQueryBuilder set(string $key, string $value)
 * @method RansackQueryBuilder add(string $dqlPartName, string $dqlPart, bool $append = false)
 *
 * @package Paliari\Doctrine
 */
class RansackQueryBuilder extends QueryBuilder
{

    protected $_joins = [];

    /**
     * @param EntityManager $em
     * @param string        $model_name
     * @param string        $alias
     *
     * @return RansackQueryBuilder
     */
    public static function create($em, $model_name, $alias = 't')
    {
        $qb = new static($em);
        $qb->from($model_name, $alias);

        return $qb;
    }

    /**
     * @param string $parentAlias
     * @param string $fk
     *
     * @return $this
     */
    public function tryLeftJoin($parentAlias, $fk)
    {
        $join  = "$parentAlias.$fk";
        $alias = $this->prepareAliasFK($parentAlias, $fk);
        $key   = "left-$join-$alias";
        if (!isset($this->_joins[$key])) {
            $this->leftJoin($join, $alias);
            $this->_joins[$key] = true;
        }

        return $this;
    }

    /**
     * Adiciona os selects com os partials e os respectivos joins, usado pra arrayResult.
     *
     * @param array  $includes
     *
     * @param string $alias
     *
     * @return $this
     */
    public function includes($includes = [], $alias = 't')
    {
        $this->resetDQLPart('select');

        return $this->includeSelect($includes, $alias);
    }

    /**
     * @param array  $includes
     * @param string $alias
     *
     * @return $this
     */
    protected function includeSelect($includes, $alias)
    {
        $only = isset($includes['only']) ? $includes['only'] : null;
        $this->addSelect($this->prepareIncludeSelect($alias, $only));
        if (isset($includes['include'])) {
            foreach ($includes['include'] as $k => $v) {
                $is_array = is_array($v);
                $fk       = $is_array ? $k : $v;
                $this->tryLeftJoin($alias, $fk);
                $this->includeSelect($is_array ? $v : [], $this->prepareAliasFK($alias, $fk));
            }
        }

        return $this;
    }

    /**
     * @param string $alias
     * @param array  $only
     *
     * @return string
     */
    protected function prepareIncludeSelect($alias, $only)
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

    /**
     * @param string $parentAlias
     * @param string $fk
     *
     * @return string
     */
    protected function prepareAliasFK($parentAlias, $fk)
    {
        return "{$parentAlias}_$fk";
    }

    /**
     * @param array $includes
     *
     * @return array
     */
    public function asJson($includes = [])
    {
        if ($includes) {
            $this->includes($includes);
        }

        return $this->getQuery()->getArrayResult();
    }

    /**
     * Alias $this->getQuery()->getArrayResult()
     *
     * @param array $includes
     *
     * @return array
     */
    public function getArrayResult($includes = [])
    {
        if ($includes) {
            $this->includes($includes);
        }

        return $this->getQuery()->getArrayResult();
    }

    /**
     * Alias to $this->getQuery()->getResult()
     *
     * @return array
     */
    public function getResult()
    {
        return $this->getQuery()->getResult();
    }

    /**
     * @return object|null
     */
    public function first()
    {
        $rows = $this->setMaxResults(1)->getResult();

        return isset($rows[0]) ? $rows[0] : null;
    }

}
