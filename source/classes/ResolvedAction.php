<?php

declare(strict_types=1);

class ResolvedAction
{
    private array $path_partials;
    private Maybe $controller_namespace;
    private Maybe $controller_action;
    private Maybe $frontend;
    private Maybe $controller_action_parameters;

    public function __construct(array $path_partials)
    {
        $this->path_partials = $path_partials;
        $this->controller_namespace = Maybe::getEmpty();
        $this->controller_action = Maybe::getEmpty();
        $this->controller_action_parameters = Maybe::getEmpty();
        $this->frontend = Maybe::getEmpty();
    }

    public static function create(self $o): self
    {
        $obj = new self($o->getPathPartials());
        $obj->setControllerNamespace(clone $o->getControllerNamespace());
        $obj->setControllerAction(clone $o->getControllerAction());
        $obj->setControllerActionParameters(clone $o->getControllerActionParameters());
        $obj->setFrontend(clone $o->getFrontend());

        return $obj;
    }

    public function removeFirstArrayElement(): void
    {
        if (!isset($this->path_partials[0])) {
            throw new Error("Trying to remove path partial which doesn't exist");
        }
        unset($this->path_partials[0]);
        $this->path_partials = array_values($this->path_partials);
    }

    public function getPathPartials(): array
    {
        return $this->path_partials;
    }

    public function setPathPartials(array $path_partials): void
    {
        $this->path_partials = $path_partials;
    }

    public function getControllerNamespace(): Maybe
    {
        return $this->controller_namespace;
    }

    public function setControllerNamespace(Maybe $controller_namespace): void
    {
        $this->controller_namespace = $controller_namespace;
    }

    public function getControllerAction(): Maybe
    {
        return $this->controller_action;
    }

    public function setControllerAction(Maybe $controller_action): void
    {
        $this->controller_action = $controller_action;
    }

    public function getControllerActionParameters(): Maybe
    {
        return $this->controller_action_parameters;
    }

    public function setControllerActionParameters(Maybe $controller_action_parameters): void
    {
        $this->controller_action_parameters = $controller_action_parameters;
    }

    public function getFrontend(): Maybe
    {
        return $this->frontend;
    }

    public function setFrontend(Maybe $frontend): void
    {
        $this->frontend = $frontend;
    }
}
