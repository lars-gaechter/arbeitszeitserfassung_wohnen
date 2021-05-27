<?php

declare(strict_types=1);

class RIOMaybe
{
    private mixed $value;
    private static ?RIOMaybe $empty = null;

    protected function __construct($value)
    {
        $this->value = $value;
        $this->checkIfValidState();
    }

    public static function getEmpty(): RIOMaybe
    {
        if (null === self::$empty) {
            self::$empty = new class() extends RIOMaybe {
                public function __construct()
                {
                    parent::__construct('EmptyRIOMaybe');
                }
                public function getValue(): void
                {
                    if(RIOConfig::isInDebugMode()) {
                        throw new Error('Tried to getValue from empty RIOMaybe!');
                    }
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

    /**
     * @param $value
     * @return RIOMaybe
     */
    public static function ofSettable(&$value): RIOMaybe
    {
        if (isset($value) && !empty($value)) {
            return self::of($value);
        }
        return self::getEmpty();
    }

    public static function of($value): RIOMaybe
    {
        return new RIOMaybe($value);
    }

    private function checkIfValidState(): void
    {
        if(RIOConfig::isInDebugMode()) {
            if (!isset($this->value)) {
                throw new Error('RIOMaybe state is invalid');
            }
        }
    }
}
