<?php

declare(strict_types=1);

class RIOResolverConfig
{
    /**
     * @var RIOResolverStep[]
     */
    private array $steps;

    /**
     * @param RIOResolverStep[] $steps
     */
    public function __construct(array $steps)
    {
        $this->steps = $steps;
    }

    /**
     * @return RIOResolverStep[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }
}
