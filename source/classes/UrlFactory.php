<?php

declare(strict_types=1);

class UrlFactory
{
    private string $hostname;
    private bool $secure;

    public function __construct(
        string $hostname,
        bool $secure
    ) {
        $this->hostname = $hostname;
        $this->secure = $secure;
    }

    public static function get(): UrlFactory
    {
        $domain = $_ENV['HOSTNAME'];
        $secure = $_ENV['HTTPS'];

        return new UrlFactory(
            $domain,
            $secure
        );
    }

    public function getLocalUrl(array $parts): Url
    {
        $split_string = new RIOSplitString($parts);

        return $this->getUrl($this->secure, $this->hostname, $split_string);
    }

    public function getHttpUrl(string $domain, array $parts): Url
    {
        $split_string = new RIOSplitString($parts);

        return $this->getUrl(false, $domain, $split_string);
    }

    public function getHttpsUrl(string $domain, array $parts): Url
    {
        $split_string = new RIOSplitString($parts);

        return $this->getUrl(true, $domain, $split_string);
    }

    private function getUrl(bool $secure, string $domain, RIOSplitString $parts): Url
    {
        $protocol = $this->getProtocol($secure);

        return new Url($protocol, $domain, $parts);
    }

    public function getHttpOrHttps(): string
    {
        return $this->getProtocol($this->secure);
    }

    private function getProtocol(bool $secure): string
    {
        $return = 'http';
        if (true === $secure) {
            $return .= 's';
        }
        return $return;
    }
}
