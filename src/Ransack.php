<?php

namespace Paliari\Doctrine;

use Doctrine\ORM\QueryBuilder;
use JetBrains\PhpStorm\Pure;
use Paliari\Doctrine\Factories\QueryBuilderHelperFactory;
use Paliari\Doctrine\Factories\RansackBuilderFactory;

/**
 * Class Ransack
 * @package Paliari\Doctrine
 */
class Ransack
{
    protected QueryBuilderHelperFactory $qbHelperFactory;
    protected RansackBuilderFactory $builderFactory;

    #[Pure]
    public function __construct(protected RansackConfig $config)
    {
        $this->qbHelperFactory = new QueryBuilderHelperFactory($this->config->getEntityHelper());
        $this->builderFactory = new RansackBuilderFactory($this->config);
    }

    /**
     * Create a Query Builder for model with ransack filters.
     */
    public function query(QueryBuilder $qb, string $entityName, string $alias = 't'): RansackBuilder
    {
        $qbHelper = $this->qbHelperFactory->create($qb, $alias);

        return $this->builderFactory->create($qbHelper, $entityName, $alias);
    }
}
