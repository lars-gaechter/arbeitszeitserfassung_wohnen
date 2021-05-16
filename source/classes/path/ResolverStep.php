<?php

declare(strict_types=1);

class ResolverStep
{
    /**
     * @var PartialResolver[]
     */
    private array $partial_resolvers;

    /**
     * @param PartialResolver[] $partial_resolvers
     */
    public function __construct(array $partial_resolvers)
    {
        $this->partial_resolvers = $partial_resolvers;
    }

    /**
     * @return PartialResolver[]
     */
    public function getPartialResolvers(): array
    {
        return $this->partial_resolvers;
    }
}
