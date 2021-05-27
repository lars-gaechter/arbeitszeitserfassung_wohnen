<?php

namespace source\classes\tests;

use PHPUnit\Framework\TestCase;
use RIODateTimeFactory;
use RIOWorkDayObject;
use Symfony\Component\Dotenv\Dotenv;

/** The import directory before the git directory can vary the path. */
include_once 'C:\Users\l.gaechter\git\lab\arbeitszeitserfassung_wohnen\source\RIOAutoloader.php';
include_once 'C:\Users\l.gaechter\git\lab\arbeitszeitserfassung_wohnen\source\functions.php';

class RIOWorkDayObjectTest extends TestCase
{

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function testIsSaturday(): void
    {
        (new Dotenv())->bootEnv(dirname(__DIR__).'\..\..\.env');
        $dateTime = RIODateTimeFactory::getDateTime("2021-05-22");
        $workDay = new RIOWorkDayObject($dateTime);
        $result = $workDay->isSaturday();
        $this->assertEquals(true,$result,"Test has failed!");
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function testIsSunday(): void
    {
        (new Dotenv())->bootEnv(dirname(__DIR__).'\..\..\.env');
        $dateTime = RIODateTimeFactory::getDateTime("2021-05-23");
        $workDay = new RIOWorkDayObject($dateTime);
        $result = $workDay->isSunday();
        $this->assertEquals(true,$result,"Test has failed!");
    }

    /**
     * Test
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException|\Exception
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function testIsHoliday(): void
    {
        (new Dotenv())->bootEnv(dirname(__DIR__).'\..\..\.env');
        $dateTime = RIODateTimeFactory::getDateTime("2021-05-24");
        $workDay = new RIOWorkDayObject($dateTime);
        $result = $workDay->isRIOHoliday();
        $this->assertEquals(true,$result,"Test has failed!");
    }

    /**
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function testMandatoryTimeToday(): void
    {
        (new Dotenv())->bootEnv(dirname(__DIR__).'\..\..\.env');
        $workDay = new RIOWorkDayObject();
        $result = $workDay->isRIOHoliday() ||
                  $workDay->isSunday() ||
                  $workDay->isSaturday();
        $this->assertEquals(false,$result,"Test has failed!");
    }
}
