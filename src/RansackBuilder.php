<?php

namespace Paliari\Doctrine;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use JetBrains\PhpStorm\Pure;
use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\Factories\RansackFilterFactory;
use Paliari\Doctrine\VO\WhereParamsVO;

class RansackBuilder
{
    protected RansackFilterFactory $filterFactory;

    #[Pure]
    public function __construct(
        protected RansackConfig $config,
        protected QueryBuilderManger $qbManager,
        protected string $modelName,
        protected string $alias = 't',
    )
    {
        $this->filterFactory = new RansackFilterFactory($this->config);
    }

    public function includes(array $includes = []): static
    {
        $this->qbManager->includes($includes);

        return $this;
    }

    /**
     * @throws RansackException
     */
    public function where(WhereParamsVO $paramsVO): static
    {
        $this->filterFactory
            ->create($this->qbManager, $this->modelName, $this->alias)
            ->apply($paramsVO);

        return $this;
    }

    public function getConfig(): RansackConfig
    {
        return $this->config;
    }

    public function getQbManager(): QueryBuilderManger
    {
        return $this->qbManager;
    }

    #[Pure]
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->qbManager->getQueryBuilder();
    }

    public function getQuery(): Query
    {
        return $this->getQueryBuilder()->getQuery();
    }
}
