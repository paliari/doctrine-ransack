<?php

namespace Paliari\Doctrine;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use JetBrains\PhpStorm\Pure;
use Paliari\Doctrine\Exceptions\RansackException;
use Paliari\Doctrine\Factories\RansackFilterHelperFactory;
use Paliari\Doctrine\Helpers\QueryBuilderHelper;
use Paliari\Doctrine\VO\RansackParamsVO;

class RansackBuilder
{
    protected RansackFilterHelperFactory $filterHelperFactory;

    #[Pure]
    public function __construct(
        protected RansackConfig $config,
        protected QueryBuilderHelper $qbHelper,
        protected string $entityName,
        protected string $alias = 't',
    )
    {
        $this->filterHelperFactory = new RansackFilterHelperFactory($this->config);
    }

    public function includes(array $includes = []): static
    {
        $this->getQbHelper()->includes($includes);

        return $this;
    }

    /**
     * @throws RansackException
     */
    public function where(RansackParamsVO $paramsVO): static
    {
        $this->filterHelperFactory
            ->create($this->qbHelper, $this->entityName, $this->alias)
            ->apply($paramsVO);

        return $this;
    }

    public function getQbHelper(): QueryBuilderHelper
    {
        return $this->qbHelper;
    }

    #[Pure]
    public function getQueryBuilder(): QueryBuilder
    {
        return $this->getQbHelper()->getQueryBuilder();
    }

    public function getQuery(): Query
    {
        return $this->getQueryBuilder()->getQuery();
    }
}
