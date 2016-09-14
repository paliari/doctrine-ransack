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

    /**
     * @var EntityManager
     */
    protected $_em;

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
    ];

    /**
     * @var string
     */
    protected $model;

    protected $alias = 't';

    /**
     * @var QueryBuilder
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
        return $this->_em;
    }

    /**
     * @param EntityManager $em
     *
     * @return $this
     */
    public function setEm($em)
    {
        $this->_em = $em;

        return $this;
    }

    /**
     * Create a Query Builder for model with ransack filters.
     *
     * @param string $model
     * @param array  $params
     *
     * @return QueryBuilder
     */
    public function query($model, $params = [])
    {
        $this->left_joins = [];
        $this->model      = $model;
        $this->qb         = $this->createQB($model);
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
            throw new DomainException('EntityManager cannot be null! Use this method setEM.');
        }

        return $this->getEm()->createQueryBuilder()->from($model, $this->alias)->select($this->alias);
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
        $this->tryLeftJoin($alias, $fk);
    }

    /**
     * @param string $parentAlias
     * @param string $fk
     *
     * @return $this
     */
    protected function tryLeftJoin($parentAlias, $fk)
    {
        $alias = $this->prepareAliasFK($parentAlias, $fk);
        if (!isset($this->left_joins[$alias])) {
            $this->qb->leftJoin("$parentAlias.$fk", $alias);
            $this->left_joins[$alias] = true;
        }

        return $this;
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
     * Add Filter in the QB.
     *
     * @param mixed $expr
     */
    protected function andWhere($expr)
    {
        $this->qb->andWhere($expr);
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
        } else {
            $key = "{$alias}_{$field}_$expr";
            if (in_array($method, ['in', 'notIn'])) {
                foreach ((array)$value as $k => $v) {
                    $value[$k] = $this->getEm()->getConnection()->convertToDatabaseValue($v, $type);
                }
                $this->qb->setParameter($key, $value);
            } else {
                $value = 'cont' == $expr ? "%$value%" : $value;
                $this->qb->setParameter($key, $value, $type);

            }

            return $this->qb->expr()->$method("$alias.$field", ":$key");
        }
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

        return null;
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
        if (!$this->getEm()->getClassMetadata($model)->hasField($field)) {
            throw new DomainException("Field '$model.$field' not found!");
        }

        return $this->getEm()->getClassMetadata($model)->getFieldName($field);
    }

}
