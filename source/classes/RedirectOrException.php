<?php


class RedirectOrException
{
    /**
     * @param string $message
     * @param int $error_code
     * @param int $code
     * @param int $severity
     * @param string $filename
     * @param int $line
     * @param null $previous
     * @return RIORedirect
     * @throws \Whoops\Exception\ErrorException
     */
    public static function throwErrorException(
        string $message = "",
        int $error_code = 404,
        int $code = 0,
        int $severity = 1,
        $filename = __FILE__,
        $line = __LINE__,
        $previous = null
    ): RIORedirect
    {


        if(RIOConfig::isDevelopmentMode()) {
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