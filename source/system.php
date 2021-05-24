<?php

use MongoDB\Model\BSONDocument;

/**
 * @throws Exception
 */
function activeUsersEndStart(DateTime $dateTime): void
{
    $mongoDB = RIOMongoDatabase::getInstance();
    $year = $dateTime->format("Y");
    $usersInactive = $mongoDB->getInactiveUsers();
    $usersActive = $mongoDB->getActiveUsers();

    foreach ($usersActive as $userActive) {
        $workDaysCollection = $mongoDB->getWorkDaysCollectionByYearUser($year, $userActive->offsetGet("sessionUsername"));
        /** @var BSONDocument $findOneWorkDay */
        $findOneWorkDay = $workDaysCollection->findOne(['date' => $dateTime->format("d.m.Y")]);
        /** @var BSONDocument[] $findWorkDaysFromThisMonth */
        $findWorkDaysFromThisMonth = $workDaysCollection->find(["month" => $dateTime->format("m")])->toArray();
        $lastDayThisMonth = end($findWorkDaysFromThisMonth);



        $mandatoryTimeMonthly = $lastDayThisMonth->offsetGet("mandatoryTimeMonthly");
        $currentMandatoryTimeMonthly = RIODateTimeFactory::getDateTime();
        $time = stringTimeToIntArray($mandatoryTimeMonthly);
        $mandatoryTime = $findOneWorkDay->offsetGet('mandatoryTime');
        $mandatoryTimeCorrected = $findOneWorkDay->offsetGet('mandatoryTimeCorrected');
        if('' !== $mandatoryTimeCorrected) {
            $mandatoryTimeCorrectedDateTime = RIODateTimeFactory::getDateTime($mandatoryTimeCorrected);
            $final = calculationOverTwentyfourHours($currentMandatoryTimeMonthly, $time, $mandatoryTimeCorrectedDateTime);
        } else {
            $mandatoryTimeDateTime = RIODateTimeFactory::getDateTime($mandatoryTime);
            $final = calculationOverTwentyfourHours($currentMandatoryTimeMonthly, $time, $mandatoryTimeDateTime);
        }
        $findOneWorkDay->offsetSet("mandatoryTimeMonthly", $final);
    }

    foreach ($usersInactive as $userInactive) {
        $workDaysCollection = $mongoDB->getWorkDaysCollectionByYearUser($year, $userActive->offsetGet("sessionUsername"));
        /** @var BSONDocument $findOneWorkDay */
        $findOneWorkDay = $workDaysCollection->findOne(['date' => $dateTime->format("d.m.Y")]);
        /** @var BSONDocument[] $findWorkDaysFromThisMonth */
        $findWorkDaysFromThisMonth = $workDaysCollection->find(["month" => $dateTime->format("m")])->toArray();
        $lastDayThisMonth = end($findWorkDaysFromThisMonth);
    }
}

/**
 * @param string $stringTime
 * @return int[]
 */
function stringTimeToIntArray(string $stringTime): array
{
    $array = explode(':', $stringTime);
    return [
        "hour" => $array[0],
        "minute" => $array[1]
    ];
}

/**
 * Only for monthly calculation usage
 *
 * @param \DateTime $dateTime
 * @param array $time
 * @param \DateTime $addDateTime
 * @return string
 * @throws Exception
 */
function calculationOverTwentyfourHours(DateTime $dateTime, array $time, DateTime $addDateTime): string
{
    $firstDateTime = RIODateTimeFactory::getDateTime("00:00");
    $newDateTime = RIODateTimeFactory::getDateTime();
    $nowDayNumber = (int)$dateTime->format("d");
    $nowYearNumber = (int)$dateTime->format("Y");
    $firstDayNumber = (int)$addDateTime->format("d");
    $firstYearNumber = (int)$addDateTime->format("Y");
    $addNumberOfDays = $firstDayNumber - $nowDayNumber;
    $addNumberOfYears = $firstYearNumber - $nowYearNumber;
    $dateTime->setTime($time["hour"], $time["minute"]);
    $afterDayNumber = (int)$dateTime->format("d");
    $numberOfDays = $afterDayNumber - $nowDayNumber;
    $hoursOfDays = $numberOfDays*24;
    $restHour = (int)$dateTime->format("H");
    $totalHour = $hoursOfDays + $restHour;
    $hourFinal = $totalHour <= 9 ? '0'.$totalHour : $totalHour;
    $restMinute = (int)$dateTime->format("i");
    $minuteFinal = $restMinute <= 9 ? '0'.$restMinute : $restMinute;
    $newDateTime->setTime($hourFinal, $minuteFinal);
    $interval = new DateInterval("P".$addNumberOfYears."Y".$addNumberOfDays."DT".$addDateTime->format("H")."H".$addDateTime->format("i")."M");
    $newDateTime->add($interval);
    $finalInterval = $firstDateTime->diff($newDateTime);
    $hour = ($finalInterval->d*24)+$finalInterval->h <= 9 ? '0'.($finalInterval->d*24)+$finalInterval->h : ($finalInterval->d*24)+$finalInterval->h;
    $minute = $finalInterval->i <= 9 ? '0'.$finalInterval->i : $finalInterval->i;
    return $hour.':'.$minute;
}