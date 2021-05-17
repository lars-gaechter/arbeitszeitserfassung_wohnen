<?php

declare(strict_types=1);

class RIOResolverStep
{
    /**
     * @var RIOPartialResolver[]
     */
    private array $partial_resolvers;

    /**
     * @param RIOPartialResolver[] $partial_resolvers
     */
    public function __construct(array $partial_resolvers)
    {
        $this->partial_resolvers = $partial_resolvers;
    }

    /**
     * @return RIOPartialResolver[]
     */
    public function getPartialResolvers(): array
    {
        return $this->partial_resolvers;
    }
}
