<?php

declare(strict_types=1);

use Twig\Environment;

interface RIOTwigProvider
{
    public function getTwig(): Environment;
}
