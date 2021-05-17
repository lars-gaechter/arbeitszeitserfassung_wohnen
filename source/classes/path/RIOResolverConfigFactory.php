<?php

declare(strict_types=1);


class RIOResolverConfigFactory
{
    /**
     * @param RIOResolverStep[] $steps
     * @return RIOResolverConfig
     */
    public static function config(array $steps): RIOResolverConfig
    {
        return new RIOResolverConfig($steps);
    }

    /**
     * @param RIOPartialResolver[] $partial_resolvers
     * @return RIOResolverStep
     */
    public static function step(array $partial_resolvers): RIOResolverStep
    {
        return new RIOResolverStep($partial_resolvers);
    }
}
