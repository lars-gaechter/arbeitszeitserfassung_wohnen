<?php

declare(strict_types=1);

class RIOResolvedAction
{
    private array $path_partials;
    private RIOMaybe $controller_namespace;
    private RIOMaybe $controller_action;
    private RIOMaybe $frontend;
    private RIOMaybe $controller_action_parameters;

    public function __construct(array $path_partials)
    {
        $this->path_partials = $path_partials;
        $this->controller_namespace = RIOMaybe::getEmpty();
        $this->controller_action = RIOMaybe::getEmpty();
        $this->controller_action_parameters = RIOMaybe::getEmpty();
        $this->frontend = RIOMaybe::getEmpty();
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

    public function getControllerNamespace(): RIOMaybe
    {
        return $this->controller_namespace;
    }

    public function setControllerNamespace(RIOMaybe $controller_namespace): void
    {
        $this->controller_namespace = $controller_namespace;
    }

    public function getControllerAction(): RIOMaybe
    {
        return $this->controller_action;
    }

    public function setControllerAction(RIOMaybe $controller_action): void
    {
        $this->controller_action = $controller_action;
    }

    public function getControllerActionParameters(): RIOMaybe
    {
        return $this->controller_action_parameters;
    }

    public function setControllerActionParameters(RIOMaybe $controller_action_parameters): void
    {
        $this->controller_action_parameters = $controller_action_parameters;
    }

    public function getFrontend(): RIOMaybe
    {
        return $this->frontend;
    }

    public function setFrontend(RIOMaybe $frontend): void
    {
        $this->frontend = $frontend;
    }
}
