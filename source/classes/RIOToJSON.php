<?php


interface RIOToJSON
{
    public function toJSON(): string;

    public function toObject(): object;
}