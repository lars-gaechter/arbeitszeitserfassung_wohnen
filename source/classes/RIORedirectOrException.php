<?php


use Symfony\Component\HttpFoundation\RedirectResponse;

class RIORedirectOrException
{
    /**
     * @param string $message
     * @param int $error_code
     * @param int $code
     * @param int $severity
     * @param string $filename
     * @param int $line
     * @param null $previous
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * @throws \Whoops\Exception\ErrorException
     */
    public static function throwErrorException(
        string $message = "",
        int $error_code = 404,
        int $code = 0,
        int $severity = 1,
        string $filename = __FILE__,
        int $line = __LINE__,
        $previous = null
    ): RedirectResponse
    {
        if(RIOConfig::isInDebugMode()) {
            throw new \Whoops\Exception\ErrorException(
                $message,
                $code,
                $severity,
                $filename,
                $line,
                $previous
            );
        }
        return RIORedirect::error($error_code);
    }
}