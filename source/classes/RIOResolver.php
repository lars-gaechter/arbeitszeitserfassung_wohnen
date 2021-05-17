<?php

declare(strict_types=1);

class RIOResolver
{
    private RIOResolverConfig $config;

    public function __construct(RIOResolverConfig $config)
    {
        $this->config = $config;
    }

    /**
     * @throws RIONotFoundException
     */
    public function resolveWithString(string $path): RIOResolvedAction
    {
        $path = $this->cleanPath($path);
        $splitter = new RIOSplitter($path);
        $cleaned_parts = $splitter->splitAt('/')->removeEmptyParts();
        $path_parts = $cleaned_parts->getParts();
        return $this->resolve($path_parts);
    }

    /**
     * @throws RIONotFoundException
     */
    public function resolveWithResolvedAction(RIOResolvedAction $resolved_action): RIOResolvedAction
    {
        /**
         * @var RIOResolveException
         */
        $lastError = null;

        foreach ($this->config->getSteps() as $step) {
            $solved = false;
            $lastSuccessfulAction = RIOResolvedAction::create($resolved_action);
            foreach ($step->getPartialResolvers() as $partialResolver) {
                $last_successful_action_copy = RIOResolvedAction::create(
                    $lastSuccessfulAction
                );
                try {
                    $resolved_action = $partialResolver->resolve($last_successful_action_copy);
                    $solved = true;
                    break;
                } catch (RIOResolveException $e) {
                    $lastError = $e;
                }
            }

            if (!$solved) {
                // 404
                throw new RIONotFoundException("RIOResolver didn't find it", 0, $lastError);
            }
        }

        return $resolved_action;
    }

    /**
     * @param string[] $path_parts
     * @return RIOResolvedAction
     * @throws RIONotFoundException
     */
    private function resolve(array $path_parts): RIOResolvedAction
    {
        $resolved_action = new RIOResolvedAction($path_parts);
        return $this->resolveWithResolvedAction($resolved_action);
    }


    private function cleanPath(string $path): string
    {
        // Remove get parameters
        $getRemoved = explode('?', $path, 2);

        return $getRemoved[0];
    }
}
