<?php

declare(strict_types=1);

interface RIOToJSON
{
    public function toJSON(): string;

    public function toObject(): object;
}