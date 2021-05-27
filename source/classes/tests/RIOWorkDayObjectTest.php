<?php

namespace source\classes\tests;

use Exception;
use PHPUnit\Framework\ExpectationFailedException;
use PHPUnit\Framework\TestCase;
use RIODateTimeFactory;
use RIOWorkDayObject;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

include_once __DIR__.'\..\..\RIOAutoloader.php';
include_once __DIR__.'\..\..\functions.php';

class RIOWorkDayObjectTest extends TestCase
{

    /**
     * @throws TransportExceptionInterface
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
     * @throws TransportExceptionInterface
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
     * @throws ExpectationFailedException|Exception
     * @throws TransportExceptionInterface
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
     * @throws TransportExceptionInterface
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
