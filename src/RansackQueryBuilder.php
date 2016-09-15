<?php
namespace Paliari\Doctrine;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;

class RansackQueryBuilder extends QueryBuilder
{

    protected $_joins = [];

    /**
     * @param EntityManager $em
     */
    public function __construct(EntityManager $em = null)
    {
        parent::__construct($em ?: Ransack::instance()->getEm());
    }

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
     * @param array $options
     *
     * @return $this
     */
    public function includes($options = [], $alias = 't')
    {
        $this->resetDQLPart('select');

        return $this->includeSelect($options, $alias);
    }

    /**
     * @param array  $options
     * @param string $alias
     *
     * @return $this
     */
    protected function includeSelect($options, $alias)
    {
        $this->addSelect($this->prepareIncludeSelect($alias, @$options['only']));
        if (isset($options['include'])) {
            foreach ($options['include'] as $k => $v) {
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

}
