<?php
namespace source;

function getAbsolutePath(array $parts = [], string $after = ""): string
{
    $path = "http";
    $path .= "true" === $_ENV['HTTPS'] ? "s" : "";
    $path .= "://".$_ENV['APP_HOST']."/". getPathPartsToPath($parts) .$after;
    return $path;
}

function getAPIAbsolutePath(array $parts = [], string $after = ""): string
{
    $path = "http";
    $path .= "true" === $_ENV['API_HTTPS'] ? "s" : "";
    $path .= "://".$_ENV['API_HOSTNAME']."/". getPathPartsToPath($parts) .$after;
    return $path;
}

function getPathPartsToPath(array $parts = []): string
{
    $path = '';
    foreach ($parts as $part) {
        $path .= $part;
        $path .= '/';
    }
    return $path;
}
