<?php

declare(strict_types=1);

abstract class RIOPartialResolver
{
    /**
     * @param RIOResolvedAction $action
     * @return RIOResolvedAction
     * @throws RIOResolveException
     */
    abstract public function resolve(RIOResolvedAction $action): RIOResolvedAction;
}
