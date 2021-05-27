<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Twig\Environment;
use Twig\Extension\DebugExtension;
use Twig\Loader\ChainLoader;
use Twig\Loader\FilesystemLoader;

final class RIOAreaSpecificRIOTwigProvider implements RIOTwigProvider
{
    /**
     * instance
     *
     * @var null|RIOAreaSpecificRIOTwigProvider
     */
    protected static ?RIOAreaSpecificRIOTwigProvider $_instance = null;

    private string $area_name;
    private const ROOT_PREFIX = '';
    private const PREFIX_DIR = self::ROOT_PREFIX.'templates/';
    private Request $request;

    protected function __construct(string $area_name, Request $request)
    {
        $this->area_name = $area_name;
        $this->request = $request;
    }

    protected function __clone(): void {}

    public static function getInstance(string $area_name, Request $request): self
    {
        if (null === self::$_instance)
        {
            self::$_instance = new self($area_name, $request);
        }
        return self::$_instance;
    }

    public function getTwig(): Environment
    {
        $debug_mode_enabled = RIOConfig::isInDebugMode();
        $area_name = $this->area_name;
        $area_inheritance = [
            'email' => 'riomain',
        ];
        if ($debug_mode_enabled) {
            $this->checkIfDirectoriesExist($area_name);
        }
        $loaders = [];
        $loaders = array_merge(
            $loaders,
            $this->getLoadersOfArea($area_name)
        );
        $current_area_name = $area_name;
        while (isset($area_inheritance[$current_area_name])) {
            $current_area_name = $area_inheritance[$current_area_name];
            if ($debug_mode_enabled) {
                $this->checkIfDirectoriesExist($current_area_name);
            }
            $loaders = array_merge(
                $loaders,
                $this->getLoadersOfArea($current_area_name)
            );
        }
        $loaders[] = new FilesystemLoader(self::ROOT_PREFIX.'views', dirname(__DIR__));
        $chain = new ChainLoader($loaders);

        $twig_arguments = [
            'debug' => $debug_mode_enabled,
            'cache' => __DIR__ . '/../../cache/twig',
        ];
        $twig = new Environment($chain, $twig_arguments);
        if ($debug_mode_enabled) {
            $twig->enableAutoReload();
        } else {
            $twig->disableAutoReload();
        }
        $twig->addExtension(new RIOCustomTwigExtension($this->request));
        if ($debug_mode_enabled) {
            $twig->addExtension(new DebugExtension());
        }

        return $twig;
    }

    private function getLoadersOfArea(string $area_name): array
    {
        return [
            new FilesystemLoader(self::PREFIX_DIR."$area_name", dirname(__DIR__)),
        ];
    }

    private function checkIfDirectoriesExist(string $area_name): void
    {
        if(RIOConfig::isInDebugMode()) {
            if (!is_dir(
                dirname(__DIR__) . '/'
            )) {
                throw new Error("The twig template folder \"$area_name/\" is missing. "."The path \"/source/templates/$area_name/\" should exist.");
            }
        }
    }
}
