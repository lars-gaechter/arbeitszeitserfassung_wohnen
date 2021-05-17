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
            [ "session_username" => $user->getUsername(), "session_id" => $user->getSessionId() ],
            [
                '$set' => [ 'session_id' => '' ]
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
    private function showUser(array $context = []): Response
    {
        $user = new RIOUserObject($this);
        $workday = new RIOWorkDayObject();
        $monthYear = $workday->getDate()->format("m.Y");
        $customTwigExtension = new RIOCustomTwigExtension($this->getRequest());
        return $this->renderPage(
            "user_home.twig",
            array_merge_recursive(
                array_merge(
                    $context,
                    $customTwigExtension->navByActive($user->getUsername(), $monthYear, "user_home")
                ),
                [
                    "time_record_started" => $user->isTimeRecordStarted(),
                    "day" => $workday->getWeekDay(),
                    "date" => $workday->getFormattedDate(),
                    'display_username' => $this->getUsers()->findOne($this->getUserFromSession())["display_username"],
                    'month_year' => $monthYear,
                    'session_username' => $user->getUsername()
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
        $user = $this->getUsers()->findOne(['session_username' => $username]);
        $request = $this->getRequest();
        $mandatoryTime = $request->get("mandatory_time");
        if(null !== $mandatoryTime && '' !== $mandatoryTime) {
            $user->offsetSet("mandatory_time",RIODateTimeFactory::getDateTime($mandatoryTime));
            /** @var DateTime $updatedMandatoryTime */
            $updatedMandatoryTime = $user->offsetGet("mandatory_time");
            $this->getUsers()->findOneAndUpdate(
                ["session_username" => $user->getUsername()],
                ['$set' => ['mandatory_time' => $updatedMandatoryTime->format("H:i")]]
            );
        }
        return RIORedirect::redirectResponse(['admin', 'edituser', $username]);
    }

    public function editUser(string $username): RedirectResponse|Response
    {
        /** @var BSONDocument $user */
        $user = $this->getUsers()->findOne(['session_username' => $username]);
        $workday = new RIOWorkDayObject();
        $monthYear = $workday->getDate()->format("m.Y");
        $customTwigExtension = new RIOCustomTwigExtension($this->getRequest());
        return $this->renderPage(
            "edit_user.twig",
            array_merge(
                $customTwigExtension->navByActive($user->offsetGet("session_username"), $monthYear, "edit_user"),
                [
                    "mandatory_time" => $user->offsetGet("mandatory_time"),
                    'month_year' => $monthYear,
                    'session_username' => $user->offsetGet("session_username")
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
        $user = $this->getUsers()->findOne(['session_username' => $username]);
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
                    "time_start" => '' === $time->offsetGet("start_corrected") ? $time->offsetGet("start") : $time->offsetGet("start_corrected"),
                    "time_start_corrected" => $time->offsetGet("start_corrected"),
                    "time_end" => '' === $time->offsetGet("end_corrected") ? $time->offsetGet("end") : $time->offsetGet("end_corrected"),
                    "time_end_corrected" => $time->offsetGet("end_corrected"),
                    'display_username' => $user->offsetGet("display_username"),
                    'surname_username' => $user->offsetGet("surname_username"),
                    'presence_time' => '' === $time->offsetGet("presence_time_corrected") ? $time->offsetGet("presence_time") : $time->offsetGet("presence_time_corrected"),
                    'presence_time_corrected' => $time->offsetGet("presence_time_corrected"),
                    'absent_options' => RIOAbsentOptionObject::getOptions(),
                    'absent_all_day' => $workDay->offsetGet("absent_all_day"),
                    'absent_afternoon' => $workDay->offsetGet("absent_afternoon"),
                    'absent_morning' => $workDay->offsetGet("absent_morning"),
                    'comment' => $time->offsetGet("comment"),
                    "mandatory_time" => $workDay->offsetGet("mandatory_time"),
                    'working_time_performed_corrected' => $time->offsetGet("working_time_performed_corrected"),
                    'working_time_performed' => '' === $time->offsetGet("working_time_performed_corrected") ? $time->offsetGet("working_time_performed") : $time->offsetGet("working_time_performed_corrected"),
                    'presence_time_total' => $workDay->offsetGet("presence_time"),
                    'presence_time_total_corrected' => $workDay->offsetGet("presence_time_corrected"),
                    'deviation' => $workDay->offsetGet("deviation"),
                    'deviation_negative_or_positive_or_zero' => $workDay->offsetGet("deviation_negative_or_positive_or_zero"),
                    'time_credit' => $workDay->offsetGet("time_credit"),
                    'time_credit_corrected' => $workDay->offsetGet("time_credit_corrected"),
                    'month_year' => $monthYear,
                    'username_date_time_index' => [$username, $date, $indexOfTime],
                    'last_edited_user' => $time->offsetGet("last_edited_user"),
                    'last_edited_date' => $time->offsetGet("last_edited_date"),
                    'last_edited_time' => $time->offsetGet("last_edited_time")
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
        $user = $this->getUsers()->findOne(['session_username' => $username]);
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
        return $this->renderPage(
            "overview.twig",
            array_merge(
                $customTwigExtension->navByActive($user->offsetGet("session_username"), $monthYear, "overview"),
                [
                    "all_work_days_from_user_past" => $this->getUserAllPastWorkdaysByMonthYearUser($monthYear, $username),
                    "previous_month_name" => $previousMonthYearName,
                    "next_month_name" => $nextMonthYearName,
                    "current_month_name" => $this->getFormattedDateByDate(RIODateTimeFactory::getDateTime("01.".$monthYear)),
                    "previous_month" => $previousMonthYear,
                    "next_month" => $nextMonthYear,
                    'display_username' => $user->offsetGet("display_username"),
                    'surname_username' => $user->offsetGet("surname_username"),
                    'session_username' => $user->offsetGet("session_username")
                ]
            )
        );
    }

    /**
     * @throws Exception
     */
    public function updatePresenceTimeCorrections(string $username, string $date, string $indexOfTime): RedirectResponse|Response
    {
        // By default the form hasn't changed
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
        if ($absentAllDay !== $workDay->offsetGet("absent_all_day")) {
            $workDay->offsetSet("absent_all_day", $absentAllDay);
            $formHasChanged = true;
        }
        if ($absentMorning !== $workDay->offsetGet("absent_morning")) {
            $workDay->offsetSet("absent_morning", $absentMorning);
            $formHasChanged = true;
        }
        if ($absentAfternoon !== $workDay->offsetGet("absent_afternoon")) {
            $workDay->offsetSet("absent_afternoon", $absentAfternoon);
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
            $time->offsetSet("last_edited_user", $user->offsetGet("display_username") . ' ' . $user->offsetGet("surname_username"));
            $time->offsetSet("last_edited_date", $timestamp->format("d.m.Y"));
            $time->offsetSet("last_edited_time", $timestamp->format("H:i"));
            $times->offsetSet($indexOfTime, $time);
            $workDays->findOneAndUpdate(
                $givenTime,
                [
                    '$set' => [
                        'time' => $times,
                        'absent_all_day' => $workDay->offsetGet("absent_all_day"),
                        'absent_morning' => $workDay->offsetGet("absent_morning"),
                        'absent_afternoon' => $workDay->offsetGet("absent_afternoon"),
                        $this->getMandatoryTimeCorrectedKey() => $workDay->offsetGet($this->getMandatoryTimeCorrectedKey()),
                        $this->getMandatoryTimeKey() => $workDay->offsetGet($this->getMandatoryTimeKey())
                    ]
                ]
            );
        }

        $workday = new RIOWorkDayObject();
        $workday->setDate(RIODateTimeFactory::getDateTime($date));
        $monthYear = $workday->getDate()->format("m.Y");
        return RIORedirect::redirectResponse(["admin", "overview", $username, $monthYear]);
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
            throw new Error("Wrong djacent argument can be previous or next");
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
                array_merge(
                    [
                        "session_username" =>  $this->getSession()->get("username")
                    ],
                    $this->getDate()
                )
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
                [ "session_username" => $user->getUsername(), 'time_record_started' => false ],
                ['$set' => [ 'time_record_started' => true ]]
            );
        }
        return RIORedirect::redirectResponse(["admin", "sessionlogin"]);
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
            $userMonth = $this->getUserAllPastWorkdaysByMonthYear($findOneWorkDay->offsetGet("month_year"), $this);
            $userTotal = $this->getUserAllPastWorkdays($this);
            $userWeek = $this->getUserAllPastWorkdaysByWeek($findOneWorkDay->offsetGet("week_year"), $this);
            $isTimeMonthly = RIODateTimeFactory::getDateTime();
            $isTimeMonthly->setTime(0, 0);
            $mandatoryTimeMonthly = RIODateTimeFactory::getDateTime();
            $mandatoryTimeMonthly->setTime(0, 0);
            $deviationTimeMonthly = RIODateTimeFactory::getDateTime();
            $deviationTimeMonthly->setTime(0, 0);
            /** @var BSONDocument $day */
            foreach ($userMonth as $day) {
                $dayTime = RIODateTimeFactory::getDateTime($day->offsetGet("presence_time"));
                $dayMandatoryTime = RIODateTimeFactory::getDateTime($day->offsetGet("mandatory_time"));
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
                $dayTime = RIODateTimeFactory::getDateTime($day->offsetGet("presence_time"));
                $dayMandatoryTime = RIODateTimeFactory::getDateTime($day->offsetGet("mandatory_time"));
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
                $dayTime = RIODateTimeFactory::getDateTime($day->offsetGet("presence_time"));
                $dayMandatoryTime = RIODateTimeFactory::getDateTime($day->offsetGet("mandatory_time"));
                $isTimeWeekly->add($startTime->diff($dayTime));
                $mandatoryTimeWeekly->add($startTime->diff($dayMandatoryTime));
            }
            $deviationTimeWeekly->add($mandatoryTimeWeekly->diff($isTimeWeekly));
            $this->getWorkDaysByYearUser(RIODateTimeFactory::getDateTime()->format("Y"),$user->getUsername())->findOneAndUpdate(
                $this->getTimeRecordingFilterStarted(),
                ['$set' => [
                    'presence_time' => $presenceTimeTotal->format("H:i"),
                    'presence_time_corrected' => '',
                    'time' => $findOneWorkDay->offsetGet("time"),
                    'deviation' => $deviation->format("H:i"),
                    'deviation_negative_or_positive_or_zero' => $deviationNegativeOrPositiveOrZero,
                    'is_time_monthly' => $isTimeMonthly->format("H:i"),
                    'is_time_total' => $isTimeTotal->format("H:i"),
                    'is_time_weekly' => $isTimeWeekly->format("H:i"),
                    'mandatory_time_monthly' => $mandatoryTimeMonthly->format("H:i"),
                    'mandatory_time_total' => $mandatoryTimeTotal->format("H:i"),
                    'mandatory_time_weekly' => $mandatoryTimeWeekly->format("H:i"),
                    'deviation_time_monthly' => $deviationTimeMonthly->format("H:i"),
                    'deviation_time_total' => $deviationTimeTotal->format("H:i"),
                    'deviation_time_weekly' => $mandatoryTimeWeekly->format("H:i")
                ]
                ]
            );
            $this->getUsers()->findOneAndUpdate(
                ["session_username" => $user->getUsername(), 'time_record_started' => true],
                ['$set' => ['time_record_started' => false]]
            );
        }
        return RIORedirect::redirectResponse(["admin", "sessionlogin"]);
    }
}