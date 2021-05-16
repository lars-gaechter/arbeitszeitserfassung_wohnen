<?php

declare(strict_types=1);

class ResolverConfig
{
    /**
     * @var ResolverStep[]
     */
    private array $steps;

    /**
     * @param ResolverStep[] $steps
     */
    public function __construct(array $steps)
    {
        $this->steps = $steps;
    }

    /**
     * @return ResolverStep[]
     */
    public function getSteps(): array
    {
        return $this->steps;
    }
}
