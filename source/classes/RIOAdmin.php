<?php

use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

/**
 * Class RIOAdmin
 * User must be in logged in state, otherwise user can't change other users time record
 * This area RIOAdmin should be used for editing other users past or feature data
 * Like other users view overview, editing mandatory time and change there presence in the past
 */
class RIOAdmin extends RIOAccessController
{
    public function __construct(
        string $directory_namespace,
        Environment $twig,
        Request $request
    ) {
        parent::__construct($directory_namespace, $twig, $request);
    }

    /**
     * Delete current user authentication with saved session id and username in mongodb
     *
     * @return Response
     * @throws Exception
     */
    public function logout(): Response
    {
        $user = new RIOUserObject($this);
        $this->getUsers()->updateOne(
            [ "sessionUsername" => $user->getUsername(), "sessionId" => $user->getSessionId() ],
            [
                '$set' => [ 'sessionId' => '' ]
            ]
        );
        $this->getSession()->invalidate();
        return RIORedirect::redirectResponse();
    }

    /**
     * @param string $location
     * @param string $date
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface
     * @throws \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function getHoliday(string $location, string $date): Response
    {
        $workDayObject = new RIOWorkDayObject();
        return new Response($workDayObject->getHoliday($location, $date)->getContent());
    }

    /**
     * Tries to login user by current session if saved
     *
     * @return Response
     * @throws Exception
     */
    public function sessionLogin(): Response
    {
        return $this->showUser();
    }

    /**
     * @throws Exception
     */
    private function showUser(): Response
    {
        $user = new RIOUserObject($this);
        $workday = new RIOWorkDayObject();
        $monthYear = $workday->getDate()->format("m.Y");
        $customTwigExtension = new RIOCustomTwigExtension($this->getRequest());
        return $this->renderPage(
            "user_home.twig",
            array_merge_recursive(
                $customTwigExtension->navByActive($user->getUsername(), $monthYear, "user_home"),
                [
                    "timeRecordStarted" => $user->isTimeRecordStarted(),
                    "day" => $workday->getWeekDay(),
                    "date" => $workday->getFormattedDate(),
                    'displayUsername' => $this->getUsers()->findOne($this->getUserFromSession())["displayUsername"],
                    'monthYear' => $monthYear,
                    'sessionUsername' => $user->getUsername()
                ]
            )

        );
    }

    /**
     * @throws Exception
     */
    public function updateUser(string $username): RedirectResponse
    {
        /** @var BSONDocument $user */
        $user = $this->getUsers()->findOne(['sessionUsername' => $username]);
        $request = $this->getRequest();
        $mandatoryTime = $request->get("mandatoryTime");
        if(null !== $mandatoryTime && '' !== $mandatoryTime) {
            $user->offsetSet("mandatoryTime",RIODateTimeFactory::getDateTime($mandatoryTime));
            /** @var DateTime $updatedMandatoryTime */
            $updatedMandatoryTime = $user->offsetGet("mandatoryTime");
            $this->getUsers()->findOneAndUpdate(
                ["sessionUsername" => $user->offsetGet("sessionUsername")],
                ['$set' => ['mandatoryTime' => $updatedMandatoryTime->format("H:i")]]
            );
        }
        return RIORedirect::redirectResponse(['rioadmin', 'edituser', $username]);
    }

    public function editUser(string $username): RedirectResponse|Response
    {
        /** @var BSONDocument $user */
        $user = $this->getUsers()->findOne(['sessionUsername' => $username]);
        $workday = new RIOWorkDayObject();
        $monthYear = $workday->getDate()->format("m.Y");
        $customTwigExtension = new RIOCustomTwigExtension($this->getRequest());
        return $this->renderPage(
            "edit_user.twig",
            array_merge(
                $customTwigExtension->navByActive($user->offsetGet("sessionUsername"), $monthYear, "edit_user"),
                [
                    "mandatoryTime" => $user->offsetGet("mandatoryTime"),
                    'monthYear' => $monthYear,
                    'sessionUsername' => $user->offsetGet("sessionUsername")
                ]
            )
        );
    }

    /**
     * Means: date like 21.04.2021 * index of workday * index of workday single time with start and end timestamp
     *
     * @param string $username
     * @param string $date
     * @param string $indexOfTime
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function presenceTimeCorrections(string $username, string $date, string $indexOfTime): RedirectResponse|Response
    {
        /** @var BSONDocument $user */
        $user = $this->getUsers()->findOne(['sessionUsername' => $username]);
        if(null === $user) {
            throw new Error("User with this username ".$username." doesn't exist.");
        }
        $workDays = $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime($date)->format("Y"),$username);
        if(null === $workDays) {
            throw new Error("User ".$username." has not workday collection for the year ".RIODateTimeFactory::getDateTime($date)->format("Y"));
        }
        $givenTime = ["date" => $date];
        /** @var BSONDocument $workDay */
        $workDay = $workDays->findOne($givenTime);
        /** @var BSONArray $times */
        $times = $workDay->offsetGet("time");
        /** @var BSONDocument $time */
        $time = $times->offsetGet($indexOfTime);
        $workdayObject = new RIOWorkDayObject();
        $monthYear = $workdayObject->getDate()->format("m.Y");
        $customTwigExtension = new RIOCustomTwigExtension($this->getRequest());
        return $this->renderPage(
            "presence_time_corrections.twig",
            array_merge(
                $customTwigExtension->navByActive($username, $monthYear),
                [
                    "date" => $date,
                    "time" => $time,
                    "timeStart" => '' === $time->offsetGet("startCorrected") ? $time->offsetGet("start") : $time->offsetGet("startCorrected"),
                    "timeStartCorrected" => $time->offsetGet("startCorrected"),
                    "timeEnd" => '' === $time->offsetGet("endCorrected") ? $time->offsetGet("end") : $time->offsetGet("endCorrected"),
                    "timeEndCorrected" => $time->offsetGet("endCorrected"),
                    'displayUsername' => $user->offsetGet("displayUsername"),
                    'surnameUsername' => $user->offsetGet("surnameUsername"),
                    'presenceTime' => '' === $time->offsetGet("presenceTimeCorrected") ? $time->offsetGet("presenceTime") : $time->offsetGet("presenceTimeCorrected"),
                    'presenceTimeCorrected' => $time->offsetGet("presenceTimeCorrected"),
                    'absentOptions' => RIOAbsentOptionObject::getOptions(),
                    'absentAllDay' => $workDay->offsetGet("absentAllDay"),
                    'absentAfternoon' => $workDay->offsetGet("absentAfternoon"),
                    'absentMorning' => $workDay->offsetGet("absentMorning"),
                    'comment' => $time->offsetGet("comment"),
                    "mandatoryTime" => $workDay->offsetGet("mandatoryTime"),
                    'workingTimePerformedCorrected' => $time->offsetGet("workingTimePerformedCorrected"),
                    'workingTimePerformed' => '' === $time->offsetGet("workingTimePerformedCorrected") ? $time->offsetGet("workingTimePerformed") : $time->offsetGet("workingTimePerformedCorrected"),
                    'presenceTimeTotal' => $workDay->offsetGet("presenceTime"),
                    $this->getDeviationTimeTotalKey() => $workDay->offsetGet("deviationTimeTotal"),
                    $this->getDeviationTimeMonthlyKey() => $workDay->offsetGet("deviationTimeMonthly"),
                    $this->getDeviationTimeWeeklyKey() => $workDay->offsetGet("deviationTimeWeekly"),
                    $this->getDeviationTimeTotalCorrectedKey() => $workDay->offsetGet("deviationTimeTotalCorrected"),
                    'deviation' => $workDay->offsetGet("deviation"),
                    'deviationNegativeOrPositiveOrZero' => $workDay->offsetGet("deviationNegativeOrPositiveOrZero"),
                    'timeCredit' => $workDay->offsetGet("timeCredit"),
                    'timeCreditCorrected' => $workDay->offsetGet("timeCreditCorrected"),
                    'monthYear' => $monthYear,
                    'usernameDateTimeIndex' => [$username, $date, $indexOfTime],
                    'lastEditedUser' => $time->offsetGet("lastEditedUser"),
                    'lastEditedDate' => $time->offsetGet("lastEditedDate"),
                    'lastEditedTime' => $time->offsetGet("lastEditedTime")
                ]
            )
        );
    }

    /**
     * @param string $username
     * @param string $monthYear
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function overview(string $username, string $monthYear): RedirectResponse|Response
    {
        /** @var BSONDocument $user */
        $user = $this->getUsers()->findOne(['sessionUsername' => $username]);
        $previousMonthYear = $this->getAdjacentMonth($monthYear);
        if([] !== $this->getUserAllPastWorkdaysByMonthYearUser($previousMonthYear, $username)) {
            $previousMonthYearName = $this->getFormattedDateByDate(RIODateTimeFactory::getDateTime("01.".$previousMonthYear));
        } else {
            $previousMonthYearName = '';
            $previousMonthYear = '';
        }
        $nextMonthYear = $this->getAdjacentMonth($monthYear, "next");
        if('' !== $nextMonthYear) {
            $nextMonthYearName = $this->getFormattedDateByDate(RIODateTimeFactory::getDateTime("01.".$nextMonthYear));
        } else {
            $nextMonthYearName = '';
            $nextMonthYear = '';
        }
        $customTwigExtension = new RIOCustomTwigExtension($this->getRequest());
        array_merge(
            $customTwigExtension->navByActive($user->offsetGet("sessionUsername"), $monthYear, "overview"),
            [
                "allWorkDaysFromUserPast" => $this->getUserAllPastWorkdaysByMonthYearUser($monthYear, $username),
                "previousMonthName" => $previousMonthYearName,
                "nextMonthName" => $nextMonthYearName,
                "currentMonthName" => $this->getFormattedDateByDate(RIODateTimeFactory::getDateTime("01.".$monthYear)),
                "previousMonth" => $previousMonthYear,
                "nextMonth" => $nextMonthYear,
                'displayUsername' => $user->offsetGet("displayUsername"),
                'surnameUsername' => $user->offsetGet("surnameUsername"),
                'sessionUsername' => $user->offsetGet("sessionUsername")
            ]
        );
        echo "test";
        die();
        return $this->renderPage(
            "overview.twig",
            array_merge(
                $customTwigExtension->navByActive($user->offsetGet("sessionUsername"), $monthYear, "overview"),
                [
                    "allWorkDaysFromUserPast" => $this->getUserAllPastWorkdaysByMonthYearUser($monthYear, $username),
                    "previousMonthName" => $previousMonthYearName,
                    "nextMonthName" => $nextMonthYearName,
                    "currentMonthName" => $this->getFormattedDateByDate(RIODateTimeFactory::getDateTime("01.".$monthYear)),
                    "previousMonth" => $previousMonthYear,
                    "nextMonth" => $nextMonthYear,
                    'displayUsername' => $user->offsetGet("displayUsername"),
                    'surnameUsername' => $user->offsetGet("surnameUsername"),
                    'sessionUsername' => $user->offsetGet("sessionUsername")
                ]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function updatePresenceTimeCorrections(string $username, string $date, string $indexOfTime): RedirectResponse|Response
    {
        // By default the form hasn't changed compared to database data
        $formHasChanged = false;

        // Get all from requests
        $request = $this->getRequest();
        $comment = $request->get("comment");
        $absentAllDay = $request->get($this->getAbsentAllDayKey());
        $absentMorning = $request->get($this->getAbsentMorningKey());
        $absentAfternoon = $request->get($this->getAbsentAfternoonKey());
        $mandatoryTimeCorrected = $request->get($this->getMandatoryTimeCorrectedKey());
        $startCorrected = $request->get($this->getTimeRecordingStartCorrectedKey());
        $endCorrected = $request->get($this->getTimeRecordingEndCorrectedKey());
        $timeCreditCorrected = $request->get($this->getTimeCreditCorrectedKey());
        $presenceTimeCorrected = $request->get($this->getPresenceTimeCorrectedKey());
        $workingTimePerformedCorrected = $request->get($this->getWorkingTimePerformedCorrectedKey());
        $deviationTimeTotal = $request->get($this->getDeviationTimeTotalKey());

        // Input validation
        if(RIODateTimeFactory::getDateTime($startCorrected) > RIODateTimeFactory::getDateTime($endCorrected)) {
            throw new Error("Start time cannot be after end time or end time before start time.");
        }

        if(false === array_search($absentAllDay, RIOAbsentOptionObject::getOptions(), true)) {
            throw new Error("The option ".$absentAllDay." doesn't exist for ".$this->getAbsentAllDayKey());
        }
        if(false === array_search($absentMorning, RIOAbsentOptionObject::getOptions(), true)) {
            throw new Error("The option ".$absentMorning." doesn't exist for ".$this->getAbsentMorningKey());
        }
        if(false === array_search($absentAfternoon, RIOAbsentOptionObject::getOptions(), true)) {
            throw new Error("The option ".$absentAfternoon." doesn't exist for ".$this->getAbsentAfternoonKey());
        }

        // Get database objects for this request
        $workDays = $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime($date)->format("Y"),$username);
        $givenTime = ["date" => $date];
        /** @var BSONDocument $workDay */
        $workDay = $workDays->findOne($givenTime);
        /** @var BSONArray $times */
        $times = $workDay->offsetGet("time");
        /** @var BSONDocument $time */
        $time = $times->offsetGet($indexOfTime);

        // Check if request data is different then database data
        if ($comment !== $time->offsetGet("comment")) {
            $time->offsetSet("comment", $comment);
            $formHasChanged = true;
        }
        if ($absentAllDay !== $workDay->offsetGet($this->getAbsentAllDayKey())) {
            $workDay->offsetSet($this->getAbsentAllDayKey(), $absentAllDay);
            $formHasChanged = true;
        }
        if ($absentMorning !== $workDay->offsetGet($this->getAbsentMorningKey())) {
            $workDay->offsetSet($this->getAbsentMorningKey(), $absentMorning);
            $formHasChanged = true;
        }
        if ($absentAfternoon !== $workDay->offsetGet($this->getAbsentAfternoonKey())) {
            $workDay->offsetSet($this->getAbsentAfternoonKey(), $absentAfternoon);
            $formHasChanged = true;
        }
        if ($startCorrected !== $time->offsetGet($this->getTimeRecordingStartCorrectedKey())) {
            $time->offsetSet($this->getTimeRecordingStartCorrectedKey(), $startCorrected);
            $formHasChanged = true;
        }
        if ($endCorrected !== $time->offsetGet($this->getTimeRecordingEndCorrectedKey())) {
            $time->offsetSet($this->getTimeRecordingEndCorrectedKey(), $endCorrected);
            $formHasChanged = true;
        }
        if ($mandatoryTimeCorrected !== $workDay->offsetGet($this->getMandatoryTimeCorrectedKey())) {
            $workDay->offsetSet($this->getMandatoryTimeCorrectedKey(), $mandatoryTimeCorrected);
            $formHasChanged = true;
        }

        if(
            false !== array_search($absentMorning, RIOAbsentOptionObject::getNoMandatoryTimeOptions(), true) ||
            false !== array_search($absentAfternoon, RIOAbsentOptionObject::getNoMandatoryTimeOptions(), true)
        ) {
            // Minus half of mandatory time
            $mandatoryTimeDateTime = RIODateTimeFactory::getDateTime($workDay->offsetGet($this->getMandatoryTimeKey()));
            $newHours = (int) $mandatoryTimeDateTime->format("H") / 2;
            $newMinutes = (int) $mandatoryTimeDateTime->format("i") / 2;
            $newMandatoryTimeDateTime = RIODateTimeFactory::getDateTime($newHours.':'.$newMinutes);
            $workDay->offsetSet($this->getMandatoryTimeKey(), $newMandatoryTimeDateTime->format("H:i"));
        }

        if(
            (
                false !== array_search($absentMorning, RIOAbsentOptionObject::getNoMandatoryTimeOptions(), true) &&
                false !== array_search($absentAfternoon, RIOAbsentOptionObject::getNoMandatoryTimeOptions(), true)
            ) ||
            false !== array_search($absentAllDay, RIOAbsentOptionObject::getNoMandatoryTimeOptions(), true)
        ) {
            // No mandatory time
            $workDay->offsetSet($this->getMandatoryTimeKey(), "00:00");
        }

        if (true === $formHasChanged) {
            // The form has changed
            $user = $this->getUsers()->findOne($this->getUserFromSession());
            $timestamp = RIODateTimeFactory::getDateTime();
            $time->offsetSet("lastEditedUser", $user->offsetGet("displayUsername") . ' ' . $user->offsetGet("surnameUsername"));
            $time->offsetSet("lastEditedDate", $timestamp->format("d.m.Y"));
            $time->offsetSet("lastEditedTime", $timestamp->format("H:i"));
            $times->offsetSet($indexOfTime, $time);
            $workDays->findOneAndUpdate(
                $givenTime,
                [
                    '$set' => [
                        'time' => $times,
                        $this->getAbsentAllDayKey() => $workDay->offsetGet($this->getAbsentAllDayKey()),
                        $this->getAbsentMorningKey() => $workDay->offsetGet($this->getAbsentMorningKey()),
                        $this->getAbsentAfternoonKey() => $workDay->offsetGet($this->getAbsentAfternoonKey()),
                        $this->getMandatoryTimeCorrectedKey() => $workDay->offsetGet($this->getMandatoryTimeCorrectedKey()),
                        $this->getMandatoryTimeKey() => $workDay->offsetGet($this->getMandatoryTimeKey())
                    ]
                ]
            );
        }

        $workday = new RIOWorkDayObject();
        $workday->setDate(RIODateTimeFactory::getDateTime($date));
        $monthYear = $workday->getDate()->format("m.Y");
        return RIORedirect::redirectResponse(["rioadmin", "overview", $username, $monthYear]);
    }

    public function getFormattedDateByDate(DateTime $date): string
    {
        $month = $date->format("m");
        $year = $date->format("Y");
        $monthNames = [
            "Januar",
            "Februar",
            "März",
            "April",
            "Mai",
            "Juni",
            "Juli",
            "August",
            "September",
            "Oktober",
            "November",
            "Dezember"
        ];
        $monthName = $monthNames[(int) $month-1];
        return $monthName." ".$year;
    }

    /**
     * @throws Exception
     */
    public function getAdjacentMonth(string $monthYear, string $djacent = "previous"): string
    {
        $date = RIODateTimeFactory::getDateTime("01.".$monthYear);
        $djacentMonth = null;
        if("previous" === $djacent) {
            $djacentMonth = (int)$date->format("m")-1;
            if(13 === $djacentMonth) {
                $djacentMonth = 12;
                $year = (int)$date->format("Y")-1;
            } else {
                $year = $date->format("Y");
            }
        }
        if("next" === $djacent) {
            $djacentMonth = (int)$date->format("m")+1;
            if(13 === $djacentMonth) {
                $djacentMonth = 1;
                $year = (int)$date->format("Y")+1;
            } else {
                $year = $date->format("Y");
            }
        }
        if(null === $djacentMonth || null === $year) {
            throw new Error("Wrong adjacent argument can be previous or next");
        }
        $currentDate = RIODateTimeFactory::getDateTime();
        $date->setDate($year, $djacentMonth, 1);
        if($date > $currentDate) {
            return '';
        }
        return $date->format("m.Y");
    }


    /**
     * Check if theres an work day exist for the session user
     *  if not then insert new work day with new start time
     *  if yes then update existing work day with mew start time
     *
     * @return RedirectResponse|Response
     * @throws Exception|\Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface
     */
    public function start(): RedirectResponse|Response
    {
        $user = new RIOUserObject($this);
        if($user->isTimeRecordStopped()){
            /** @var BSONDocument $findOneWorkDay */
            $findOneWorkDay = $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime()->format("Y"),$user->getUsername())->findOne(
                $this->getDate()
            );
            if(null === $findOneWorkDay) {
                $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime()->format("Y"),$user->getUsername())->insertOne(
                    $this->getTimeRecording()
                );
            } else {
                // Existing workday to update on one user
                $findOneWorkDay->offsetGet("time")[] = $this->getTimeRecordingStart();
                $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime()->format("Y"),$user->getUsername())->findOneAndUpdate(
                    $this->getTimeRecordingFilter(),
                    ['$set' => [ 'time' => $findOneWorkDay->offsetGet("time") ]]
                );
            }
            $this->getUsers()->updateOne(
                [ "sessionUsername" => $user->getUsername(), 'timeRecordStarted' => false ],
                ['$set' => [ 'timeRecordStarted' => true ]]
            );
        }
        return RIORedirect::redirectResponse(["rioadmin", "sessionlogin"]);
    }

    /**
     * @throws Exception
     */
    public function stop(): RedirectResponse|Response
    {
        $user = new RIOUserObject($this);
        if($user->isTimeRecordStarted()) {
            /** @var BSONDocument $findOneWorkDay */
            $findOneWorkDay = $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime()->format("Y"),$user->getUsername())->findOne($this->getDate());

            // Start for nightly cronjob
            /** @var BSONDocument[] $findWorkDaysFromLastMonth */
            $findWorkDaysFromLastMonth = $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime()->format("Y"),$user->getUsername())->find($this->getLastMonth())->toArray();
            /** @var BSONDocument[] $findWorkDaysFromThisMonth */
            $findWorkDaysFromThisMonth = $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime()->format("Y"),$user->getUsername())->find($this->getMonth())->toArray();


            /** @var BSONDocument $lastDayThisMonth */
            $lastDayThisMonth = end($findWorkDaysFromThisMonth);
            if(count($findWorkDaysFromThisMonth) >= 2) {
                $lastDayThisMonth = $findWorkDaysFromThisMonth[count($findWorkDaysFromThisMonth)-2];
            }


            $mandatoryTimeMonthly = $lastDayThisMonth->offsetGet($this->getMandatoryTimeMonthlyKey());
            $currentMandatoryTimeMonthly = RIODateTimeFactory::getDateTime();
            $time = $this->stringTimeToIntArray($mandatoryTimeMonthly);
            $mandatoryTime = $findOneWorkDay->offsetGet($this->getMandatoryTimeKey());
            $mandatoryTimeCorrected = $findOneWorkDay->offsetGet($this->getMandatoryTimeCorrectedKey());
            if('' !== $mandatoryTimeCorrected) {
                $mandatoryTimeCorrectedDateTime = RIODateTimeFactory::getDateTime($mandatoryTimeCorrected);
                $final = $this->calculationOverTwentyfourHours($currentMandatoryTimeMonthly, $time, $mandatoryTimeCorrectedDateTime);
            } else {
                $mandatoryTimeDateTime = RIODateTimeFactory::getDateTime($mandatoryTime);
                $final = $this->calculationOverTwentyfourHours($currentMandatoryTimeMonthly, $time, $mandatoryTimeDateTime);
            }
            $findOneWorkDay->offsetSet($this->getMandatoryTimeMonthlyKey(), $final);
            // End for nightly cronjob

            /** @var BSONArray $times */
            $times = $findOneWorkDay->offsetGet("time");
            /*** @var BSONArray $time */
            foreach ($times as $time) {
                $endTime = $this->getTimeRecordingEndValue();
                if(false === $time->offsetExists($this->getTimeRecordingEndKey())) {
                    $time->offsetSet($this->getTimeRecordingEndKey(), $endTime);
                }
                if(false === $time->offsetExists($this->getTimeRecordingEndCorrectedKey())) {
                    $time->offsetSet($this->getTimeRecordingEndCorrectedKey(), '');
                }
                $timeObject = new RIOTimeObject();
                $timeObject->setTimeStart(RIODateTimeFactory::getDateTime($time->offsetGet("start")));
                $timeObject->setTimeEnd(RIODateTimeFactory::getDateTime($endTime));
                $origin = $timeObject->getTimeStart();
                $target = $timeObject->getTimeEnd();
                $interval = $origin->diff($target);
                $presenceTime = RIODateTimeFactory::getDateTime($interval->h.':'.$interval->i);
                if(false === $time->offsetExists($this->getWorkingTimePerformedKey())) {
                    // TODO: workingTimePerformed take temporary the same value like presenceTime, actual calculation workingTimePerformed = presenceTime - breaks
                    $time->offsetSet($this->getWorkingTimePerformedKey(), $presenceTime->format("H:i"));
                }
                if(false === $time->offsetExists($this->getWorkingTimePerformedCorrectedKey())) {
                    $time->offsetSet($this->getWorkingTimePerformedCorrectedKey(), '');
                }
                if(false === $time->offsetExists($this->getPresenceTimeKey())) {
                    $time->offsetSet($this->getPresenceTimeKey(), $presenceTime->format("H:i"));
                }
                if(false === $time->offsetExists($this->getPresenceTimeCorrectedKey())) {
                    $time->offsetSet($this->getPresenceTimeCorrectedKey(), '');
                }
            }
            $start = "00:00";
            $startTime = RIODateTimeFactory::getDateTime($start);
            $presenceTimeTotal = RIODateTimeFactory::getDateTime($start);
            $times = $findOneWorkDay->offsetGet("time");
            /** @var BSONDocument $time */
            foreach ($times as $time) {
                if('' !== $time->offsetGet($this->getPresenceTimeCorrectedKey())) {
                    $addableTime = $time->offsetGet($this->getPresenceTimeCorrectedKey());
                } else {
                    if('' !== $time->offsetGet($this->getTimeRecordingStartCorrectedKey())) {
                        $addableTimeStart = $time->offsetGet($this->getTimeRecordingStartCorrectedKey());
                    } else {
                        $addableTimeStart = $time->offsetGet($this->getTimeRecordingStartKey());
                    }
                    if('' !== $time->offsetGet($this->getTimeRecordingEndCorrectedKey())) {
                        $addableTimeEnd  = $time->offsetGet($this->getTimeRecordingEndCorrectedKey());
                    } else {
                        $addableTimeEnd = $time->offsetGet($this->getTimeRecordingEndKey());
                    }
                    $start = RIODateTimeFactory::getDateTime($addableTimeStart);
                    $end = RIODateTimeFactory::getDateTime($addableTimeEnd);
                    $startEndDiff = RIODateTimeFactory::getDateTime();
                    /** @var DateInterval|false $diff */
                    $diff = $start->diff($end);
                    $startEndDiff->setTime($diff->h, $diff->i);
                    if($startEndDiff->format("H:i") === $time->offsetGet($this->getPresenceTimeKey())) {
                        $addableTime = $time->offsetGet($this->getPresenceTimeKey());
                    } else {
                        $addableTime = $startEndDiff->format("H:i");
                    }
                }
                $presenceTime = RIODateTimeFactory::getDateTime($addableTime);
                $presenceTimeTotal->add($startTime->diff($presenceTime));
                if(false === $time->offsetExists($this->getIsTimeKey())) {
                    $time->offsetSet($this->getIsTimeKey(), $presenceTimeTotal->format("H:i"));
                }
                if(false === $time->offsetExists($this->getDiffKey())) {
                    $isTimeMandatoryTimeTimeDiff = RIODateTimeFactory::getDateTime();
                    $mandatoryTime = $findOneWorkDay->offsetGet($this->getMandatoryTimeKey());
                    $mandatoryTimeTime = RIODateTimeFactory::getDateTime($mandatoryTime);
                    $isTime = RIODateTimeFactory::getDateTime($time->offsetGet($this->getIsTimeKey()));
                    $diffIsTimeMandatoryTimeTime = $mandatoryTimeTime->diff($isTime);
                    $isTimeMandatoryTimeTimeDiff->setTime($diffIsTimeMandatoryTimeTime->h, $diffIsTimeMandatoryTimeTime->i);
                    $time->offsetSet($this->getDiffKey(), $isTimeMandatoryTimeTimeDiff->format("H:i"));
                    $isTimeNegativeOrPositiveOrZero = '';
                    if($isTime->format("H:i") === $mandatoryTime) {
                        $isTimeNegativeOrPositiveOrZero .= ' ';
                    }
                    if($isTime->format("H:i") > $mandatoryTime) {
                        $isTimeNegativeOrPositiveOrZero .= '+';
                    }
                    if($isTime->format("H:i") < $mandatoryTime) {
                        $isTimeNegativeOrPositiveOrZero .= '-';
                    }
                    if(false === $time->offsetExists($this->getDiffNegativePositiveKey())) {
                        $time->offsetSet($this->getDiffNegativePositiveKey(), $isTimeNegativeOrPositiveOrZero);
                    }
                }


            }
            $mandatoryTime = RIODateTimeFactory::getDateTime($findOneWorkDay->offsetGet($this->getMandatoryTimeKey()));
            $deviationDiff = $mandatoryTime->diff($presenceTimeTotal);
            $deviation = RIODateTimeFactory::getDateTime();
            $deviation->setTime($deviationDiff->h, $deviationDiff->i);
            $deviationNegativeOrPositiveOrZero = '';
            if($presenceTimeTotal->format("H:i") === $findOneWorkDay->offsetGet($this->getMandatoryTimeKey())) {
                $deviationNegativeOrPositiveOrZero .= ' ';
            }
            if($presenceTimeTotal->format("H:i") > $findOneWorkDay->offsetGet($this->getMandatoryTimeKey())) {
                $deviationNegativeOrPositiveOrZero .= '+';
            }
            if($presenceTimeTotal->format("H:i") < $findOneWorkDay->offsetGet($this->getMandatoryTimeKey())) {
                $deviationNegativeOrPositiveOrZero .= '-';
            }
            $userMonth = $this->getUserAllPastWorkdaysByMonthYear($findOneWorkDay->offsetGet("monthYear"), $this);
            $userTotal = $this->getUserAllPastWorkdays($this);
            $userWeek = $this->getUserAllPastWorkdaysByWeek($findOneWorkDay->offsetGet("weekYear"), $this);
            $isTimeMonthly = RIODateTimeFactory::getDateTime();
            $isTimeMonthly->setTime(0, 0);
            $mandatoryTimeMonthly = RIODateTimeFactory::getDateTime();
            $mandatoryTimeMonthly->setTime(0, 0);
            $deviationTimeMonthly = RIODateTimeFactory::getDateTime();
            $deviationTimeMonthly->setTime(0, 0);
            /** @var BSONDocument $day */
            foreach ($userMonth as $day) {
                $dayTime = RIODateTimeFactory::getDateTime($day->offsetGet("presenceTime"));
                $dayMandatoryTime = RIODateTimeFactory::getDateTime($day->offsetGet("mandatoryTime"));
                $isTimeMonthly->add($startTime->diff($dayTime));
                $mandatoryTimeMonthly->add($startTime->diff($dayMandatoryTime));
            }
            $deviationTimeMonthly->add($mandatoryTimeMonthly->diff($isTimeMonthly));
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
            $isTimeWeekly = RIODateTimeFactory::getDateTime();
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
            $deviationTimeWeekly->add($mandatoryTimeWeekly->diff($isTimeWeekly));
            $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime()->format("Y"),$user->getUsername())->findOneAndUpdate(
                $this->getTimeRecordingFilterStarted(),
                ['$set' => [
                    'presenceTime' => $presenceTimeTotal->format("H:i"),
                    'presenceTimeCorrected' => '',
                    'time' => $findOneWorkDay->offsetGet("time"),
                    'deviation' => $deviation->format("H:i"),
                    'deviationNegativeOrPositiveOrZero' => $deviationNegativeOrPositiveOrZero,
                    'isTimeMonthly' => $isTimeMonthly->format("H:i"),
                    'isTimeTotal' => $isTimeTotal->format("H:i"),
                    'isTimeWeekly' => $isTimeWeekly->format("H:i"),
                    // for nightly cronjob
                    //$this->getMandatoryTimeMonthlyKey() => $mandatoryTimeMonthly->format("H:i"),
                    $this->getMandatoryTimeMonthlyKey() => $final,
                    $this->getMandatoryTimeTotalKey() => $mandatoryTimeTotal->format("H:i"),
                    $this->getMandatoryTimeWeeklyKey() => $mandatoryTimeWeekly->format("H:i"),
                    // for nightly cronjob
                    //$this->getDeviationTimeMonthlyKey() => $deviationTimeMonthly->format("H:i"),
                    //$this->getDeviationTimeTotalKey() => $deviationTimeTotal->format("H:i"),
                    $this->getDeviationTimeMonthlyKey() => $lastDayThisMonth->offsetGet($this->getDeviationTimeMonthlyKey()),
                    $this->getDeviationTimeTotalKey() => $lastDayThisMonth->offsetGet($this->getDeviationTimeTotalKey()),
                    $this->getDeviationTimeWeeklyKey() => $mandatoryTimeWeekly->format("H:i"),
                    $this->getDeviationTimeTotalCorrectedKey() => '',
                ]
                ]
            );
            $this->getUsers()->findOneAndUpdate(
                ["sessionUsername" => $user->getUsername(), 'timeRecordStarted' => true],
                ['$set' => ['timeRecordStarted' => false]]
            );
        }
        return RIORedirect::redirectResponse(["rioadmin", "sessionlogin"]);
    }
}