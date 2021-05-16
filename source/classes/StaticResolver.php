<?php

declare(strict_types=1);

class StaticResolver extends PartialResolver
{
    private array $static_routes;

    public function __construct(array $static_routes)
    {
        $this->static_routes = $static_routes;
    }

    /**
     * @throws ResolveException
     */
    public function resolve(ResolvedAction $action): ResolvedAction
    {
        foreach ($this->static_routes as $route) {
            $matches = true;
            // Check for empty route
            // Empty routes can't have parameters
            $matched_part_count = 0;
            if (empty($route[0])) {
                if (!empty($action->getPathPartials())) {
                    foreach ($action->getPathPartials() as $part) {
                        if (!empty($part)) {
                            $matches = false;
                        }
                    }
                }
            } else {
                foreach ($route[0] as $key => $route_part) {
                    if ('*' !== $route_part) {
                        ++$matched_part_count;
                        if (!isset($action->getPathPartials()[$key])) {
                            $matches = false;
                            break;
                        }
                        if (
                            $route_part != $action->getPathPartials()[$key]
                        ) {
                            $matches = false;
                            break;
                        }
                    }
                }
            }
            if ($matches) {
                for ($i = 0; $i < $matched_part_count; ++$i) {
                    $action->removeFirstArrayElement();
                }
                $conf = $route[1];
                $namespace = $conf['controller_namespace'];
                $method = $conf['controller_method'];
                $action->setControllerNamespace(Maybe::of($namespace));
                $action->setControllerAction(Maybe::of($method));
                $action->setControllerActionParameters(Maybe::of($action->getPathPartials()));
                return $action;
            }
        }
        throw new ResolveException("Didn't find the specified page");
    }
}
