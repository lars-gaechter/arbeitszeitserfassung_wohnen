<?php

namespace source\classes\tests;

use PHPUnit\Framework\TestCase;
use RIOTimeObject;

/** The import directory before the git directory can vary the path. */
include_once 'C:\Users\l.gaechter\git\ldap-user-authentication\source\RIOAutoloader.php';


class RIOTimeObjectTest extends TestCase
{
    /**
     * Tests addMinutes and getTotalMinutes
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function testaddMinutesGetTotalMinutes(): void
    {
        $start = 435;
        $add = 10;
        $expected = $start+$add;
        $time = new RIOTimeObject($start);
        $time->addMinutes($add);
        $this->assertEquals($expected,$time->getTotalMinutes(),"Add ".$add.", start ".$start." and expected addition start+add === ".$expected." minutes to RIOTimeObject.");
    }

    /**
     * Tests multiple (x2) addMinutes and getMinutes
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function testaddMinutesGetMinutes(): void
    {
        $time = new RIOTimeObject(435); // 435m % 60 = 15m
        $time->addMinutes(10); // 435m+10m = 445m % 60 = 25m
        $time->addMinutes(220);// 445m+220m = 665 % 60 = 5m
        $this->assertEquals(5,$time->getMinutes(),"Test has failed!");
    }

    /**
     * Tests addTime and getTotalMinutes
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function testAddTimeGetTotalMinutes(): void
    {
        $time = new RIOTimeObject(324); // 324m == 5h 24m
        $time->addTime(
            [
                "hours" => 2,
                "minutes" => 17
            ]
        ); // 324m + 137m = 461m
        $this->assertEquals(461,$time->getTotalMinutes(),"Test has failed!");
    }

    /**
     * Tests addTime and getMinutes
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function testAddTimeGetMinutes(): void
    {
        $time = new RIOTimeObject(324); // 324m == 5h 24m
        $time->addTime(
            [
                "hours" => 2,
                "minutes" => 17
            ]
        ); // 324m + 137m = 461m % 60 = 41m
        $this->assertEquals(41,$time->getMinutes(),"Test has failed!");
    }

    /**
     * Tests addTime (with negative hours and minutes) and getMinutes
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function testAddTimeGetMinutesNegativeNegative(): void
    {
        $time = new RIOTimeObject(324); // 324m == 5h 24m
        $time->addTime(
            [
                "hours" => -2,
                "minutes" => -17
            ]
        ); // 324m - 137m = 187m % 60 = 7m
        $this->assertEquals(7,$time->getMinutes(),"Test has failed!");
    }

    /**
     * Tests addTime (with positive hours and negative minutes) and getMinutes
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function testAddTimeGetMinutesNegativePositive(): void
    {
        $time = new RIOTimeObject(324); // 324m == 5h 24m
        $time->addTime(
            [
                "hours" => -2,
                "minutes" => 17
            ]
        ); // 324m - 120m + 17m = 221m % 60 = 41m
        $this->assertEquals(41,$time->getMinutes(),"Test has failed!");
    }

    /**
     * Tests addTime (with positive hours and negative minutes) and getMinutes
     *
     * @throws \PHPUnit\Framework\ExpectationFailedException
     */
    public function testAddTimeGetMinutesPositiveNegative(): void
    {
        $time = new RIOTimeObject(324); // 324m == 5h 24m
        $time->addTime(
            [
                "hours" => 2,
                "minutes" => -17
            ]
        ); // 324m + 120m - 17m = 427m % 60 = 7m
        $this->assertEquals(7,$time->getMinutes(),"Test has failed!");
    }


}
