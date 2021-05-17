<?php

declare(strict_types=1);

class RIOTimeObject implements RIOToJSON
{
    private DateTime $timeEnd;

    private DateTime $workingTimePerformedCorrected;

    private DateTime $workingTimePerformed;

    private DateTime $timeEndCorrected;

    private DateTime $timeStart;

    private DateTime $timeStartCorrected;

    private DateTime $presenceTime;

    private DateTime $presenceTimeCorrected;

    private DateTime $isTime;

    private string $comment;

    /**
     * RIOTimeObject constructor.
     * @throws Exception
     */
    public function __construct()
    {
        $this->timeStart = RIODateTimeFactory::getDateTime();
        $this->timeStartCorrected = RIODateTimeFactory::getDateTime();
        $this->timeEnd = RIODateTimeFactory::getDateTime();
        $this->timeEndCorrected = RIODateTimeFactory::getDateTime();
        $this->presenceTime = RIODateTimeFactory::getDateTime();
        $this->presenceTimeCorrected = RIODateTimeFactory::getDateTime();
        $this->comment = '';
        $this->workingTimePerformedCorrected = RIODateTimeFactory::getDateTime();
        $this->workingTimePerformed = RIODateTimeFactory::getDateTime();
        $this->isTime = RIODateTimeFactory::getDateTime();
    }

    public function toJSON(): string
    {
        $array = [
            "start" => $this->timeStart,
            "start_corrected" => $this->timeStartCorrected,
            "end" => $this->timeEnd,
            "end_corrected" => $this->timeEndCorrected,
            "presence_time" => $this->presenceTime,
            "presence_time_corrected" => $this->presenceTimeCorrected,
            "comment" => $this->comment,
            "is_time" => $this->isTime
        ];
        return json_encode($array);
    }

    /**
     * @return DateTime
     */
    public function getIsTime(): DateTime
    {
        return $this->isTime;
    }

    /**
     * @param DateTime $isTime
     */
    public function setIsTime(DateTime $isTime): void
    {
        $this->isTime = $isTime;
    }

    public function toObject(): self
    {
        return new RIOTimeObject();
    }

    /**
     * @return DateTime
     */
    public function getTimeEnd(): DateTime
    {
        return $this->timeEnd;
    }

    /**
     * @param DateTime $timeEnd
     */
    public function setTimeEnd(DateTime $timeEnd): void
    {
        $this->timeEnd = $timeEnd;
    }

    /**
     * @return DateTime
     */
    public function getTimeStart(): DateTime
    {
        return $this->timeStart;
    }

    /**
     * @param DateTime $timeStart
     */
    public function setTimeStart(DateTime $timeStart): void
    {
        $this->timeStart = $timeStart;
    }

    /**
     * @return \DateTime
     */
    public function getTimeEndCorrected(): DateTime
    {
        return $this->timeEndCorrected;
    }

    /**
     * @param \DateTime $timeEndCorrected
     */
    public function setTimeEndCorrected(DateTime $timeEndCorrected): void
    {
        $this->timeEndCorrected = $timeEndCorrected;
    }

    /**
     * @return \DateTime
     */
    public function getTimeStartCorrected(): DateTime
    {
        return $this->timeStartCorrected;
    }

    /**
     * @param \DateTime $timeStartCorrected
     */
    public function setTimeStartCorrected(DateTime $timeStartCorrected): void
    {
        $this->timeStartCorrected = $timeStartCorrected;
    }

    /**
     * @return \DateTime
     */
    public function getPresenceTime(): DateTime
    {
        return $this->presenceTime;
    }

    /**
     * @param \DateTime $presenceTime
     */
    public function setPresenceTime(DateTime $presenceTime): void
    {
        $this->presenceTime = $presenceTime;
    }

    /**
     * @return \DateTime
     */
    public function getPresenceTimeCorrected(): DateTime
    {
        return $this->presenceTimeCorrected;
    }

    /**
     * @param \DateTime $presenceTimeCorrected
     */
    public function setPresenceTimeCorrected(DateTime $presenceTimeCorrected): void
    {
        $this->presenceTimeCorrected = $presenceTimeCorrected;
    }

    /**
     * @return string
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * @param string $comment
     */
    public function setComment(string $comment): void
    {
        $this->comment = $comment;
    }

    /**
     * @return DateTime
     */
    public function getWorkingTimePerformedCorrected(): DateTime
    {
        return $this->workingTimePerformedCorrected;
    }

    /**
     * @param DateTime $workingTimePerformedCorrected
     */
    public function setWorkingTimePerformedCorrected(DateTime $workingTimePerformedCorrected): void
    {
        $this->workingTimePerformedCorrected = $workingTimePerformedCorrected;
    }

    /**
     * @return DateTime
     */
    public function getWorkingTimePerformed(): DateTime
    {
        return $this->workingTimePerformed;
    }

    /**
     * @param DateTime $workingTimePerformed
     */
    public function setWorkingTimePerformed(DateTime $workingTimePerformed): void
    {
        $this->workingTimePerformed = $workingTimePerformed;
    }


}