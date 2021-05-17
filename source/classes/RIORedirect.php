<?php

declare(strict_types=1);

use Symfony\Component\HttpFoundation\RedirectResponse;

class RIORedirect
{
    /**
     * @param int $code
     * @return RedirectResponse
     */
    public static function error(int $code): RedirectResponse
    {
        return RIORedirect::redirectResponse("error.php?status=$code");
    }

    /**
     * RIORedirect constructor.
     * @param array $path_parts
     * @return RedirectResponse
     */
    public static function redirectResponse(array $path_parts = ['']): RedirectResponse
    {
        return self::redirectToDomain($_ENV['HOSTNAME'], $path_parts, "true" === $_ENV['HTTPS']);
    }

    /**
     * @param string $path
     * @return RedirectResponse
     */
    public static function redirectWithString(string $path): RedirectResponse
    {
        return self::redirectToDomainWithString($_ENV['HOSTNAME'], $path, "true" === $_ENV['HTTPS']);
    }

    /**
     * @param string $domain
     * @param array $path_parts
     * @param bool $secure
     * @return RedirectResponse
     */
    public static function redirectToDomain(string $domain, array $path_parts, bool $secure = true): RedirectResponse
    {
        $url_factory = new RIOUrlFactory($domain, $secure);
        $url = $url_factory->getLocalUrl($path_parts);
        return new RedirectResponse($url->getUrl());
    }

    /**
     * @param string $domain
     * @param string $path
     * @param bool $secure
     * @return RedirectResponse
     */
    public static function redirectToDomainWithString(string $domain, string $path, bool $secure = true): RedirectResponse
    {
        $url_factory = new RIOUrlFactory($domain, $secure);
        $protocol = $url_factory->getHttpOrHttps();
        return new RedirectResponse("$protocol://$domain/$path");
    }
}
