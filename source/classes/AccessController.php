<?php

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

class AccessController extends GeneralAccessController
{
    private static array $renderStack = [];
    private string $template = "templates";
    private static bool $rendering = false;

    public function __construct(
        string $directoryNamespace,
        Environment $twig,
        Request $request = null
    ) {
        parent::__construct($directoryNamespace, $twig, $request);
    }

    public static function elevate(callable $function, bool $ajax = false)
    {
        self::$renderStack[] = [
            'rendering' => self::$rendering,
            'ajax' => $ajax,
        ];
        self::$rendering = false;
        call_user_func($function);
        self::$rendering = self::getCurrentRenderFrame()['rendering'];
        array_pop(self::$renderStack);
    }

    private static function getCurrentRenderFrame()
    {
        return self::$renderStack[count(self::$renderStack) - 1];
    }

    protected function renderPage(string $name, array $context = []): Response
    {
        return $this->render(
            strtolower($this->getViewPath()).$name,
            array_merge(
                [
                    'title' => $_ENV['APP_NAME'],
                ],
                $context
            )
        );
    }

    protected function render(string $name, array $context = []): Response
    {
        $raw = $this->rawRender($name, $context);

        return $this->packResponse($raw);
    }

    protected function getViewPath(): string
    {
        $cleanParts = $this->getViewPathParts();

        return 'modules/'.implode('/', $cleanParts).'/';
    }

    public function rawRender(string $name, array $context = []): string
    {
        $context = array_merge($context, [
            'view_base' => strtolower($this->getViewPath()),
            'view_base_parts' => $this->getViewPathParts(),
            'access_controller' => $this,
        ]);
        if (true !== self::$rendering && false === $this->isAjax()) {
            // template is set by the area
            $context = array_merge($context, [
                'template' => $this->template,
            ]);
        }
        return $this->renderTwig($name, $context);
    }

    private function renderTwig(string $name, array $context = []): string
    {
        if (false === self::$rendering) {
            self::$rendering = true;
            $response = $this->twigRender($name, $context);
            self::$rendering = false;
        } else {
            $response = $this->twigRender($name, $context);
        }

        return $response;
    }

    private function twigRender(string $name, array $context = []): string
    {
        return $this->twig()->render(
            $name,
            $context
        );
    }

    protected function packResponse(string $raw): Response
    {
        return $this->packResponseWithType($raw, 'html');
    }

    protected function packResponseWithType(string $raw, string $type): Response
    {
        if ($this->isAjax() && false === self::$rendering) {
            return new JsonResponse([
                'content' => $raw,
                'type' => $type,
            ]);
        } else {
            return new Response($raw);
        }
    }

    /**
     * @return string[]
     */
    protected function getViewPathParts(): array
    {
        $dirSpace = $this->getDirectoryNamespaceString();
        $parts = explode('\\', $dirSpace);
        return array_filter(
            $parts,
            function ($value) {
                return
                    ('' !== $value) &&
                    ('modules' !== $value);
            }
        );
    }

    private function isAjax(): bool
    {
        if (self::isElevated()) {
            return self::getCurrentRenderFrame()['ajax'];
        } else {
            $request = $this->getRequest();

            return (!$request->isMethod(Request::METHOD_GET)) && $request->headers->contains('X-Requested-With', 'XMLHttpRequest');
        }
    }

    private static function isElevated(): bool
    {
        return 0 !== count(self::$renderStack);
    }

}