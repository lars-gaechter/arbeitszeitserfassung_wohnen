<?php

use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use function source\getDefaultTheme;

/**
 * Class Admin
 * User must be in logged in state, otherwise user can't change other users time record
 * This area Admin should be used for editing other users past or feature data
 * Like other users view overview, editing mandatory time and change there presence in the past
 */
class Admin extends AccessController
{
    public function __construct(
        string $directory_namespace,
        Environment $twig,
        Request $request
    ) {
        parent::__construct($directory_namespace, $twig, $request);
    }

    /**
     * @throws Exception
     */
    public function updateUser(string $username): RedirectResponse
    {
        if($this->isLoggedIn()) {
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
        return RIORedirect::redirectResponse();
    }

    public function editUser(string $username): RedirectResponse|Response
    {
        if($this->isLoggedIn()) {
            /** @var BSONDocument $user */
            $user = $this->getUsers()->findOne(['session_username' => $username]);
            $workday = new RIOWorkDayObject();
            $monthYear = $workday->getDate()->format("m.Y");
            return $this->renderPage(
                "edit_user.twig",
                array_merge(
                    getDefaultTheme(),
                    [
                        "mandatory_time" => $user->offsetGet("mandatory_time"),
                        'month_year' => $monthYear,
                        'session_username' => $user->offsetGet("session_username")
                    ]
                )
            );
        }
        return RIORedirect::redirectResponse();
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
        if($this->isLoggedIn()) {
            return $this->renderPage(
                "presence_time_corrections.twig",
                array_merge(
                    getDefaultTheme(),
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
        return RIORedirect::redirectResponse();
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
        if($this->isLoggedIn()) {
            return $this->renderPage(
                "overview.twig",
                array_merge(
                    getDefaultTheme(),
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
        return RIORedirect::redirectResponse();
    }

    /**
     * @throws Exception
     */
    public function updatePresenceTimeCorrections(string $username, string $date, string $indexOfTime): RedirectResponse|Response
    {
        if($this->isLoggedIn()) {
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
        return RIORedirect::redirectResponse();
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
     * @throws Exception
     */
    private function isLoggedIn(): bool
    {
        $user = new RIOUserObject($this);
        $users = $this->getUsers();
        $userFind = $users->findOne(
            ["session_id" => $user->getSessionId()]
        );
        return null !== $userFind;
    }
}