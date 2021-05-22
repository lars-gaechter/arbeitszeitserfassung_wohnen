<?php

use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Model\BSONDocument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Twig\Environment;

class RIOGeneralAccessController
{

    private Environment $twig;
    private Request $request;
    private string $directoryNamespace;
    private RIOMongoDatabase $mongoDatabase;
    private DateTime $dateTime;

    public function __construct(string $directoryNamespace, Environment $twig, Request $request)
    {
        $this->twig = $twig;
        $this->request = $request;
        $this->directoryNamespace = $directoryNamespace;
        $this->mongoDatabase = RIOMongoDatabase::getInstance();
        $this->dateTime = RIODateTimeFactory::getDateTime();
    }

    /**
     * @return DateTime
     */
    public function getDateTime(): DateTime
    {
        return $this->dateTime;
    }

    public function getSession(): SessionInterface
    {
        return $this->getRequest()->getSession();
    }

    /**
     * Current session user with current timestamp
     *
     * @return array
     * @throws Exception
     * @throws TransportExceptionInterface
     */
    public function getTimeRecording(): array
    {
        $mandatoryTime = RIODateTimeFactory::getDateTime($this->getUser()["mandatoryTime"]);
        $workDay = new RIOWorkDayObject();
        if($workDay->isRIOHoliday() || $workDay->isSunday() || $workDay->isSaturday()) {
            $workDay->getAbsentAllDay()->setOfficialHoliday();
            $workDay->getAbsentMorning()->setOfficialHoliday();
            $workDay->getAbsentAfternoon()->setOfficialHoliday();
            $mandatoryTime->setTime(0,0);
        }
        return [
            'date' => $this->dateTime->format("d.m.Y"),
            'monthYear' => $this->dateTime->format("m.Y"),
            'weekYear' => $this->dateTime->format("W.Y"),
            'month' => $this->dateTime->format("m"),
            'week' => $this->dateTime->format("W"),
            $this->getMandatoryTimeKey() => $mandatoryTime->format("H:i"),
            $this->getMandatoryTimeCorrectedKey() => '',
            $this->getTimeCreditKey() => '00:00',
            $this->getTimeCreditCorrectedKey() => '',
            'absentAllDay' => $workDay->getAbsentAllDay()->getOption(),
            'absentAfternoon' => $workDay->getAbsentAfternoon()->getOption(),
            'absentMorning' => $workDay->getAbsentMorning()->getOption(),
            'time' => [
                [
                    'start' => $this->dateTime->format("H:i"),
                    'startCorrected' => '',
                    'comment' => '',
                    'lastEditedUser' => '',
                    'lastEditedDate' => '',
                    'lastEditedTime' => ''
                ]
            ]
        ];
    }

    public function getTimeRecordingFilter(): array
    {
        return [
            'date' => $this->dateTime->format("d.m.Y")
        ];
    }

    public function getTimeRecordingFilterStarted(): array
    {
        return [
            'date' => $this->dateTime->format("d.m.Y")
        ];
    }

    public function getTimeRecordingStart(): BSONDocument
    {
        return new BSONDocument([
            'start' => $this->dateTime->format("H:i"),
            'startCorrected' => '',
            'comment' => '',
            'lastEditedUser' => '',
            'lastEditedDate' => '',
            'lastEditedTime' => ''
        ]);
    }

    public function getTimeRecordingEnd(): array
    {
        return [
            $this->getTimeRecordingEndKey() => $this->getTimeRecordingEndValue()
        ];
    }

    public function getAbsentAllDayKey(): string
    {
        return "absentAllDay";
    }

    public function getAbsentMorningKey(): string
    {
        return "absentMorning";
    }

    public function getAbsentAfternoonKey(): string
    {
        return "absentAfternoon";
    }

    public function getTimeRecordingStartKey(): string
    {
        return 'start';
    }

    public function getTimeRecordingStartCorrectedKey(): string
    {
        return 'startCorrected';
    }

    public function getTimeRecordingEndKey(): string
    {
        return 'end';
    }

    public function getTimeRecordingEndCorrectedKey(): string
    {
        return 'endCorrected';
    }

    public function getPresenceTimeKey(): string
    {
        return 'presenceTime';
    }

    public function getTimeCreditKey(): string
    {
        return "timeCredit";
    }

    public function getTimeCreditCorrectedKey(): string
    {
        return "timeCreditCorrected";
    }

    public function getIsTimeKey(): string
    {
        return 'isTime';
    }

    public function getDiffKey(): string
    {
        return 'diff';
    }

    public function getDiffNegativePositiveKey(): string
    {
        return 'diffNegativePositive';
    }

    public function getMandatoryTimeKey(): string
    {
        return 'mandatoryTime';
    }

    public function getMandatoryTimeCorrectedKey(): string
    {
        return 'mandatoryTimeCorrected';
    }

    public function getMandatoryTimeMonthlyKey(): string
    {
        return "mandatoryTimeMonthly";
    }

    public function getMandatoryTimeTotalKey(): string
    {
        return "mandatoryTimeTotal";
    }

    public function getMandatoryTimeWeeklyKey(): string
    {
        return "mandatoryTimeWeekly";
    }

    public function getDeviationTimeMonthlyKey(): string
    {
        return "deviationTimeMonthly";
    }

    public function getDeviationTimeTotalKey(): string
    {
        return "deviationTimeTotal";
    }

    public function getDeviationTimeWeeklyKey(): string
    {
        return "deviationTimeWeekly";
    }

    public function getDeviationTimeTotalCorrectedKey(): string
    {
        return "deviationTimeTotalCorrected";
    }

    public function getWorkingTimePerformedKey(): string
    {
        return 'workingTimePerformed';
    }

    public function getWorkingTimePerformedCorrectedKey(): string
    {
        return 'workingTimePerformedCorrected';
    }

    public function getPresenceTimeCorrectedKey(): string
    {
        return 'presenceTimeCorrected';
    }

    public function getPresenceTimeTotalCorrectedKey(): string
    {
        return "presenceTimeTotalCorrected";
    }

    public function getTimeRecordingEndValue(): string
    {
        return $this->dateTime->format("H:i");
    }

    public function getDate(): array
    {
        return [
            'date' => $this->dateTime->format("d.m.Y")
        ];
    }

    public function getLastMonth(): array
    {
        $month = ((int)$this->dateTime->format("m"))-1;
        $monthString = (string)$month;
        $monthFinal = $month <= 9 ? '0'.$monthString : $monthString;
        return [
            "month" => $monthFinal
        ];
    }

    public function getMonth(): array
    {
        return [
            "month" => $this->dateTime->format("m")
        ];
    }

    /**
     * @param string $stringTime
     * @return int[]
     */
    public function stringTimeToIntArray(string $stringTime): array
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
     * @throws \Exception
     */
    public function calculationOverTwentyfourHours(DateTime $dateTime, array $time, DateTime $addDateTime): string
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

    /**
     * Every workday of an user is unique
     *
     * @param RIOMain|RIOAdmin $controller
     * @return array
     * @throws Exception
     */
    public function getUserAllPastWorkdays(RIOMain|RIOAdmin $controller): array
    {
        $user = new RIOUserObject($controller);
        $currentWorkDay = new RIOWorkDayObject();
        $allWorkDaysFromUser = $this->getWorkDays()->find(["sessionUsername" => $user->getUsername()])->toArray();
        return $this->getPastWorkDays($allWorkDaysFromUser, $currentWorkDay, $user);
    }

    /**
     * Month of a year is unique
     *
     * @param string $monthYear
     * @param RIOMain|RIOAdmin $controller
     * @return array
     * @throws Exception
     */
    public function getUserAllPastWorkdaysByMonthYearUser(string $monthYear, string $username): array
    {
        /** @var BSONDocument $user */
        $user = $this->getUsers()->findOne(['sessionUsername' => $username]);
        $currentWorkDay = new RIOWorkDayObject();
        $allWorkDaysFromUser = $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime("01.".$monthYear)->format("Y"),$username)->find(["monthYear" => $monthYear])->toArray();
        $pastWorkDaysUser = $this->getPastWorkDaysUser($allWorkDaysFromUser, $currentWorkDay, $user);
        echo "<pre>";
        var_dump($pastWorkDaysUser);
        echo "</pre>";
        return $pastWorkDaysUser;
    }

    /**
     * Month of a year is unique
     *
     * @param string $monthYear
     * @param RIOMain|RIOAdmin $controller
     * @return array
     * @throws Exception
     */
    public function getUserAllPastWorkdaysByMonthYear(string $monthYear, RIOMain|RIOAdmin $controller): array
    {
        $user = new RIOUserObject($controller);
        $currentWorkDay = new RIOWorkDayObject();
        $allWorkDaysFromUser = $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime("01.".$monthYear)->format("Y"),$user->getUsername())->find(["sessionUsername" => $user->getUsername(), "monthYear" => $monthYear])->toArray();
        return $this->getPastWorkDays($allWorkDaysFromUser, $currentWorkDay, $user);
    }

    /**
     * Week of a year is unique
     *
     * @param string $weekYear
     * @param RIOMain|RIOAdmin $controller
     * @return array
     * @throws Exception
     */
    public function getUserAllPastWorkdaysByWeek(string $weekYear, RIOMain|RIOAdmin $controller): array
    {
        $user = new RIOUserObject($controller);
        $currentWorkDay = new RIOWorkDayObject();
        $allWorkDaysFromUser = $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime("01.01.".explode('.',$weekYear)[1])->format("Y"),$user->getUsername())->find(["sessionUsername" => $user->getUsername(), "weekYear" => $weekYear])->toArray();
        return $this->getPastWorkDays($allWorkDaysFromUser, $currentWorkDay, $user);
    }

    /**
     * @throws \Exception
     */
    private function getPastWorkDaysUser(array $allWorkDaysFromUser, RIOWorkDayObject $currentWorkDay, BSONDocument $user): array
    {
        $allWorkDaysFromUserPast = [];
        foreach ($allWorkDaysFromUser as $OneWorkDayFromUser) {
            $maybePastWorkDay = new RIOWorkDayObject();
            $maybePastWorkDay->setDate(RIODateTimeFactory::getDateTime($OneWorkDayFromUser["date"]));
            $pastDayDiff = $currentWorkDay->getDate()->diff($maybePastWorkDay->getDate(), true)->d;
            $pastMonthDiff = $currentWorkDay->getDate()->diff($maybePastWorkDay->getDate(), true)->m;
            $pastYearDiff = $currentWorkDay->getDate()->diff($maybePastWorkDay->getDate(), true)->y;
            if(0 !== $pastDayDiff || 0 !== $pastMonthDiff || 0 !== $pastYearDiff) {
                $allWorkDaysFromUserPast[] = $OneWorkDayFromUser;
            }
        }
        usort($allWorkDaysFromUserPast, function($a, $b) {
            return RIODateTimeFactory::getDateTime($a['date']) <=> RIODateTimeFactory::getDateTime($b['date']);
        });
        $OneWorkDayFromUserPastIndexed = [];
        $i = 0;
        foreach ($allWorkDaysFromUserPast as $OneWorkDayFromUserPast) {
            $OneWorkDayFromUserPast["presenceTimeCorrections"] = [$user->offsetGet("sessionUsername"), $OneWorkDayFromUserPast["date"]];
            $OneWorkDayFromUserPastIndexed[] = $OneWorkDayFromUserPast;
            $i++;
        }
        return $OneWorkDayFromUserPastIndexed;
    }

    private function getPastWorkDays(array $allWorkDaysFromUser, RIOWorkDayObject $currentWorkDay, RIOUserObject $user): array
    {
        $allWorkDaysFromUserPast = [];
        foreach ($allWorkDaysFromUser as $OneWorkDayFromUser) {
            $maybePastWorkDay = new RIOWorkDayObject();
            $maybePastWorkDay->setDate(RIODateTimeFactory::getDateTime($OneWorkDayFromUser["date"]));
            $pastDayDiff = $currentWorkDay->getDate()->diff($maybePastWorkDay->getDate(), true)->d;
            $pastMonthDiff = $currentWorkDay->getDate()->diff($maybePastWorkDay->getDate(), true)->m;
            $pastYearDiff = $currentWorkDay->getDate()->diff($maybePastWorkDay->getDate(), true)->y;
            if(0 !== $pastDayDiff || 0 !== $pastMonthDiff || 0 !== $pastYearDiff) {
                $allWorkDaysFromUserPast[] = $OneWorkDayFromUser;
            }
        }
        usort($allWorkDaysFromUserPast, function($a, $b) {
            return RIODateTimeFactory::getDateTime($a['date']) <=> RIODateTimeFactory::getDateTime($b['date']);
        });
        $OneWorkDayFromUserPastIndexed = [];
        $i = 0;
        foreach ($allWorkDaysFromUserPast as $OneWorkDayFromUserPast) {
            $OneWorkDayFromUserPast["presenceTimeCorrections"] = [$user->getUsername(), $OneWorkDayFromUserPast["date"]];
            $OneWorkDayFromUserPastIndexed[] = $OneWorkDayFromUserPast;
            $i++;
        }
        return $OneWorkDayFromUserPastIndexed;
    }

    /**
     * Current session user with session id
     * @return array
     */
    public function getUser(): array
    {
        $username = $this->getSession()->get("username");
        $sessionId = $this->getSession()->getId();
        $timeRecordStarted = false;
        $findUser = $this->getUsers()->findOne([ "sessionId" => $sessionId ]);
        if(null === $username) {
            if(null !== $findUser) {
                if($findUser->offsetExists("sessionUsername")) {
                    $username = $findUser->offsetGet("sessionUsername");
                } else {
                    $username = '';
                }
            } else {
                $username = '';
            }
        }
        if(null !== $findUser){
            $timeRecordStarted = [
                'timeRecordStarted' => $this->getUsers()->findOne(
                    [ "sessionUsername" => $username ]
                )["timeRecordStarted"]
            ];
            $timeRecordStarted = $timeRecordStarted["timeRecordStarted"];
        }
        $user = [
            'sessionUsername' => $username,
            'sessionId' => null === $sessionId ? '' : $sessionId,
            'timeRecordStarted' => $timeRecordStarted,
        ];
        if(null !== $findUser) {
            $user = array_merge(
                $user,
                [
                    'mandatoryTime' => $findUser["mandatoryTime"]
                ]
            );
        }
        return $user;
    }

    /**
     * @return array
     */
    public function getUserFromSession(): array
    {
        $sessionId = $this->getSession()->getId();
        return [
            'sessionId' => null === $sessionId ? '' : $sessionId
        ];
    }

    public function getUsers(): Collection
    {
        return $this->getMongoDatabase()->getUsersCollection();
    }

    public function getWorkDays(): Collection
    {
        return $this->getMongoDatabase()->getWorkDaysCollection();
    }

    /**
     * @return Collection[]
     * @throws Exception
     */
    public function getWorkDaysFromUser(string $username): array
    {
        $currentYear = RIODateTimeFactory::getDateTime()->format("Y");
        $collections = [];
        for($startYear = $_ENV["LAUNCH_YEAR"]; $startYear <= $currentYear; $startYear++) {
            if($this->workDaysByYearUserExists($startYear, $username)) {
                $collections[] = $this->getWorkDaysByYearUser($startYear, $username);
            }
        }
        return $collections;
    }

    public function workDaysByYearUserExists(string $year, string $username): bool
    {
        return 0 !== $this->getMongoDatabase()->getWorkDaysCollectionByYearUser($year, $username)->countDocuments();
    }

    public function getWorkDaysByYearUser(string $year, string $username): Collection
    {
        return $this->getMongoDatabase()->getWorkDaysCollectionByYearUser($year, $username);
    }

    /**
     * @throws Exception
     */
    public function getWorkDaysByUser(): array
    {
        $collections = $this->getWorkDaysFromUser($this->getUser()['sessionUsername']);
        $collectionsArray = [];
        foreach ($collections as $collection) {
            $collectionsArray[] = $collection->find(
                [
                    "sessionUsername" => $this->getUser()['sessionUsername']
                ]
            )->toArray();
        }
        return $collectionsArray;
    }

    final protected function twig(): Environment
    {
        return $this->twig;
    }

    public function getDirectoryNamespaceString(): string
    {
        if(isset($this->directoryNamespace)) {
            return $this->directoryNamespace;
        }
        return "";
    }

    final protected function getRequest(): Request
    {
        return $this->request;
    }

    /**
     * @return RIOMongoDatabase
     */
    public function getMongoDatabase(): RIOMongoDatabase
    {
        return $this->mongoDatabase;
    }

    public function getDatabase(): Database
    {
        return $this->getMongoDatabase()->getDatabase();
    }

    public function selectCollection($collectionName): Collection
    {
        return $this->mongoDatabase->getDatabase()->selectCollection($collectionName);
    }
}