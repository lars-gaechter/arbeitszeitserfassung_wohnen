<?php

namespace source\classes\tests;

use PHPUnit\Framework\TestCase;
use RIODateTimeFactory;
use RIOWorkDayObject;

/** The import directory before the git directory can vary the path. */
include_once 'C:\Users\Lars\git\arbeitszeitserfassung_wohnen\source\RIOAutoloader.php';

class RIOWorkDayObjectTest extends TestCase
{
    /**
     * Test
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function testIsSunday(): void
    {
        $dateTime = RIODateTimeFactory::getDateTime();
        $workDay = new RIOWorkDayObject();
        $this->assertEquals(false,$workDay->isSunday(),"Test has failed!");
    }
}
