<?php

use MongoDB\Collection;
use MongoDB\Model\BSONDocument;

/**
 * Nightly cronjob which calc today with last day or only today
 *
 * Class RIOCronJob
 */
class RIOCronJob
{
    /**
     * Have to update data in database
     * @var \RIOMongoDatabase|null
     */
    private ?RIOMongoDatabase $mongoDB;

    private DateTime $dateTime;

    /**
     * RIOCronJob constructor.
     */
    public function __construct(DateTime $dateTime)
    {
        $this->mongoDB = RIOMongoDatabase::getInstance();
        $this->dateTime = $dateTime;
    }


    /**
     * Day ends at 23:59 and starts at 00:00
     *
     * @throws Exception
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function usersEndStart(): void
    {
        // The current year
        $year = $this->dateTime->format("Y");
        // User which haven't or have time record started
        $users = $this->mongoDB->getUsers();
        // Inactive user which haven't time record started
        $usersInactive = $this->mongoDB->getInactiveUsers();
        // Active user which haven time record started
        $usersActive = $this->mongoDB->getActiveUsers();
        $today = ['date' => $this->dateTime->format("d.m.Y")];
        // Update active users
        //$this->calcActiveUsers($usersActive, $today, $year);
        // Update users
        $this->calcUsers($users, $today, $year);
        // Update inactive users
        //$this->calcInactiveUsers($usersInactive, $today, $year);
    }

    private function calcUsers($users, $today, $year): void
    {
        foreach ($users as $user) {
            $workDaysCollection = $this->mongoDB->getWorkDaysCollectionByYearUser($year, $user->offsetGet("sessionUsername"));
            /** @var BSONDocument $findOneWorkDay */
            $findOneWorkDay = $workDaysCollection->findOne($today);
            // Get all mandatory time calc
            $timeMonthlyWeeklyAndTotal = $this->calcMandatoryTimeMonthlyWeeklyAndTotal($workDaysCollection, $findOneWorkDay);
            // Calc mandatory time monthly
            $findOneWorkDay->offsetSet("mandatoryTimeMonthly", $timeMonthlyWeeklyAndTotal["mandatoryTimeMonthly"]);
            // Calc mandatory time weekly
            $findOneWorkDay->offsetSet("mandatoryTimeWeekly", $timeMonthlyWeeklyAndTotal["mandatoryTimeWeekly"]);
            // Calc mandatory time total
            $findOneWorkDay->offsetSet("mandatoryTimeTotal", $timeMonthlyWeeklyAndTotal["mandatoryTimeTotal"]);

            $isTimeMonthly = $this->calcIsTimeMonthly($workDaysCollection, $findOneWorkDay);
            $deviationTimeMonthly = $this->calcDeviationTimeMonthly($isTimeMonthly, $findOneWorkDay->offsetGet("mandatoryTimeMonthly"));

            $updateFutureImplementation = [
                'mandatoryTimeWeekly' => $findOneWorkDay->offsetGet("mandatoryTimeWeekly"),
                'mandatoryTimeTotal' => $findOneWorkDay->offsetGet("mandatoryTimeTotal")
            ];
            $update = [
                'mandatoryTimeMonthly' => $findOneWorkDay->offsetGet("mandatoryTimeMonthly"),
                'isTimeMonthly' => $isTimeMonthly,
                'deviationTimeMonthly' => $deviationTimeMonthly,
                'deviationTimeTotal' => '',
                'deviationTimeWeekly' => ''
            ];

            echo "<pre>";
            var_dump($update);
            echo "</pre>";
            die();
            // Save today
            $workDaysCollection->updateOne(
                [
                    'date' => $this->dateTime->format("d.m.Y")
                ],
                [
                    '$set' => $update
                ]
            );
        }
    }

    private function calcInactiveUsers($usersInactive, $today, $year): void
    {
        foreach ($usersInactive as $userInactive) {
            $timeRecordStarted = false;
            $workDaysCollection = $this->mongoDB->getWorkDaysCollectionByYearUser($year, $userInactive->offsetGet("sessionUsername"));
            /** @var BSONDocument $findOneWorkDay */
            $findOneWorkDay = $workDaysCollection->findOne($today);
            // Check if times is still empty because of active user without start record
            if([] === $findOneWorkDay->offsetGet("time")) {
                // Insert 0 minute workday entry for inactive user
                $stopTimes = $this->updateInactiveTimes();
            } else {
                $stopTimes = $findOneWorkDay->offsetGet("time");
            }

            // Save today
            $workDaysCollection->updateOne(
                [
                    'date' => $this->dateTime->format("d.m.Y")
                ],
                [
                    '$set' => $stopTimes
                ]
            );
            // Save tomorrow, create new work day for the next day
            $this->createNewWorkDay($workDaysCollection, $userInactive, $timeRecordStarted);
        }
    }

    private function calcActiveUsers($usersActive, $today, $year): void
    {
        foreach ($usersActive as $userActive) {
            $timeRecordStarted = true;
            $workDaysCollection = $this->mongoDB->getWorkDaysCollectionByYearUser($year, $userActive->offsetGet("sessionUsername"));
            /** @var BSONDocument $findOneWorkDay */
            $findOneWorkDay = $workDaysCollection->findOne($today);
            // Check if times is still empty because of active user without start record
            if([] === $findOneWorkDay->offsetGet("time")) {
                $timeRecordStarted = false;
                // Set active user inactive because of 24 hour inactivity
                $this->mongoDB->getUsersCollection()->findOneAndUpdate(
                    ["sessionUsername" => $userActive->offsetGet("sessionUsername"), 'timeRecordStarted' => $userActive->offsetGet("timeRecordStarted")],
                    ['$set' => ['timeRecordStarted' => $timeRecordStarted]]
                );
                // Insert 0 minute workday entry for active user
                $stopTimes = $this->updateInactiveTimes();
            } else {
                // All active users stop time record for today
                $stopTimes = $this->updateTodayTimes($findOneWorkDay, $userActive->offsetGet("sessionUsername"));
            }
            // Save today
            $workDaysCollection->updateOne(
                [
                    'date' => $this->dateTime->format("d.m.Y")
                ],
                [
                    '$set' => $stopTimes
                ]
            );
            // Save tomorrow, create new work day for the next day, all active users start time record for the next day
            $this->createNewWorkDay($workDaysCollection, $userActive, $timeRecordStarted);
        }
    }

    /**
     * Set 0 hour and 0 minute entry for user current work day was empty
     *
     * @return array
     */
    private function updateInactiveTimes(): array
    {
        $timeStart =
            [
                'start' => '00:00',
                'startCorrected' => '',
                'comment' => '',
                'lastEditedUser' => '',
                'lastEditedDate' => '',
                'lastEditedTime' => ''
            ];
        $timeEnd = [

        ];
        return [
            "time" => array_merge(
                $timeStart,
                $timeEnd
            )
        ];
    }

    /**
     * Stop time, calc time
     *
     * @param \MongoDB\Model\BSONDocument $findOneWorkDay
     * @param string $sessionUsername
     * @return array
     * @throws \Exception
     */
    private function updateTodayTimes(BSONDocument $findOneWorkDay, string $sessionUsername): array
    {
        $times = $findOneWorkDay->offsetGet("time");
        $end = "23:59";
        $endDateTime = $this->dateTime->format("H:i");
        if($end === $endDateTime) {
            $endTime = $endDateTime;
        } else {
            $endTime = $end;
        }
        $start = "00:00";
        $startTime = RIODateTimeFactory::getDateTime($start);
        $presenceTimeTotal = RIODateTimeFactory::getDateTime($start);
        foreach ($times as $time) {
            if(false === $time->offsetExists("end")) {
                $time->offsetSet("end", $endTime);
            }
            if(false === $time->offsetExists("endCorrected")) {
                $time->offsetSet("endCorrected", '');
            }
            $addableTimeStart = $time->offsetGet("start");
            $addableTimeEnd = $endTime;
            $start = RIODateTimeFactory::getDateTime($addableTimeStart);
            $end = RIODateTimeFactory::getDateTime($addableTimeEnd);
            $startEndDiff = RIODateTimeFactory::getDateTime();
            /** @var DateInterval|false $diff */
            $diff = $start->diff($end);
            $startEndDiff->setTime($diff->h, $diff->i);
            if($startEndDiff->format("H:i") === $time->offsetGet("presenceTime")) {
                $addableTime = $time->offsetGet("presenceTime");
            } else {
                $addableTime = $startEndDiff->format("H:i");
            }
        }
        if(null === $addableTime) {
            $addableTime = "00:00";
        }
        $presenceTime = RIODateTimeFactory::getDateTime($addableTime);
        $presenceTimeTotal->add($startTime->diff($presenceTime));
        $mandatoryTime = RIODateTimeFactory::getDateTime($findOneWorkDay->offsetGet("mandatoryTime"));
        $deviationDiff = $mandatoryTime->diff($presenceTimeTotal);
        $deviation = RIODateTimeFactory::getDateTime();
        $deviation->setTime($deviationDiff->h, $deviationDiff->i);
        $deviationNegativeOrPositiveOrZero = '';
        if($presenceTimeTotal->format("H:i") === $findOneWorkDay->offsetGet("mandatoryTime")) {
            $deviationNegativeOrPositiveOrZero .= ' ';
        }
        if($presenceTimeTotal->format("H:i") > $findOneWorkDay->offsetGet("mandatoryTime")) {
            $deviationNegativeOrPositiveOrZero .= '+';
        }
        if($presenceTimeTotal->format("H:i") < $findOneWorkDay->offsetGet("mandatoryTime")) {
            $deviationNegativeOrPositiveOrZero .= '-';
        }
        //$userMonth = getUserAllPastWorkdaysByMonthYearUser($findOneWorkDay->offsetGet("monthYear"), "l.gaechter");
        $userTotal = $this->getUserAllPastWorkdays($sessionUsername);
        //$userWeek = getUserAllPastWorkdaysByWeek($findOneWorkDay->offsetGet("weekYear"), "l.gaechter");
        /*$isTimeMonthly = RIODateTimeFactory::getDateTime();
        $isTimeMonthly->setTime(0, 0);
        $mandatoryTimeMonthly = RIODateTimeFactory::getDateTime();
        $mandatoryTimeMonthly->setTime(0, 0);
        $deviationTimeMonthly = RIODateTimeFactory::getDateTime();
        $deviationTimeMonthly->setTime(0, 0);
        foreach ($userMonth as $day) {
            $dayTime = RIODateTimeFactory::getDateTime($day->offsetGet("presenceTime"));
            $dayMandatoryTime = RIODateTimeFactory::getDateTime($day->offsetGet("mandatoryTime"));
            $isTimeMonthly->add($startTime->diff($dayTime));
            $mandatoryTimeMonthly->add($startTime->diff($dayMandatoryTime));
        }
        $deviationTimeMonthly->add($mandatoryTimeMonthly->diff($isTimeMonthly));*/
        $isTimeTotal = RIODateTimeFactory::getDateTime();
        $isTimeTotal->setTime(0, 0);
        $mandatoryTimeTotal = RIODateTimeFactory::getDateTime();
        $mandatoryTimeTotal->setTime(0, 0);
        $deviationTimeTotal = RIODateTimeFactory::getDateTime();
        $deviationTimeTotal->setTime(0, 0);
        foreach ($userTotal as $day) {
            $dayTime = RIODateTimeFactory::getDateTime($day->offsetGet("presenceTime"));
            $dayMandatoryTime = RIODateTimeFactory::getDateTime($day->offsetGet("mandatoryTime"));
            $isTimeTotal->add($startTime->diff($dayTime));
            $mandatoryTimeTotal->add($startTime->diff($dayMandatoryTime));
        }
        $deviationTimeTotal->add($mandatoryTimeTotal->diff($isTimeTotal));
        /*$isTimeWeekly = RIODateTimeFactory::getDateTime();
        $isTimeWeekly->setTime(0, 0);
        $mandatoryTimeWeekly = RIODateTimeFactory::getDateTime();
        $mandatoryTimeWeekly->setTime(0, 0);
        $deviationTimeWeekly = RIODateTimeFactory::getDateTime();
        $deviationTimeWeekly->setTime(0, 0);
        foreach ($userWeek as $day) {
            $dayTime = RIODateTimeFactory::getDateTime($day->offsetGet("presenceTime"));
            $dayMandatoryTime = RIODateTimeFactory::getDateTime($day->offsetGet("mandatoryTime"));
            $isTimeWeekly->add($startTime->diff($dayTime));
            $mandatoryTimeWeekly->add($startTime->diff($dayMandatoryTime));
        }
        $deviationTimeWeekly->add($mandatoryTimeWeekly->diff($isTimeWeekly));*/
        return [
            "time" => $times,
            'presenceTime' => $presenceTimeTotal->format("H:i"),
            'presenceTimeCorrected' => '',
            'deviation' => $deviation->format("H:i"),
            'deviationNegativeOrPositiveOrZero' => $deviationNegativeOrPositiveOrZero,
            'isTimeMonthly' => '',
            'isTimeTotal' => $isTimeTotal->format("H:i"),
            'isTimeWeekly' => '',
            "deviationTimeMonthly" => '',
            "deviationTimeTotal" => $deviationTimeTotal->format("H:i"),
            "deviationTimeWeekly" => '',
            "deviationTimeTotalCorrected" => ''
        ];
    }

    /**
     * @throws \Exception
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    private function createNewWorkDay(Collection $workDaysCollection, BSONDocument $user, bool $timeRecordStarted): void
    {
        $mandatoryTime = RIODateTimeFactory::getDateTime($user->offsetGet("mandatoryTime"));
        $workDay = new RIOWorkDayObject();
        $tomorrow = RIODateTimeFactory::getDateTime('tomorrow');
        if($workDay->isRIOHoliday() || $workDay->isSunday() || $workDay->isSaturday()) {
            $workDay->getAbsentAllDay()->setOfficialHoliday();
            $workDay->getAbsentMorning()->setOfficialHoliday();
            $workDay->getAbsentAfternoon()->setOfficialHoliday();
            $mandatoryTime->setTime(0,0);
        }
        if(true === $timeRecordStarted) {
            $times = [
                [
                    'start' => '00:00',
                    'startCorrected' => '',
                    'comment' => '',
                    'lastEditedUser' => '',
                    'lastEditedDate' => '',
                    'lastEditedTime' => ''
                ]
            ];
        } else {
            $times = [];
        }
        $workDaysCollection->insertOne(
            [
                'date' => $tomorrow->format("d.m.Y"),
                'monthYear' => $tomorrow->format("m.Y"),
                'weekYear' => $tomorrow->format("W.Y"),
                'month' => $tomorrow->format("m"),
                'week' => $tomorrow->format("W"),
                'mandatoryTime' => $mandatoryTime->format("H:i"),
                'mandatoryTimeCorrected' => '',
                'timeCredit' => '00:00',
                'timeCreditCorrected' => '',
                'absentAllDay' => $workDay->getAbsentAllDay()->getOption(),
                'absentAfternoon' => $workDay->getAbsentAfternoon()->getOption(),
                'absentMorning' => $workDay->getAbsentMorning()->getOption(),
                'time' => $times
            ]
        );
    }

    /**
     * @throws \Exception
     */
    private function calcMandatoryTimeMonthlyWeeklyAndTotal(Collection $workDaysCollection, BSONDocument $findOneWorkDay): array
    {
        $isFirstDayInMonth = false;
        $isFirstDayInWeek = false;
        /** @var BSONDocument[] $findWorkDaysFromThisMonth */
        $findWorkDaysFromThisMonth = $workDaysCollection->find(["month" => $this->dateTime->format("m")])->toArray();
        $findWorkDaysFromThisWeek = $workDaysCollection->find(["week" => $this->dateTime->format("W")])->toArray();
        // Check if there is past day in this month
        if(count($findWorkDaysFromThisMonth) >= 2) {
            // There is minimum one day before in this month
            $mandatoryTimeMonthly = $findWorkDaysFromThisMonth[count($findWorkDaysFromThisMonth)-2]->offsetGet("mandatoryTimeMonthly");
        } else {
            // First day in this month
            $mandatoryTimeMonthly = "00:00";
            $isFirstDayInMonth = true;
        }
        $mandatoryTime = $findOneWorkDay->offsetGet('mandatoryTime');
        $mandatoryTimeCorrected = $findOneWorkDay->offsetGet('mandatoryTimeCorrected');
        $time = $this->stringTimeToIntArray($mandatoryTimeMonthly);
        // Is last day a corrected mandatory time set
        if('' !== $mandatoryTimeCorrected) {
            $mandatoryTimeCorrectedDateTime = RIODateTimeFactory::getDateTime($mandatoryTimeCorrected);
            $finalMandatoryTimeMonthly = $this->calculationOverTwentyfourHours(RIODateTimeFactory::getDateTime(), $time, $mandatoryTimeCorrectedDateTime);
        } else {
            $mandatoryTimeDateTime = RIODateTimeFactory::getDateTime($mandatoryTime);
            $finalMandatoryTimeMonthly = $this->calculationOverTwentyfourHours(RIODateTimeFactory::getDateTime(), $time, $mandatoryTimeDateTime);
        }
        $finalMandatoryTimeWeekly = "";
        $finalMandatoryTimeTotal = "";
        return [
            "mandatoryTimeMonthly" => $finalMandatoryTimeMonthly,
            "mandatoryTimeWeekly" => $finalMandatoryTimeWeekly,
            "mandatoryTimeTotal" => $finalMandatoryTimeTotal
        ];
    }

    /**
     * @param string $stringTime
     * @return int[]
     */
    private function stringTimeToIntArray(string $stringTime): array
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
     * @param string $addition + or - time
     * @return string
     * @throws \Exception
     */
    private function calculationOverTwentyfourHours(DateTime $dateTime, array $time, DateTime $addDateTime, string $addition = ""): string
    {
        if("+" === $addition || "" === $addition) {
            $subtraction = false;
        }
        if("-" === $addition) {
            $subtraction = true;
        }
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
        if(false === $subtraction) {
            $newDateTime->add($interval);
        } else {
            $newDateTime->sub($interval);
        }
        $finalInterval = $firstDateTime->diff($newDateTime);
        $hour = ($finalInterval->d*24)+$finalInterval->h <= 9 ? '0'.($finalInterval->d*24)+$finalInterval->h : ($finalInterval->d*24)+$finalInterval->h;
        $minute = $finalInterval->i <= 9 ? '0'.$finalInterval->i : $finalInterval->i;
        return $hour.':'.$minute;
    }


    /**
     * Every workday of an user is unique
     *
     * @param string $username
     * @return array
     * @throws \Exception
     */
    private function getUserAllPastWorkdays(string $username): array
    {
        $currentWorkDay = new RIOWorkDayObject();
        /** @var BSONDocument $user */
        $user = $this->mongoDB->getUsersCollection()->findOne(['sessionUsername' => $username]);
        $allWorkDaysFromUser = $this->mongoDB->getWorkDaysCollection()->find(["sessionUsername" => $username])->toArray();
        return $this->getPastWorkDaysUser($allWorkDaysFromUser, $currentWorkDay, $user);
    }

    /**
     * @param array $allWorkDaysFromUser
     * @param \RIOWorkDayObject $currentWorkDay
     * @param \MongoDB\Model\BSONDocument $user
     * @param bool $pastWorkDay
     * @param bool $sortByDate
     * @return array
     * @throws \Exception
     */
    private function getPastWorkDaysUser(array $allWorkDaysFromUser, RIOWorkDayObject $currentWorkDay, BSONDocument $user, bool $pastWorkDay = true, bool $sortByDate = true): array
    {
        $allWorkDaysFromUserPast = [];
        $currentWorkDayString = $currentWorkDay->getDate()->format("d.m.Y");
        foreach ($allWorkDaysFromUser as $oneWorkDayFromUser) {
            if(true === $pastWorkDay) {
                if($oneWorkDayFromUser->offsetGet("date") !== $currentWorkDayString) {
                    $allWorkDaysFromUserPast[] = $oneWorkDayFromUser;
                }
            } else {
                $allWorkDaysFromUserPast[] = $oneWorkDayFromUser;
            }
        }
        if(true === $sortByDate) {
            usort($allWorkDaysFromUserPast, function($a, $b) {
                return RIODateTimeFactory::getDateTime($a['date']) <=> RIODateTimeFactory::getDateTime($b['date']);
            });
        }
        $oneWorkDayFromUserPastIndexed = [];
        $i = 0;
        foreach ($allWorkDaysFromUserPast as $oneWorkDayFromUserPast) {
            $oneWorkDayFromUserPast["presenceTimeCorrections"] = [$user->offsetGet("sessionUsername"), $oneWorkDayFromUserPast["date"]];
            $oneWorkDayFromUserPastIndexed[] = $oneWorkDayFromUserPast;
            $i++;
        }
        return $oneWorkDayFromUserPastIndexed;
    }

    private function calcIsTimeMonthly(Collection $workDaysCollection, BSONDocument $findOneWorkDay): string
    {
        $isFirstDayInMonth = false;
        /** @var BSONDocument[] $findWorkDaysFromThisMonth */
        $findWorkDaysFromThisMonth = $workDaysCollection->find(["month" => $this->dateTime->format("m")])->toArray();
        // Check if there is past day in this month
        if(count($findWorkDaysFromThisMonth) >= 2) {
            // There is minimum one day before in this month
            $isTimeMonthly = $findWorkDaysFromThisMonth[count($findWorkDaysFromThisMonth)-2]->offsetGet("isTimeMonthly");
        } else {
            // First day in this month
            $isTimeMonthly = "00:00";
            $isFirstDayInMonth = true;
        }
        $presenceTime = $findOneWorkDay->offsetGet("presenceTime");
        $presenceTimeCorrected = $findOneWorkDay->offsetGet("presenceTimeCorrected");
        $time = $this->stringTimeToIntArray($isTimeMonthly);
        if('' !== $presenceTimeCorrected) {
            $presenceTimeCorrectedDateTime = RIODateTimeFactory::getDateTime($presenceTimeCorrected);
            $finalPresenceTimeMonthly = $this->calculationOverTwentyfourHours(RIODateTimeFactory::getDateTime(), $time, $presenceTimeCorrectedDateTime);
        } else {
            $presenceTimeDateTime = RIODateTimeFactory::getDateTime($presenceTime);
            $finalPresenceTimeMonthly = $this->calculationOverTwentyfourHours(RIODateTimeFactory::getDateTime(), $time, $presenceTimeDateTime);
        }
        return $finalPresenceTimeMonthly;
    }

    private function calcDeviationTimeMonthly(string $isTimeMonthly, mixed $mandatoryTimeMonthly): string
    {
        $positiveNegative = "";
        $isTimeMonthlyDateTime = RIODateTimeFactory::getDateTime();
        $isTimeMonthlyArray = $this->stringTimeToIntArray($isTimeMonthly);
        $isTimeMonthlyDateTime->setTime($isTimeMonthlyArray["hour"], $isTimeMonthlyArray["minute"]);
        $mandatoryTimeMonthlyDateTime = RIODateTimeFactory::getDateTime();
        $mandatoryTimeMonthlyArray = $this->stringTimeToIntArray($mandatoryTimeMonthly);
        $mandatoryTimeMonthlyDateTime->setTime($mandatoryTimeMonthlyArray["hour"], $mandatoryTimeMonthlyArray["minute"]);
        if($isTimeMonthlyDateTime > $mandatoryTimeMonthlyDateTime) {
            $positiveNegative .= "+";
        } else {
            $positiveNegative .= "-";
        }
        $h = $mandatoryTimeMonthlyDateTime->diff($isTimeMonthlyDateTime)->h;
        $m = $mandatoryTimeMonthlyDateTime->diff($isTimeMonthlyDateTime)->i;
        $hourFinal = $h <= 9 ? '0'.$h : $h;
        $minuteFinal = $m <= 9 ? '0'.$m : $m;
        return $positiveNegative.$hourFinal.':'.$minuteFinal;
    }

    /**
     * Month of a year is unique
     *
     * @param string $monthYear
     * @param string $username
     * @return array
     * @throws Exception
     */
    /*
    private function getUserAllPastWorkdaysByMonthYearUser(string $monthYear, string $username): array
    {
        $user = $this->getUsers()->findOne(['sessionUsername' => $username]);
        $currentWorkDay = new RIOWorkDayObject();
        $allWorkDaysFromUser = $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime("01.".$monthYear)->format("Y"),$username)->find(["monthYear" => $monthYear])->toArray();
        return $this->getPastWorkDaysUser($allWorkDaysFromUser, $currentWorkDay, $user);
    }*/

    /**
     * Week of a year is unique
     *
     * @param string $weekYear
     * @param string $username
     * @return array
     * @throws \Exception
     */
    /*
    private function getUserAllPastWorkdaysByWeek(string $weekYear, string $username): array
    {
        $user = new RIOUserObject($controller);
        $currentWorkDay = new RIOWorkDayObject();
        $allWorkDaysFromUser = $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime("01.01.".explode('.',$weekYear)[1])->format("Y"),$user->getUsername())->find(["sessionUsername" => $user->getUsername(), "weekYear" => $weekYear])->toArray();
        return $this->getPastWorkDays($allWorkDaysFromUser, $currentWorkDay, $user);
    }*/
}