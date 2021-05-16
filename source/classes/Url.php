<?php

/**
 * Allows for easy url generation.
 *
 * With this class you can avoid directly manipulating a string url.
 */
class Url
{
    private string $domain;
    private string $protocol;
    private RIOSplitString $path_parts;

    /**
     * A link to a website.
     *
     * Url constructor.
     * @param string $protocol
     * @param string $domain
     * @param RIOSplitString $path_parts
     */
    public function __construct(string $protocol, string $domain, RIOSplitString $path_parts)
    {
        $this->domain = $domain;
        $this->protocol = $protocol;
        $this->path_parts = $path_parts;
    }

    public function getUrl(): string
    {
        $safe_parts = $this->path_parts->transformParts(function ($part) {
            $encoded_part = urlencode($part);
            // We still have get parameters sometimes in the url
            // So we need to use the real characters for get parameters here
            // This breaks cases were it shouldn't be a get parameter
            $encoded_part = str_replace('%3F', '?', $encoded_part);
            return str_replace('%3D', '=', $encoded_part);
        });
        $parts = $safe_parts->glueTogether('/');

        if($safe_parts->hasParts()) {
            return "$this->protocol://$this->domain/$parts/";
        } else {
            return "$this->protocol://$this->domain/";
        }
    }

    /**
     * @param string[] $parts
     */
    public function addParts(array $parts): void
    {
        $this->path_parts = $this->path_parts->addParts($parts);
    }
}
