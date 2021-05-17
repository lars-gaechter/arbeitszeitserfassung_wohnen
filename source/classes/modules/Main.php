<?php

use MongoDB\Model\BSONArray;
use MongoDB\Model\BSONDocument;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Twig\Environment;
use function source\getAbsolutePath;

/**
 * Class Main
 * User can be logged in or out state
 * This area Main should be used for editing user by it's user session
 * Like user start and stop time record or login and logout user
 */
class Main extends RIOAccessController
{

    public function __construct(
        string $directoryNamespace,
        Environment $twig,
        Request $request
    ) {
        parent::__construct($directoryNamespace, $twig, $request);
    }

    /**
     * show home or do auto login if already an user is logged in
     *
     * @return Response
     * @throws Exception
     */
    public function showHomepage(): Response
    {
        return $this->sessionLogin();
    }

    /**
     * @throws TransportExceptionInterface
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getHoliday(string $location, string $date): Response
    {
        $workDayObject = new RIOWorkDayObject();
        return new Response($workDayObject->getHoliday($location, $date)->getContent());
    }

    private function showHome(): Response
    {
        return $this->renderPage(
            "home.twig",
            [
                'action' => getAbsolutePath(["postlogin"])
            ]
        );
    }

    /**
     * Tries to login with username and password from current session
     *
     * @return Response
     * @throws Exception
     */
    private function autoLogin(): Response
    {
        if($this->isLoggedIn()) {
            return $this->showUser();
        }
        return $this->showHome();
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
                    $customTwigExtension->navByActive("user_home", $monthYear, $user->getUsername())
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
        return $this->showHome();
    }


    /**
     * Check if given user and password exists in LDAP
     *
     * @param false $username
     * @param false $password
     * @return Response|null
     * @throws Exception
     */
    private function userValidate($username = FALSE, $password = FALSE): ?Response
    {
        $context = [];
        /** @var resource $ldap */
        $ldap = ldap_connect($_ENV["LDAP_HOST"], $_ENV["LDAP_PORT"]);
        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        $search = ldap_search($ldap, $_ENV["LDAP_SEARCH_ROOT"], '(' . $_ENV["LDAP_RDN"] . '=' . $username . ')');
        $results = ldap_get_entries($ldap, $search);
        $dn = $results[0]['dn'];
        $displayUsername = $results[0]['uid'][0];
        $sessionUsername = $results[0]['uid'][1];
        $surnameUsername = $results[0]["sn"][0];
        $session = $this->getSession();
        $sessionId = $session->getId();
        $request = $this->getRequest();
        $maybeObject = [
            'session_username' => $sessionUsername,
            'display_username' => $displayUsername,
            'surname_username' => $surnameUsername
        ];
        $maybeAuthObject = array_merge(
          $maybeObject,
          ['session_id' => $sessionId]
        );
        $user = new RIOUserObject();
        $authObjectNoTime = array_merge(
            $maybeAuthObject,
            [
                'time_record_started' => false, 'theme' => $request->get("theme"),
                "mandatory_time" => $user->getMandatoryTime()->format("H:i"),
                "location" => $user->getLocation()
            ]
        );
        $auth_find = $this->getUsers()->findOne(
            $maybeObject
        );
        try {
            $bind = ldap_bind($ldap, $dn, $password);
        } catch (Exception $e) {
            throw new Exception($e->getMessage(). ", dn = ".$dn.", password = ".$password);
        }
        if ($bind) {
            ldap_unbind($ldap);
            if(null === $auth_find) {
                $this->getUsers()->insertOne(
                    $authObjectNoTime
                );
            } else {
                $this->getUsers()->updateOne(
                    $maybeObject,
                    [
                        '$set' => [ 'session_id' => $sessionId, 'theme' => $request->get("theme") ]
                    ]
                );
            }
            return $this->showUser($context);
        } else {
            return $this->showHome();
        }
    }

    /**
     * @throws Exception
     */
    public function theme(string $contraryTheme): RedirectResponse
    {
        if($this->isLoggedIn()) {
            $newTheme = '';
            $light = "light";
            $dark = "dark";
            if($dark === $contraryTheme) {
                $newTheme .= $light;
            }
            if($light === $contraryTheme) {
                $newTheme .= $dark;
            }
            $user = new RIOUserObject($this);
            $users = $this->getUsers();
            $users->updateOne(["session_id" => $user->getSessionId(), "session_username" => $user->getUsername() ], ['$set' => [ 'theme' => $newTheme ]]);
            return RIORedirect::redirectResponse(["sessionlogin"]);
        }
        return RIORedirect::redirectResponse();
    }


    /**
     * @throws Exception
     */
    private function isLoggedIn(): bool
    {
        $user = new RIOUserObject($this);
        $users = $this->getUsers();
        $userFind = $users->findOne(
            [
                "session_id" => $user->getSessionId(),
                "session_username" => $user->getUsername()
            ]
        );
        return null !== $userFind;
    }


    /**
     * Tries to login user by post, usually called by a form
     *
     * @return Response
     * @throws Exception
     */
    public function postLogin(): Response
    {
        $request = $this->getRequest();
        $usernamePost = $request->get("username");
        $passwordPost = $request->get("password");
        if (null !== $usernamePost && null !== $passwordPost) {
            $request->getSession()->set("username", $usernamePost);
            return $this->userValidate($usernamePost, $passwordPost);
        }
        return $this->showHome();
    }


    /**
     * Tries to login user by current session if saved
     *
     * @return Response
     * @throws Exception
     */
    public function sessionLogin(): Response
    {
        return $this->autoLogin();
    }


    /**
     * Check if theres an work day exist for the session user
     *  if not then insert new work day with new start time
     *  if yes then update existing work day with mew start time
     *
     * @return RedirectResponse|Response
     * @throws Exception
     */
    public function start(): RedirectResponse|Response
    {
        if($this->isLoggedIn()) {
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
            return RIORedirect::redirectResponse(["sessionlogin"]);
        }
        return $this->showHome();
    }

    /**
     * @throws Exception
     */
    public function stop(): RedirectResponse|Response
    {
        if($this->isLoggedIn()) {
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
            return RIORedirect::redirectResponse(["sessionlogin"]);
        }
        return $this->showHome();
    }
}