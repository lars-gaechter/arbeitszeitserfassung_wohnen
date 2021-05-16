<?php

use MongoDB\Collection;
use MongoDB\Database;
use MongoDB\Model\BSONDocument;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Twig\Environment;

class GeneralAccessController
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
        $mandatoryTime = RIODateTimeFactory::getDateTime($this->getUser()["mandatory_time"]);
        $workDay = new RIOWorkDayObject();
        if($workDay->isRIOHoliday() || $workDay->isSunday()) {
            $workDay->getAbsentAllDay()->setOfficialHoliday();
            $workDay->getAbsentMorning()->setOfficialHoliday();
            $workDay->getAbsentAfternoon()->setOfficialHoliday();
            $mandatoryTime->setTime(0,0);
        }
        return [
            'date' => $this->dateTime->format("d.m.Y"),
            'month_year' => $this->dateTime->format("m.Y"),
            'week_year' => $this->dateTime->format("W.Y"),
            'month' => $this->dateTime->format("m"),
            'week' => $this->dateTime->format("W"),
            $this->getMandatoryTimeKey() => $mandatoryTime->format("H:i"),
            $this->getMandatoryTimeCorrectedKey() => '',
            'time_credit' => '00:00',
            'time_credit_corrected' => '',
            'absent_all_day' => $workDay->getAbsentAllDay()->getOption(),
            'absent_afternoon' => $workDay->getAbsentAfternoon()->getOption(),
            'absent_morning' => $workDay->getAbsentMorning()->getOption(),
            'time' => [
                [
                    'start' => $this->dateTime->format("H:i"),
                    'start_corrected' => '',
                    'comment' => '',
                    'last_edited_user' => '',
                    'last_edited_date' => '',
                    'last_edited_time' => ''
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
            'start_corrected' => '',
            'comment' => '',
            'last_edited_user' => '',
            'last_edited_date' => '',
            'last_edited_time' => ''
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
        return "absent_all_day";
    }

    public function getAbsentMorningKey(): string
    {
        return "absent_morning";
    }

    public function getAbsentAfternoonKey(): string
    {
        return "absent_afternoon";
    }

    public function getTimeRecordingStartKey(): string
    {
        return 'start';
    }

    public function getTimeRecordingStartCorrectedKey(): string
    {
        return 'start_corrected';
    }

    public function getTimeRecordingEndKey(): string
    {
        return 'end';
    }

    public function getTimeRecordingEndCorrectedKey(): string
    {
        return 'end_corrected';
    }

    public function getPresenceTimeKey(): string
    {
        return 'presence_time';
    }

    public function getIsTimeKey(): string
    {
        return 'is_time';
    }

    public function getDiffKey(): string
    {
        return 'diff';
    }

    public function getDiffNegativePositiveKey(): string
    {
        return 'diff_negative_positive';
    }

    public function getMandatoryTimeKey(): string
    {
        return 'mandatory_time';
    }

    public function getMandatoryTimeCorrectedKey(): string
    {
        return 'mandatory_time_corrected';
    }

    public function getWorkingTimePerformedKey(): string
    {
        return 'working_time_performed';
    }

    public function getWorkingTimePerformedCorrectedKey(): string
    {
        return 'working_time_performed_corrected';
    }

    public function getPresenceTimeCorrectedKey(): string
    {
        return 'presence_time_corrected';
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

    /**
     * Every workday of an user is unique
     *
     * @param Main|Admin $controller
     * @return array
     * @throws Exception
     */
    public function getUserAllPastWorkdays(Main|Admin $controller): array
    {
        $user = new RIOUserObject($controller);
        $currentWorkDay = new RIOWorkDayObject();
        $allWorkDaysFromUser = $this->getWorkDays()->find(["session_username" => $user->getUsername()])->toArray();
        return $this->getPastWorkDays($allWorkDaysFromUser, $currentWorkDay, $user);
    }

    /**
     * Month of a year is unique
     *
     * @param string $monthYear
     * @param Main|Admin $controller
     * @return array
     * @throws Exception
     */
    public function getUserAllPastWorkdaysByMonthYearUser(string $monthYear, string $username): array
    {
        /** @var BSONDocument $user */
        $user = $this->getUsers()->findOne(['session_username' => $username]);
        $currentWorkDay = new RIOWorkDayObject();
        $allWorkDaysFromUser = $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime("01.".$monthYear)->format("Y"),$username)->find(["month_year" => $monthYear])->toArray();
        return $this->getPastWorkDaysUser($allWorkDaysFromUser, $currentWorkDay, $user);
    }

    /**
     * Month of a year is unique
     *
     * @param string $monthYear
     * @param Main|Admin $controller
     * @return array
     * @throws Exception
     */
    public function getUserAllPastWorkdaysByMonthYear(string $monthYear, Main|Admin $controller): array
    {
        $user = new RIOUserObject($controller);
        $currentWorkDay = new RIOWorkDayObject();
        $allWorkDaysFromUser = $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime("01.".$monthYear)->format("Y"),$user->getUsername())->find(["session_username" => $user->getUsername(), "month_year" => $monthYear])->toArray();
        return $this->getPastWorkDays($allWorkDaysFromUser, $currentWorkDay, $user);
    }

    /**
     * Week of a year is unique
     *
     * @param string $weekYear
     * @param Main|Admin $controller
     * @return array
     * @throws Exception
     */
    public function getUserAllPastWorkdaysByWeek(string $weekYear, Main|Admin $controller): array
    {
        $user = new RIOUserObject($controller);
        $currentWorkDay = new RIOWorkDayObject();
        $allWorkDaysFromUser = $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime("01.01.".explode('.',$weekYear)[1])->format("Y"),$user->getUsername())->find(["session_username" => $user->getUsername(), "week_year" => $weekYear])->toArray();
        return $this->getPastWorkDays($allWorkDaysFromUser, $currentWorkDay, $user);
    }

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
            $OneWorkDayFromUserPast["presencetimecorrections"] = [$user->offsetGet("session_username"), $OneWorkDayFromUserPast["date"]];
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
            $OneWorkDayFromUserPast["presencetimecorrections"] = [$user->getUsername(), $OneWorkDayFromUserPast["date"]];
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
        $findUser = $this->getUsers()->findOne([ "session_username" => $username, "session_id" => $sessionId ]);
        if(null !== $findUser){
            $timeRecordStarted = [
                'time_record_started' => $this->getUsers()->findOne(
                    [ "session_username" => $username ]
                )["time_record_started"]
            ];
            $timeRecordStarted = $timeRecordStarted["time_record_started"];
        }
        $user = [
            'session_username' => null === $username ? '' : $username,
            'session_id' => null === $sessionId ? '' : $sessionId,
            'time_record_started' => $timeRecordStarted,
        ];
        if(null !== $findUser) {
            $user = array_merge(
                $user,
                [
                    'mandatory_time' => $findUser["mandatory_time"]
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
        $username = $this->getSession()->get("username");
        $sessionId = $this->getSession()->getId();
        return [
            'session_username' => null === $username ? '' : $username,
            'session_id' => null === $sessionId ? '' : $sessionId
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
        $collections = $this->getWorkDaysFromUser($this->getUser()['session_username']);
        $collectionsArray = [];
        foreach ($collections as $collection) {
            $collectionsArray[] = $collection->find(
                [
                    "session_username" => $this->getUser()['session_username']
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