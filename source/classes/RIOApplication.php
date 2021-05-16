<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;

/**
 * For each client request there is only one instance of this class
 *
 * Class RIOApplication
 */
class RIOApplication
{
    /**
     * instance
     *
     * @var null|RIOApplication
     */
    protected static ?RIOApplication $instance = null;
    public static array $perfData = [];
    private static PrettyPageHandler $handler;
    private static Request $request;



    /**
     * Singleton
     * RIOApplication constructor.
     */
    protected function __construct() {

    }

    protected function __clone(): void {}

    public static function getInstance(): self
    {
        if (null === self::$instance)
        {
            self::$instance = new self;
        }
        return self::$instance;
    }

    public function launch(Request $request): Response
    {
        if(RIOConfig::isDevelopmentMode()) {
            $run = new Run();
            if (RIOConfig::isInDebugMode()) {
                self::$handler = new PrettyPageHandler();
                $run->pushHandler(self::$handler);
                assert_options(ASSERT_ACTIVE, true);
            }
            $run->register();
        }
        return $this->serveClientRequest($request);
    }

    private function serveClientRequest(Request $request): RIORedirect|Response
    {
        try {
            self::$request = $request;

            $resolvedAction = $this->getAreaPathParser();
            return $this->resolveAndExecuteActionControllerMethodParameter($resolvedAction);
        } catch (NotFoundException $e) {
            if (RIOConfig::isInDebugMode()) {
                throw new Error("The page doesn't exist or is offline", 0, $e);
            } else {
                return RIORedirect::error(404);
            }
        } catch (ReflectionException | \Whoops\Exception\ErrorException) {
        }
        return RIORedirect::redirectWithString('');
    }

    /**
     * @param ResolvedAction $resolvedAction
     * @return Response|null
     * @throws NotFoundException
     * @throws ReflectionException
     * @throws \Whoops\Exception\ErrorException
     */
    private function resolveAndExecuteActionControllerMethodParameter(ResolvedAction $resolvedAction): RIORedirect|Response
    {
        $request = self::$request;
        $class = ucfirst($this->getAreaPathParser()->getFrontend()->getValue());
        if(isset($class)) {
            if(isset($this->getAreaPathParser()->getPathPartials()[0])) {
                $method = $this->getAreaPathParser()->getPathPartials()[0];
            }
            $i = 1;
            $args = [];
            /** @var string[] $pathPartials */
            $pathPartials = [$this->getAreaPathParser()->getPathPartials()];
            foreach ($pathPartials as $pathPartial) {
                if($i >= 1) {
                    $args[] = $pathPartial;
                }
                $i++;
            }
            unset($args[0][0]);
            $userRequestedParameters = $args[0];
        } else {
            // Controller not isset
            return RIORedirect::error(404);
        }
        if("admin" === $class) {
            // TODO: is logged in user?
        }
        $twig = self::getTwig($resolvedAction->getFrontend()->getValue());
        /** @var Main|Admin $instance */
        $instance = new $class($class, $twig, $request);
        if(false === RIOMaybe::ofSettable($class)->isEmpty()) {
            if(!class_exists($class)) {
                return RedirectOrException::throwErrorException("Controller class called '".$class."' doesn't exist");
            }
            if(false === RIOMaybe::ofSettable($method)->isEmpty()) {
                if(!method_exists($instance, $method)) {
                    return RedirectOrException::throwErrorException("Method called '".$method."' doesn't exist in controller class called '".$class."'");
                }
                $methodSignature = new ReflectionMethod($instance, $method);
                $numberOfUserRequestedParameters = count($userRequestedParameters);
                $numberOfRequiredParameters = $methodSignature->getNumberOfRequiredParameters();
                /** @var ReflectionParameter $parameters */
                $parameters = $methodSignature->getParameters();
                // This is to make error message more comprehensive for developers
                $paramNames = [];
                foreach ($parameters as $parameter) {
                    $paramNames[] = $parameter->getName();
                }
                $nameParam = '';
                $x = 0;
                foreach ($paramNames as $paramName) {
                    if($x > 0) {
                        $nameParam .= ', ';
                    }
                    $nameParam .= $paramName;
                    $x++;
                }
                if($numberOfRequiredParameters !== $numberOfUserRequestedParameters) {
                    return RedirectOrException::throwErrorException(
                        "User requested ".
                        $numberOfUserRequestedParameters.
                        " parameter/s for controller ".
                        $class.
                        " with method ".
                        $method.
                        ", which actually required ".$numberOfRequiredParameters.
                        " parameter/s called ".
                        $nameParam.
                        "."
                    );
                }
                $userParamIteration = 0;
                $allParametersWithRequiredTypes = [];
                foreach ($parameters as $parameter) {
                    $paramValueUser = $this->getAreaPathParser()->getPathPartials()[$userParamIteration+1];
                    $paramTypeUser = gettype($paramValueUser);
                    $paramTypeSystem = $parameter->getType()->getName();
                    if($paramTypeUser !== $paramTypeSystem) {
                        // User value its type isn't the same type as required
                        if(!settype($paramValueUser, $paramTypeSystem)) {
                            throw new \Whoops\Exception\ErrorException(
                                "The name of the requested parameter at position ".
                                $userParamIteration.
                                " === ".
                                $parameter->getPosition().
                                " is ".
                                $parameter->getName().
                                " We had to set value to a different type with settype(value_with_wrong_type, actual_type_we_need) which somehow failed to change. The type of requested value user parameter value is ".
                                $paramValueUser.
                                " and type of ".
                                $paramTypeUser.
                                " and the required type of this parameter is a/n ".
                                $paramTypeSystem
                            );
                        }
                    }
                    $allParametersWithRequiredTypes[] = $paramValueUser;
                    $userParamIteration++;
                }
                if(0 === $numberOfRequiredParameters && 0 === $numberOfUserRequestedParameters) {
                    // No parameter required or optional or zero possible parameter, user requested with 0 parameter
                    $response = call_user_func(
                        [
                                $instance
                            ,   $this->getAreaPathParser()->getPathPartials()[0]
                        ]
                    );
                }
                if(1 === $numberOfRequiredParameters && 1 === $numberOfUserRequestedParameters) {
                    // One parameter required and user has requested with one parameter
                    $response = call_user_func(
                        [
                                $instance
                            ,   $this->getAreaPathParser()->getPathPartials()[0]
                        ]
                            ,   $allParametersWithRequiredTypes[0]
                    );
                }
                if(1 < $numberOfRequiredParameters) {
                    // Multiple parameters required and user has requested with multiple parameters
                    $response = call_user_func_array(
                        [
                                $instance
                            ,   $this->getAreaPathParser()->getPathPartials()[0]
                        ]
                            ,   $allParametersWithRequiredTypes
                    );
                }
                if(0 === $numberOfRequiredParameters) {
                    $response = call_user_func(
                        [
                                $instance
                            ,   $this->getAreaPathParser()->getPathPartials()[0]
                        ]
                    );
                }
                if(false === isset($response)) {
                    return RedirectOrException::throwErrorException("Couldn't create valid controller method response for number of parameters may required", 500);
                }
                return $response;
            }
        }
        return call_user_func([$instance, $_ENV['EMPTY_URL_CONTROLLER']]);
    }

    private static function getTwig(string $areaName): Environment
    {
        /** @var TwigProvider $twigProvider */
        $twigProvider = RIOAreaSpecificTwigProvider::getInstance($areaName, self::$request);
        return $twigProvider->getTwig();
    }

    /**
     * @throws NotFoundException
     */
    public function getAreaPathParser(): ResolvedAction
    {
        $path = urldecode($_SERVER['REQUEST_URI']);
        $pathConfig = ResolverConfigFactory::config([
            ResolverConfigFactory::step([new FrontendResolver($_ENV['DEFAULT_AREA_NAME'], [
                'main',
                'admin',
            ])]),
        ]);
        $pathResolver = new Resolver($pathConfig);
        $resolvedAction = $pathResolver->resolveWithString($path);

        RIOApplication::addDebugInformation('Uri', [
            'frontend' => $resolvedAction->getFrontend(),
            'controller namespace' => $resolvedAction->getControllerNamespace(),
            'controller action' => $resolvedAction->getControllerAction(),
            'controller action parameters' => $resolvedAction->getControllerActionParameters(),
        ]);
        return $resolvedAction;
    }

    public static function addDebugInformation(string $title, array $data): void
    {
        if (RIOConfig::isInDebugMode()) {
            self::$handler->addDataTable($title, $data);
        }
    }
}