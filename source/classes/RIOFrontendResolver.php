<?php

declare(strict_types=1);

class RIOFrontendResolver extends RIOPartialResolver
{
    private string $default_frontend;
    /**
     * @var string[]
     */
    private array $frontends;

    /**
     * @param string[] $frontends
     */
    public function __construct(string $default_frontend, array $frontends)
    {
        $this->default_frontend = $default_frontend;
        $this->frontends = $frontends;
    }

    public function resolve(RIOResolvedAction $action): RIOResolvedAction
    {
        var_dump($action->getPathPartials());die();
        $possible_frontend = RIOMaybe::ofSettable($action->getPathPartials()[0]);

        if (!$possible_frontend->isEmpty()) {
            foreach ($this->frontends as $frontend) {
                if ($frontend == $possible_frontend->getValue()) {
                    // The frontend exists
                    $action->removeFirstArrayElement();
                    $action->setFrontend($possible_frontend);

                    return $action;
                }
            }
        }
        // The first part of the parts isn't the area name
        $action->setFrontend(RIOMaybe::of($this->default_frontend));

        return $action;
    }
}
