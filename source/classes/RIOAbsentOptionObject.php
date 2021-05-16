<?php

/**
 * Bei //0 sind bei ganzen Tagen 00:00 Pflichtzeit und bei halben Tagen Pflichtzeit/2
 *
 * Class RIOAbsentOptionObject
 */
class RIOAbsentOptionObject
{
    private const NOT_EXTERNAL = "(nicht extern)";
    private const SUPPLEMENTARY_COURSE = "Ergänzungskurs"; //0
    private const TELEWORKING = "Telearbeit";
    private const BRIDLE = "zügeln"; //0
    private const ABSENT = "abwesend";
    private const COMPENSATION = "Kompensation";
    private const OFFICIAL_HOLIDAY = "offizieller Feiertag"; //0 isholiday
    private const HOLIDAYS = "Ferien"; //0
    private const COMPANY_HOLIDAYS = "Betriebsferien"; //0
    private const BONUS = "Bonus";
    private const SICK = "krank"; //0
    private const DOCTOR = "Arzt"; //0
    private const ACCIDENT = "Unfall"; //0
    private const MILITARY = "Militär"; //0
    private const MEETING = "Besprechung";
    private const REST_IN_PEACE = "†"; //0

    private string $option;

    /**
     * RIOAbsentOptionObject constructor.
     */
    public function __construct()
    {
        $this->setNotExternal();
    }

    static function getOptions(): array
    {
        return [
            self::NOT_EXTERNAL,
            self::SUPPLEMENTARY_COURSE,
            self::TELEWORKING,
            self::BRIDLE,
            self::ABSENT,
            self::COMPENSATION,
            self::OFFICIAL_HOLIDAY,
            self::HOLIDAYS,
            self::COMPANY_HOLIDAYS,
            self::BONUS,
            self::SICK,
            self::DOCTOR,
            self::ACCIDENT,
            self::MILITARY,
            self::MEETING,
            self::REST_IN_PEACE,
        ];
    }

    static function getNoMandatoryTimeOptions(): array
    {
        return [
            self::SUPPLEMENTARY_COURSE,
            self::BRIDLE,
            self::OFFICIAL_HOLIDAY,
            self::HOLIDAYS,
            self::COMPANY_HOLIDAYS,
            self::SICK,
            self::DOCTOR,
            self::ACCIDENT,
            self::MILITARY,
            self::REST_IN_PEACE
        ];
    }

    /**
     * @return string
     */
    public function getOption(): string
    {
        return $this->option;
    }

    /**
     * @param string $option
     */
    private function setOption(string $option): void
    {
        $this->option = $option;
    }

    public function setNotExternal(): void
    {
        $this->setOption(self::NOT_EXTERNAL);
    }

    public function setSupplementaryCourse(): void
    {
        $this->setOption(self::SUPPLEMENTARY_COURSE);
    }

    public function setTeleworking(): void
    {
        $this->setOption(self::TELEWORKING);
    }

    public function setBridle(): void
    {
        $this->setOption(self::BRIDLE);
    }

    public function setAbsent(): void
    {
        $this->setOption(self::ABSENT);
    }

    public function setCompensation(): void
    {
        $this->setOption(self::COMPENSATION);
    }

    public function setOfficialHoliday(): void
    {
        $this->setOption(self::OFFICIAL_HOLIDAY);
    }

    public function setHolidays(): void
    {
        $this->setOption(self::HOLIDAYS);
    }

    public function setCompanyHolidays(): void
    {
        $this->setOption(self::COMPANY_HOLIDAYS);
    }

    public function setBonus(): void
    {
        $this->setOption(self::BONUS);
    }

    public function setSick(): void
    {
        $this->setOption(self::SICK);
    }

    public function setDoctor(): void
    {
        $this->setOption(self::DOCTOR);
    }

    public function setAccident(): void
    {
        $this->setOption(self::ACCIDENT);
    }

    public function setMilitary(): void
    {
        $this->setOption(self::MILITARY);
    }

    public function setMeeting(): void
    {
        $this->setOption(self::MEETING);
    }

    public function setRestInPeace(): void
    {
        $this->setOption(self::REST_IN_PEACE);
    }

}