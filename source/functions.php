<?php
namespace source;

function getCurrentUpperDir(int $upper = 1): string
{
    $current_upper_dir = __DIR__;
    for ($i = 0; $upper>$i; $i++) {
        $current_upper_dir .= '/..';
    }
    $current_upper_dir .= '/';
    return $current_upper_dir;
}

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

function getAbsolutePathSecondHost(string $after = "", array $parts = []): string
{
    $path = "http";
    $path .= "true" === $_ENV['HTTPS'] ? "s" : "";
    $path .= "://".$_ENV['SECOND_HOST']."/". getPathPartsToPath($parts) .$after;
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

function getAbsoluteImagePath($after = ""): string
{
    return getAbsolutePath(["img/"], $after);
}

function getHTMLInput($array): string
{
    return getInput($array);
}

function getDefaultTheme(): array
{
    if(isset($_COOKIE['theme'])) {
        if("dark" === $_COOKIE['theme']) {
            return [
                'theme' => "dark",
                'theme_css' => getAbsolutePath(["css"],"dark-theme.css")
            ];
        }
        $theme_id = $_COOKIE['theme'] === 'light' ? 2 : 1;
    } else {
        $theme_id = 1;
    }
    return [
        'theme' => "light",
        'theme_css' => getAbsolutePath(["css"], "light-theme.css"),
        'theme_id' => $theme_id
    ];
}

function getHTMLInputCheckBox($array, $number, $index = false): string
{
    $html = '';
    for ($i = 1; $i <= $number; $i++) {
        if(1 === $i) {
            if($index) {
                $html .= getInput($array, "_".$i);
            } else {
                $html .= getInput($array);
            }
        } else {
            if($index) {
                $html .= ",".getInput($array, "_".$i);
            } else {
                $html .= ",".getInput($array);
            }
        }
    }
    return $html;
}

function getInput($array, $index = ""): string
{
    return "<input name='".$array["id"].$index."' id='".$array["id"].$index."' type='".$array["input_type"]."' >";
}

function getMultipleInput($array, $multiple): string
{
    $html = "";
    if($multiple >= 2) {
        for ($i = 1; $i <= $multiple; $i++) {
            $html .= getInput($array, "_".$i);
        }
    } else {
        for ($i = 1; $i <= $multiple; $i++) {
            $html .= getInput($array);
        }
    }
    return $html;
}

function home(): void
{
    header('Location: '."/");
    exit();
}

function admin(): void
{
    header('Location: '."/admin/");
    exit();
}

function userByName(string $user_name): void
{
    header('Location: '."/user/?user_name=".$user_name);
    exit();
}
