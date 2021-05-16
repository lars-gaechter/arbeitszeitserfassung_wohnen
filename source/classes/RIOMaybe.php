<?php

declare(strict_types=1);

class RIOMaybe
{
    private mixed $value;
    private static $empty = null;

    protected function __construct($value)
    {
        $this->value = $value;
        $this->checkIfValidState();
    }

    public static function getEmpty(): Maybe
    {
        if (null === self::$empty) {
            self::$empty = new class() extends Maybe {
                public function __construct()
                {
                    parent::__construct('EmptyMaybe');
                }
                public function getValue(): void
                {
                    throw new Error('Tried to getValue from empty Maybe!');
                }
            };
        }
        return self::$empty;
    }

    public function getValue()
    {
        $this->checkIfValidState();
        return $this->value;
    }

    public function isEmpty(): bool
    {
        return $this === self::$empty;
    }

    public static function ofEmptiable($value): Maybe
    {
        if (empty($value)) {
            return self::getEmpty();
        }
        return self::of($value);
    }

    public static function ofSettable(&$value): Maybe
    {
        if (isset($value) && !empty($value)) {
            return self::of($value);
        }
        return self::getEmpty();
    }

    public static function of($value): Maybe
    {
        return new Maybe($value);
    }

    private function checkIfValidState(): void
    {
        if (!isset($this->value)) {
            throw new Error('Maybe state is invalid');
        }
    }
}
