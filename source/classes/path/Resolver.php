<?php

declare(strict_types=1);

class Resolver
{
    private ResolverConfig $config;

    public function __construct(ResolverConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @throws NotFoundException
     */
    public function resolveWithString(string $path): ResolvedAction
    {
        $path = $this->cleanPath($path);
        $splitter = new RIOSplitter($path);
        $cleaned_parts = $splitter->splitAt('/')->removeEmptyParts();
        $path_parts = $cleaned_parts->getParts();
        return $this->resolve($path_parts);
    }

    /**
     * @throws NotFoundException
     */
    public function resolveWithResolvedAction(ResolvedAction $resolved_action): ResolvedAction
    {
        /**
         * @var ResolveException
         */
        $lastError = null;

        foreach ($this->config->getSteps() as $step) {
            $solved = false;
            $lastSuccessfulAction = ResolvedAction::create($resolved_action);
            foreach ($step->getPartialResolvers() as $partialResolver) {
                $last_successful_action_copy = ResolvedAction::create(
                    $lastSuccessfulAction
                );
                try {
                    $resolved_action = $partialResolver->resolve($last_successful_action_copy);
                    $solved = true;
                    break;
                } catch (ResolveException $e) {
                    $lastError = $e;
                }
            }

            if (!$solved) {
                // 404
                throw new NotFoundException("Resolver didn't find it", 0, $lastError);
            }
        }

        return $resolved_action;
    }

    /**
     * @param string[] $path_parts
     * @return ResolvedAction
     * @throws NotFoundException
     */
    private function resolve(array $path_parts): ResolvedAction
    {
        $resolved_action = new ResolvedAction($path_parts);
        return $this->resolveWithResolvedAction($resolved_action);
    }


    private function cleanPath(string $path): string
    {
        // Remove get parameters
        $getRemoved = explode('?', $path, 2);

        return $getRemoved[0];
    }
}
