<?php

declare(strict_types=1);

abstract class PartialResolver
{
    /**
     * @param ResolvedAction $action
     * @return ResolvedAction
     * @throws ResolveException
     */
    abstract public function resolve(ResolvedAction $action): ResolvedAction;
}
