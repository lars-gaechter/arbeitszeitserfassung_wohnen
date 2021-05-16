<?php

declare(strict_types=1);

use Twig\Environment;

interface TwigProvider
{
    public function getTwig(): Environment;
}
