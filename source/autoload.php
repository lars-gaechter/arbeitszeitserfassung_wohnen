<?php

namespace source;

// autoload rafisa internal organisation
include_once __DIR__ . '/RIOAutoloader.php';

// autoload composer packages
$autoloader_dir = dirname(__DIR__) . '/vendor/autoload.php';
$found_autoloader_file = @include_once $autoloader_dir;
if (!$found_autoloader_file) {
    echo
        $autoloader_dir . "</br>" .
        "<p>The autoloader file doesn't exist in the upon directory</p>",
    '<p>Read the README.md in the root directory of this repository.</p>';
    die();
}

include_once __DIR__ . '/functions.php';

include_once __DIR__ . '/system.php';


$env_file = dirname(__DIR__) . '/.env';
$env_exists = file_exists($env_file);
if (!$env_exists) {
    echo
        $env_file . "</br>" .
        "<p>The environment file doesn't exist in the upon directory</p>",
    '<p>Read the README.md in the root directory of this repository.</p>';
    die();
}
