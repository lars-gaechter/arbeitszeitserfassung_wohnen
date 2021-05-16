<?php

declare(strict_types=1);


class ResolverConfigFactory
{
    /**
     * @param ResolverStep[] $steps
     * @return ResolverConfig
     */
    public static function config(array $steps): ResolverConfig
    {
        return new ResolverConfig($steps);
    }

    /**
     * @param PartialResolver[] $partial_resolvers
     * @return ResolverStep
     */
    public static function step(array $partial_resolvers): ResolverStep
    {
        return new ResolverStep($partial_resolvers);
    }
}
