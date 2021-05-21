<?php

declare(strict_types=1);

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\Stopwatch\Stopwatch;

include_once __DIR__.'/../source/autoload.php';
xdebug_info();
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');
error_reporting(E_ALL);
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
mb_regex_encoding('UTF-8');
if("false" === $_ENV["DEVELOPMENT_MODE"]) {
    error_reporting(0);
    ini_set('display_errors', "0");
}
if("true" === $_ENV["DEVELOPMENT_MODE"]) {
    error_reporting(E_ALL);
    ini_set('display_errors', "1");
}
$stopwatch = new Stopwatch(true);
$stopwatch->start('server_time');
$api = RIOApplication::getInstance();
$request = Request::createFromGlobals();
if("true" === $_ENV["MAINTENANCE"]) {
    $maintenanceResponse = new Response();
    $maintenanceResponse->setStatusCode(Response::HTTP_SERVICE_UNAVAILABLE)->setContent("Site under maintenance!")->send();
    die();
}
$stopwatchSession = new Stopwatch(true);
$stopwatchSession->start('session');
if (!$request->hasSession()) {
    $session = new Session(
        new NativeSessionStorage(
            [],
            new NativeFileSessionHandler()
        )
    );
    $request->setSession($session);
}
$request->getSession()->start();
if($_ENV["SESSION_LIFE_TIME"] === $request->getSession()->getMetadataBag()->getLifetime()) {
    if($_ENV["SESSION_LIFE_TIME"] >= $request->getSession()->getMetadataBag()->getLastUsed() - $request->getSession()->getMetadataBag()->getCreated()) {
        // Session has still lifetime
    } else {
        // Session has no lifetime
        $request->getSession()->invalidate();
    }
}
/**
 * Force close the session
 * You cant use the session to store things unless you open it again
 * We start the session to get the id from it.
 */
$request->getSession()->save();
$event = $stopwatchSession->stop('session');
RIOApplication::$perfData['session'] = [
    'time' => $event->getDuration(),
];
try {
    $response = RIOApplication::getInstance()->launch($request);
} catch (RIOConnectionFailed $connectionFailed) {
    if (RIOConfig::isInDebugMode()) {
        throw new Error("The database connection could not be established.", 0, $connectionFailed);
    } else {
        $response = RIORedirect::error(503);
    }
}
$event = $stopwatch->stop('server_time');
if (RIOConfig::isInDebugMode()) {
    $response->headers->add([
        'perf-server-duration' => $event->getDuration().' ms',
        'perf-max-memory' => $event->getMemory().' bytes',
    ]);
}
$response->send();