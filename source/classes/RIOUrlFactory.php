<?php

declare(strict_types=1);

class RIOUrlFactory
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

    public static function get(): RIOUrlFactory
    {
        $domain = $_ENV['HOSTNAME'];
        $secure = $_ENV['HTTPS'];

        return new RIOUrlFactory(
            $domain,
            $secure
        );
    }

    public function getLocalUrl(array $parts): RIOUrl
    {
        $split_string = new RIOSplitString($parts);

        return $this->getUrl($this->secure, $this->hostname, $split_string);
    }

    public function getHttpUrl(string $domain, array $parts): RIOUrl
    {
        $split_string = new RIOSplitString($parts);

        return $this->getUrl(false, $domain, $split_string);
    }

    public function getHttpsUrl(string $domain, array $parts): RIOUrl
    {
        $split_string = new RIOSplitString($parts);

        return $this->getUrl(true, $domain, $split_string);
    }

    private function getUrl(bool $secure, string $domain, RIOSplitString $parts): RIOUrl
    {
        $protocol = $this->getProtocol($secure);

        return new RIOUrl($protocol, $domain, $parts);
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
