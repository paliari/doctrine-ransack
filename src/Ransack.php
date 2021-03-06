<?php

namespace Paliari\Doctrine;

use Doctrine\ORM\EntityManager,
    Doctrine\ORM\QueryBuilder,
    DomainException;

/**
 * Class Ransack
 * @package Paliari\Doctrine
 */
class Ransack
{

    protected $pattern;

    protected $expr = [
        'not_eq'   => 'neq',
        'not_in'   => 'notIn',
        'not_null' => 'isNotNull',
        'eq'       => 'eq',
        'lt'       => 'lt',
        'lteq'     => 'lte',
        'gt'       => 'gt',
        'gteq'     => 'gte',
        'in'       => 'in',
        'null'     => 'isNull',
        'matches'  => 'like',
        'cont'     => 'like',
        'start'    => 'like',
        'end'      => 'like',
        'order_by' => 'addOrderBy',
        'group_by' => 'addGroupBy',
    ];

    /**
     * @var string
     */
    protected $model;

    protected $alias = 't';

    /**
     * @var RansackQueryBuilder
     */
    protected $qb;

    protected $left_joins = [];

    private static $_instance;

    /**
     * @return static
     */
    public static function instance()
    {
        return static::$_instance = static::$_instance ?: new static();
    }

    public function __construct()
    {
        $this->pattern = '!([\w\.]+?)_(' . implode('|', array_keys($this->expr)) . ')$!';
    }

    /**
     * @return EntityManager
     */
    public function getEm()
    {
        return $this->qb->getEntityManager();
    }

    /**
     * Create a Query Builder for model with ransack filters.
     *
     * @param string              $model
     * @param array               $params
     * @param RansackQueryBuilder $qb
     *
     * @return RansackQueryBuilder
     */
    public function query($qb, $model, $params = [])
    {
        $this->left_joins = [];
        $this->model      = $model;
        $this->qb         = $qb;
        $this->filters($params);

        return $this->qb;
    }

    /**
     * @param string $model
     *
     * @return QueryBuilder
     */
    protected function createQB($model)
    {
        if (!$this->getEm()) {
            throw new DomainException('EntityManager cannot be null! Use the method Ransack::setEm($em).');
        }

        return RansackQueryBuilder::create($this->getEm(), $model, $this->alias);
    }

    /**
     * Add filtros.
     *
     * @param array $params
     */
    protected function filters($params)
    {
        $model = $this->model;
        foreach ($params as $k => $v) {
            if (!$this->blank($v) || preg_match('/_null$/', $k)) {
                list($field, $expr) = $this->extractField($k);
                $fields = explode('_or_', $field);
                if (count($fields) > 1) {
                    $args = [];
                    foreach ($fields as $i => $field) {
                        $args[] = $this->filter($model, $field, $expr, $v);
                    }
                    $this->andWhere(call_user_func_array([$this->qb->expr(), 'orX'], $args));
                } else {
                    $this->andWhere($this->filter($model, $field, $expr, $v));
                }
            }
        }
    }

    /**
     * Create expr filter.
     *
     * @param string $model
     * @param string $field
     * @param string $expr
     * @param mixed  $value
     *
     * @return mixed
     */
    protected function filter($model, $field, $expr, $value)
    {
        if ($_field = $this->getField($model, $field)) {
            $field = $_field;

            return $this->createExpr($this->alias, $field, $expr, $value, $this->getTypeOfField($model, $field));
        } else {
            return $this->filtersFks($field, $expr, $value);
        }
    }

    /**
     * @param string $model
     * @param string $field
     *
     * @return \Doctrine\DBAL\Types\Type|null|string
     */
    protected function getTypeOfField($model, $field)
    {
        return $this->getEm()->getClassMetadata($model)->getTypeOfField($field);
    }

    /**
     * @param string $field
     * @param string $expr
     * @param mixed  $value
     *
     * @return mixed
     */
    protected function filtersFks($field, $expr, $value)
    {
        list($fks, $field, $type) = $this->extractFks($field);
        $alias = $this->alias;
        foreach ($fks as $i => $fk) {
            $this->leftJoin($alias, $fk);
            $alias .= "_$fk";
        }

        return $this->createExpr($alias, $field, $expr, $value, $type);
    }

    /**
     * Add left join in the QB.
     *
     * @param string $alias
     * @param string $fk
     */
    protected function leftJoin($alias, $fk)
    {
        $this->qb->tryLeftJoin($alias, $fk);
    }

    /**
     * Add Filter in the QB.
     *
     * @param mixed $expr
     */
    protected function andWhere($expr)
    {
        if ($expr) {
            $this->qb->andWhere($expr);
        }
    }

    /**
     * @param string $alias
     * @param string $field
     * @param string $expr
     * @param mixed  $value
     * @param string $type
     *
     * @return mixed
     */
    protected function createExpr($alias, $field, $expr, $value, $type)
    {
        $method = $this->expr[$expr];
        if (in_array($method, ['isNotNull', 'isNull'])) {
            return $this->qb->expr()->$method("$alias.$field");
        } elseif ('order_by' == $expr) {
            $this->qb->addOrderBy("$alias.$field", $value);
        } elseif ('group_by' == $expr) {
            $this->qb->addGroupBy("$alias.$field");
        } else {
            $key = "{$alias}_{$field}_$expr";
            if (in_array($method, ['in', 'notIn'])) {
                foreach ((array)$value as $k => $v) {
                    $value[$k] = $this->getEm()->getConnection()->convertToDatabaseValue($v, $type);
                }
                $this->qb->setParameter($key, $value);
            } else {
                if ('cont' == $expr) $value = "%$value%";
                if ('start' == $expr) $value = "$value%";
                if ('end' == $expr) $value = "%$value";
                $this->qb->setParameter($key, $value, $type);
            }

            return $this->qb->expr()->$method("$alias.$field", ":$key");
        }

        return null;
    }

    /**
     * @param string $model
     * @param string $fk
     *
     * @return string
     */
    protected function getTargetEntity($model, $fk)
    {
        return @$this->getEm()->getClassMetadata($model)->getAssociationMappings()[$fk]['targetEntity'];
    }

    /**
     * Separa a coluna do filtro.
     *
     * @param string $key
     *
     * @return array [$field, $expr]
     */
    protected function extractField($key)
    {
        preg_match($this->pattern, $key, $match);
        @list($all, $field, $expr) = $match;
        if (!$field || !$expr) {
            throw new DomainException("Condition '$key' not found!");
        }

        return [$field, $expr];
    }

    /**
     * Separa as fks da coluna.
     *
     * @param string $field
     *
     * @return array [$fks, $field, $type]
     */
    protected function extractFks($field)
    {
        return strpos($field, '.') ? $this->extractFksPoint($field) : $this->extractFksUnderline($field);
    }

    /**
     * @param string $field
     *
     * @return array [$fks, $field, $type]
     */
    protected function extractFksPoint($field)
    {
        $model = $this->model;
        $fks   = explode('.', $field);
        $field = end($fks);
        $fks   = array_slice($fks, 0, -1);
        foreach ($fks as $k => $fk) {
            if ($model = $this->getTargetEntity($model, $fk)) {
            } else {
                throw new DomainException("Target Model '$fk' not found!");
            }
        }
        $field = $this->getField($model, $field);

        return [$fks, $field, $this->getTypeOfField($model, $field)];
    }

    /**
     * @param string $field
     *
     * @return array [$fks, $field, $type]
     */
    protected function extractFksUnderline($field)
    {
        $model = $this->model;
        $keys  = $fks = [];
        $a     = explode('_', $field);
        foreach ($a as $i => $key) {
            $keys[] = $key;
            $fk     = implode('_', $keys);
            if ($target = $this->getTargetEntity($model, $fk)) {
                $fks[] = $fk;
                $model = $target;
                $field = implode('_', array_slice($a, $i + 1));
                if ($_field = $this->getField($model, $field)) {
                    $field = $_field;

                    return [$fks, $field, $this->getTypeOfField($model, $field)];
                }
                $keys = [];
            }
        }
        throw new DomainException("Field '$field' not found!");
    }

    /**
     * @param mixed $value
     *
     * @return bool
     */
    protected function blank($value)
    {
        if ('0' == $value) {
            return false;
        }

        return empty($value);
    }

    /**
     * @param string $model
     * @param string $field
     *
     * @return string
     */
    protected function getField($model, $field)
    {
        if ($this->getEm()->getClassMetadata($model)->hasField($field)) {
            return $this->getEm()->getClassMetadata($model)->getFieldName($field);
        }

        return null;
    }

}
