<?php

class RIOUserObject implements RIOToJSON
{
    private string $username;
    private string $sessionId;
    private bool $timeRecordStarted;
    private array $workDays;
    private DateTime $mandatoryTime;
    private string $location;

    /**
     * RIOUserObject constructor.
     * @throws Exception
     */
    public function __construct(RIOAccessController $accessController = null)
    {
        if(null !== $accessController) {
            $user = $accessController->getUser();
            $this->timeRecordStarted = $user["timeRecordStarted"];
            $this->sessionId = $user["sessionId"];
            $this->username = $user["sessionUsername"];
            $this->workDays = $accessController->getWorkDaysByUser();
            if(false === array_key_exists("mandatoryTime", $user)){
                $this->mandatoryTime = RIODateTimeFactory::getDateTime();
                // New created user has by default 8 hour and 0 minute mandatory time
                $this->mandatoryTime->setTime(8,0);
            } else {
                $this->mandatoryTime = RIODateTimeFactory::getDateTime($user["mandatoryTime"]);
            }
        } else {
            $this->mandatoryTime = RIODateTimeFactory::getDateTime();
            $this->mandatoryTime->setTime(8,0);
        }
        // New created user has by default location dietikon
        $this->location = "dietikon";
    }

    public function toJSON(): string
    {
        $array = [
            "sessionUsername" => $this->username,
            "displayUsername" => '',
            "surnameUsername" => '',
            "sessionId" => $this->sessionId,
            "timeRecordStarted" => $this->timeRecordStarted,
            "mandatoryTime" => $this->mandatoryTime,
            "location" => $this->location
        ];
        return json_encode($array);
    }

    /**
     * @return string
     */
    public function getLocation(): string
    {
        return $this->location;
    }

    /**
     * @param string $location
     */
    public function setLocation(string $location): void
    {
        $this->location = $location;
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
     * @return array
     */
    public function getWorkDays(): array
    {
        return $this->workDays;
    }

    /**
     * @param array $workDays
     */
    public function setWorkDays(array $workDays): void
    {
        $this->workDays = $workDays;
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
     * @return string
     */
    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    /**
     * @param string $sessionId
     */
    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    /**
     * @return bool
     */
    public function isTimeRecordStarted(): bool
    {
        return $this->timeRecordStarted;
    }

    public function isTimeRecordStopped(): bool
    {
        return !$this->isTimeRecordStarted();
    }

    /**
     * @param bool $timeRecordStarted
     */
    public function setTimeRecordStarted(bool $timeRecordStarted): void
    {
        $this->timeRecordStarted = $timeRecordStarted;
    }

    public function toObject(): self
    {
        return new RIOUserObject();
    }
}