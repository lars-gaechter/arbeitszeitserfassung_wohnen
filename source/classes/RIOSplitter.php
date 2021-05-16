<?php

declare(strict_types=1);


final class RIOSplitter
{
    private string $string;

    public function __construct(string $string)
    {
        $this->string = $string;
    }

    public function splitAt(string $delimiter): RIOSplitString
    {
        return new RIOSplitString(explode($delimiter, $this->string));
    }
}
