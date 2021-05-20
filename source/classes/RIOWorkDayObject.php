<?php

use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use function source\getAPIAbsolutePath;

class RIOWorkDayObject implements RIOToJSON
{
    private string $username;

    private DateTime $presenceTime;

    private RIOAbsentOptionObject $absentAllDay;

    private RIOAbsentOptionObject $absentAfternoon;

    private DateTime $date;

    private DateTime $deviation;

    private DateTime $hoursWorked;

    private bool $specialCompensation;

    private DateTime $mandatoryTime;

    private DateTime $monthlyBalance;

    private RIOAbsentOptionObject $absentMorning;

    /**
     * The actual minutes an user has worked at one day as an single int
     *
     * @var int
     */
    private int $totalMinutes;

    private DateTime $timeCredit;
    
    private DateTime $timeCreditCorrected;

    private DateTime $totalBalance;

    private DateTime $weeklyBalance;

    /**
     * @var RIOTimeObject[]
     */
    private array $times;

    /**
     * RIOWorkDayObject constructor.
     */
    public function __construct()
    {
        /*
        $user = new RIOUserObject();
        $this->username = $user->getUsername();*/
        $this->username = '';
        $this->absentAllDay = new RIOAbsentOptionObject();
        $this->absentAfternoon = new RIOAbsentOptionObject();
        $this->date = RIODateTimeFactory::getDateTime();
        $this->deviation = RIODateTimeFactory::getDateTime();
        $this->hoursWorked = RIODateTimeFactory::getDateTime();
        $this->specialCompensation = $this->isRIOHoliday() || $this->isSunday();
        $this->mandatoryTime = RIODateTimeFactory::getDateTime();
        if(true === $this->specialCompensation) {
            $this->mandatoryTime->setTime(0,0);
        } else {
            $this->mandatoryTime->setTime(8,0);
        }
        $this->monthlyBalance = RIODateTimeFactory::getDateTime();
        $this->absentMorning = new RIOAbsentOptionObject();
        $this->totalMinutes = 0;
        $this->timeCredit = RIODateTimeFactory::getDateTime();
        $this->timeCredit->setTime(0,0);
        $this->totalBalance = RIODateTimeFactory::getDateTime();
        $this->weeklyBalance = RIODateTimeFactory::getDateTime();
        $this->times = [];
        $this->presenceTime = RIODateTimeFactory::getDateTime();
    }

    public function toJSON(): string
    {
        $array = [
            "sessionUsername" => $this->username,
            "date" => $this->date,
            "mandatoryTime" => $this->mandatoryTime,
            "time" => $this->times,
            "presenceTime" => $this->presenceTime
        ];
        return json_encode($array);
    }

    /**
     * @return DateTime
     */
    public function getTimeCreditCorrected(): DateTime
    {
        return $this->timeCreditCorrected;
    }

    /**
     * @param DateTime $timeCreditCorrected
     */
    public function setTimeCreditCorrected(DateTime $timeCreditCorrected): void
    {
        $this->timeCreditCorrected = $timeCreditCorrected;
    }

    /**
     * @return string
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @param string $username
     */
    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    /**
     * @return DateInterval[]
     */
    public function getStartAndEndDiff(): array
    {
        $diff_list = [];
        foreach ($this->times as $time) {
            $origin = $time->getTimeStart();
            $target = $time->getTimeEnd();
            $interval = $origin->diff($target);
            $diff_list[] = $interval;
        }
        return $diff_list;
    }

    /**
     * This will be a cron job
     *
     */
    public function sum(): void
    {
        $totalList = $this->getStartAndEndDiff();
        $totalMinutes = 0;
        foreach ($totalList as $total) {
            $minutes = $total->m;
            $hours = $total->h;
            $hoursInMinutes = $hours * 60;
            $totalMinutes = $totalMinutes + $minutes + $hoursInMinutes;
        }
        $this->totalMinutes = $totalMinutes;
    }


    /**
     * @return bool
     * @throws TransportExceptionInterface
     */
    public function isRIOHoliday(): bool
    {
        $user = new RIOUserObject();
        $date = RIODateTimeFactory::getDateTime();
        $response = $this->getHoliday($user->getLocation(), $date->format("Y-m-d"));
        return '' !== json_decode($response->getContent(),true)["holiday"];
    }

    public function getHoliday(string $location, string $date): ResponseInterface
    {
        $client = new CurlHttpClient();
        return $client->request(
            "GET",
            getAPIAbsolutePath(
                [
                    "api"
                ],
                "getHoliday.php?location=".$location."&date=".$date
            ),
            [
                "verify_peer" => false,
                "verify_host" => false
            ]
        );
    }

    public function isSunday(): bool
    {
        $date = $this->date->getTimestamp();
        $day = date("D", strtotime($date));
        if($day === 'Sun') {
            return true;
        } else {
            return false;
        }

    }

    public function isSaturday(): bool
    {
        $date = $this->date->getTimestamp();
        $day = date("D", strtotime($date));
        if($day === 'Sat') {
            return true;
        } else {
            return false;
        }

    }

    public function getWeekDay(): string
    {
        $dayNames = [
            'Montag',
            'Dienstag',
            'Mittwoch',
            'Donnerstag',
            'Freitag',
            'Samstag',
            'Sonntag'
        ];
        return $dayNames[(int) $this->date->format('N')-1];
    }

    public function getFormattedDate(): string
    {
        $date = $this->date;
        $day = $date->format("d");
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
        return $day.". ".$monthName." ".$year;
    }

    public function getFormattedDateTwo(): string
    {
        $date = $this->date;
        return $date->format("d.m.Y");
    }

    /**
     * @return RIOAbsentOptionObject
     */
    public function getAbsentAllDay(): RIOAbsentOptionObject
    {
        return $this->absentAllDay;
    }

    /**
     * @param RIOAbsentOptionObject $absentAllDay
     */
    public function setAbsentAllDay(RIOAbsentOptionObject $absentAllDay): void
    {
        $this->absentAllDay = $absentAllDay;
    }

    /**
     * @return RIOAbsentOptionObject
     */
    public function getAbsentAfternoon(): RIOAbsentOptionObject
    {
        return $this->absentAfternoon;
    }

    /**
     * @param RIOAbsentOptionObject $absentAfternoon
     */
    public function setAbsentAfternoon(RIOAbsentOptionObject $absentAfternoon): void
    {
        $this->absentAfternoon = $absentAfternoon;
    }

    /**
     * @return RIOAbsentOptionObject
     */
    public function getAbsentMorning(): RIOAbsentOptionObject
    {
        return $this->absentMorning;
    }

    /**
     * @param RIOAbsentOptionObject $absentMorning
     */
    public function setAbsentMorning(RIOAbsentOptionObject $absentMorning): void
    {
        $this->absentMorning = $absentMorning;
    }

    /**
     * @return DateTime
     */
    public function getDate(): DateTime
    {
        return $this->date;
    }

    /**
     * @param DateTime $date
     */
    public function setDate(DateTime $date): void
    {
        $this->date = $date;
    }

    /**
     * @return DateTime
     */
    public function getDeviation(): DateTime
    {
        return $this->deviation;
    }

    /**
     * @param DateTime $deviation
     */
    public function setDeviation(DateTime $deviation): void
    {
        $this->deviation = $deviation;
    }

    /**
     * @return DateTime
     */
    public function getHoursWorked(): DateTime
    {
        return $this->hoursWorked;
    }

    /**
     * @param DateTime $hoursWorked
     */
    public function setHoursWorked(DateTime $hoursWorked): void
    {
        $this->hoursWorked = $hoursWorked;
    }

    /**
     * @return bool
     */
    public function isSpecialCompensation(): bool
    {
        return $this->specialCompensation;
    }

    /**
     * @param bool $specialCompensation
     */
    public function setSpecialCompensation(bool $specialCompensation): void
    {
        $this->specialCompensation = $specialCompensation;
    }

    /**
     * @return DateTime
     */
    public function getMandatoryTime(): DateTime
    {
        return $this->mandatoryTime;
    }

    /**
     * @param DateTime $mandatoryTime
     */
    public function setMandatoryTime(DateTime $mandatoryTime): void
    {
        $this->mandatoryTime = $mandatoryTime;
    }

    /**
     * @return DateTime
     */
    public function getMonthlyBalance(): DateTime
    {
        return $this->monthlyBalance;
    }

    /**
     * @param DateTime $monthlyBalance
     */
    public function setMonthlyBalance(DateTime $monthlyBalance): void
    {
        $this->monthlyBalance = $monthlyBalance;
    }

    /**
     * @return int
     */
    public function getTotalMinutes(): int
    {
        return $this->totalMinutes;
    }

    /**
     * @param int $totalMinutes
     */
    public function setTotalMinutes(int $totalMinutes): void
    {
        $this->totalMinutes = $totalMinutes;
    }

    /**
     * @return DateTime
     */
    public function getTimeCredit(): DateTime
    {
        return $this->timeCredit;
    }

    /**
     * @param DateTime $timeCredit
     */
    public function setTimeCredit(DateTime $timeCredit): void
    {
        $this->timeCredit = $timeCredit;
    }

    /**
     * @return DateTime
     */
    public function getTotalBalance(): DateTime
    {
        return $this->totalBalance;
    }

    /**
     * @param DateTime $totalBalance
     */
    public function setTotalBalance(DateTime $totalBalance): void
    {
        $this->totalBalance = $totalBalance;
    }

    /**
     * @return DateTime
     */
    public function getWeeklyBalance(): DateTime
    {
        return $this->weeklyBalance;
    }

    /**
     * @param DateTime $weeklyBalance
     */
    public function setWeeklyBalance(DateTime $weeklyBalance): void
    {
        $this->weeklyBalance = $weeklyBalance;
    }

    /**
     * @return RIOTimeObject[]
     */
    public function getTimes(): array
    {
        return $this->times;
    }

    /**
     * @param RIOTimeObject[] $times
     */
    public function setTimes(array $times): void
    {
        $this->times = $times;
    }

    public function toObject(): self
    {
        return new RIOWorkDayObject();
    }

    /**
     * @return DateTime
     */
    public function getPresenceTime(): DateTime
    {
        return $this->presenceTime;
    }

    /**
     * @param DateTime $presenceTime
     */
    public function setPresenceTime(DateTime $presenceTime): void
    {
        $this->presenceTime = $presenceTime;
    }


}