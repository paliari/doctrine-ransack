<?php

namespace Paliari\Doctrine;

use Doctrine\ORM\QueryBuilder;
use JetBrains\PhpStorm\Pure;
use Paliari\Doctrine\Factories\QueryBuilderManagerFactory;
use Paliari\Doctrine\Factories\RansackBuilderFactory;

/**
 * Class Ransack
 * @package Paliari\Doctrine
 */
class Ransack
{
    protected QueryBuilderManagerFactory $managerFactory;
    protected RansackBuilderFactory $builderFactory;

    #[Pure]
    public function __construct(protected RansackConfig $config)
    {
        $this->managerFactory = new QueryBuilderManagerFactory();
        $this->builderFactory = new RansackBuilderFactory($this->config);
    }

    /**
     * Create a Query Builder for model with ransack filters.
     */
    public function query(QueryBuilder $qb, string $modelName, string $alias = 't'): RansackBuilder
    {
        $qbManager = $this->managerFactory->create($qb, $alias);

        return $this->builderFactory->create($qbManager, $modelName, $alias);
    }

    public function getConfig(): RansackConfig
    {
        return $this->config;
    }
}
