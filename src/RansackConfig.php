<?php

namespace Paliari\Doctrine;

use JetBrains\PhpStorm\Pure;
use Paliari\Doctrine\Expressions\FilterFkManager;
use Paliari\Doctrine\Expressions\ParamFilterParser;
use Paliari\Doctrine\Expressions\Where\ExprFactory;

class RansackConfig
{
    protected ExprFactory $exprFactory;
    protected ParamFilterParser $paramFilterParser;
    protected FilterFkManager $filterFkManager;

    #[Pure]
    public function __construct()
    {
        $this->exprFactory = new ExprFactory();
        $this->paramFilterParser = new ParamFilterParser();
        $this->filterFkManager = new FilterFkManager();
    }

    public function setCustomAssociation(?CustomAssociationInterface $customAssociation): static
    {
        $this->filterFkManager->setCustomAssociation($customAssociation);

        return $this;
    }

    public function getParamFilterParser(): ParamFilterParser
    {
        return $this->paramFilterParser;
    }

    public function getExprFactory(): ExprFactory
    {
        return $this->exprFactory;
    }

    public function getFilterFkManager(): FilterFkManager
    {
        return $this->filterFkManager;
    }
}
