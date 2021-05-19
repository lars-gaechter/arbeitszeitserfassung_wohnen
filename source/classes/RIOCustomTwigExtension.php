<?php


use Symfony\Component\HttpFoundation\Request;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use function source\getAbsolutePath;

class RIOCustomTwigExtension extends AbstractExtension
{
    private Request $request;

    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('getAbsolutePath', [$this, 'getAbsolutePath']),
            new TwigFunction('getSession', [$this, 'getSession']),
            new TwigFunction('getWeekDayShortNameByDate', [$this, 'getWeekDayShortNameByDate']),
            new TwigFunction('isLoggedIn', [$this, 'isLoggedIn']),
            new TwigFunction('logoLink', [$this, 'logoLink'])
        ];
    }

    /**
     * @param string $sessionUsername
     * @param string $monthYear
     * @param string $active
     * @return array
     */
    public function navByActive(string $sessionUsername, string $monthYear, string $active = ''): array
    {
        return [
            "nav" => [
                [
                    "name" => "Zeiterfassung",
                    "active" => "user_home" === $active,
                    "link" => $this->getAbsolutePath(["rioadmin","sessionLogin"]),
                    "mobile" => true
                ],
                [
                    "name" => "Benutzer",
                    "active" => "edit_user" === $active,
                    "link" => $this->getAbsolutePath(["rioadmin","editUser", $sessionUsername]),
                    "mobile" => true
                ],
                [
                    "name" => "Übersicht",
                    "active" => "overview" === $active,
                    "link" => $this->getAbsolutePath(["rioadmin","overview", $sessionUsername, $monthYear]),
                    "mobile" => false
                ]
            ]
        ];
    }

    public function logoLink(): string
    {
        if($this->isLoggedIn()) {
            return $this->getAbsolutePath(["sessionlogin"]);
        }
        return $this->getAbsolutePath();
    }

    public function isLoggedIn(): bool
    {
        $databaseCollection = new RIOMongoDatabaseCollection(RIOMongoDatabase::getInstance()->getDatabase(), "user");
        $collection = $databaseCollection->getCollection();
        $userFind = $collection->findOne(
            ["sessionId" => $this->request->getSession()->getId()]
        );
        return null !== $userFind;
    }

    /**
     * @param string $date
     * @return string
     * @throws Exception
     */
    public function getWeekDayShortNameByDate(string $date): string
    {
        $givenDate = RIODateTimeFactory::getDateTime($date);
        $dayNames = [
            'Mo',
            'Di',
            'Mi',
            'Do',
            'Fr',
            'Sa',
            'So'
        ];
        return $dayNames[(int) $givenDate->format('N')-1];
    }

    public function getSession(string $session_key = null): ?string
    {
        return $this->request->getSession()->get($session_key);
    }

    /**
     * @param string $after
     * @param array $parts
     * @return string
     */
    public function getAbsolutePath(array $parts = [], string $after = ""): string
    {
        return getAbsolutePath($parts, $after);
    }
}