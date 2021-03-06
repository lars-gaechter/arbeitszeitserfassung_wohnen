<?php

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use function source\getAbsolutePath;

/**
 * Class RIOMain
 * User can be logged in or out state
 * This area RIOMain should be used for editing user by it's user session
 * Like user start and stop time record or login and logout user
 */
class RIOMain extends RIOAccessController
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
     */
    public function showHomepage(): Response
    {
       return $this->postLogin();
    }

    private function getRemoteUser(): string
    {
        return isset($_SERVER['REMOTE_USER']) ? $_SERVER['REMOTE_USER'] : '';
    }

    public function login(string $state): Response
    {
        if(null !== $state) {
            $stateArray = ['state' => $state];
        } else {
            $stateArray = [];
        }
        return $this->renderPage(
            "home.twig",
            array_merge(
                [
                    'action' => getAbsolutePath(["postlogin"])
                ],
                $stateArray
            )
        );
    }

    /**
     * Tries to login user by current session if saved
     *
     * @return Response
     */
    public function sessionLogin(): Response
    {
        $customTwigExtension = new RIOCustomTwigExtension($this->getRequest());
        if($customTwigExtension->isLoggedIn()) {
            return RIORedirect::redirectResponse(["rioadmin", "sessionLogin"]);
        }
        return RIORedirect::redirectResponse(["login", "unchanged"]);
    }

    private function showForm($message) {
        echo($message);
    }

    /**
     * Check if given user and password exists in LDAP
     *  create new MongoDB user if not exists or just insert new session id
     *
     * @param string $username
     * @param string $password
     * @return Response|null
     * @throws \Exception
     */
    private function userValidate(string $username, string $password): ?Response
    {
	$failure = function ($error) {
            $this->showForm($error);
        };
        $session = $this->getSession();
        $sessionId = $session->getId();
        if($username === "testuser" && $password === "Password1" && "true" === $_ENV["DEVELOPMENT_MODE"] && "true" === $_ENV["DEBUG"]) {
            $maybeObject = [
            'sessionUsername' => $username,
            'displayUsername' => $username,
            'surnameUsername' => "test"
        ];
        $maybeAuthObject = array_merge(
          $maybeObject,
          ['sessionId' => $sessionId]
        );
        $user = new RIOUserObject();
        $authObjectNoTime = array_merge(
            $maybeAuthObject,
            [
                // New created user has by default no time record
                'timeRecordStarted' => false,
                "mandatoryTime" => $user->getMandatoryTime()->format("H:i"),
                "location" => $user->getLocation()
            ]
        );
        $auth_find = $this->getUsers()->findOne(
            $maybeObject
        );
            return $this->doLogin($authObjectNoTime, $maybeObject, $auth_find, $sessionId);
        } else {
	    $maybeObject = [
            'sessionUsername' => $username, //$sessionUsername,
            'displayUsername' => "test", //$displayUsername,
            'surnameUsername' => $username //$surnameUsername
        ];
        $maybeAuthObject = array_merge(
          $maybeObject,
          ['sessionId' => $sessionId]
        );
        $user = new RIOUserObject();
        $authObjectNoTime = array_merge(
            $maybeAuthObject,
            [
                // New created user has by default no time record
                'timeRecordStarted' => false,
                "mandatoryTime" => $user->getMandatoryTime()->format("H:i"),
                "location" => $user->getLocation()
            ]
        );
        $auth_find = $this->getUsers()->findOne(
            $maybeObject
        );
            /** @var resource $ldap */
            $ldap = ldap_connect($_ENV["LDAP_HOST"], $_ENV["LDAP_PORT"]);
	    echo "ldap_connect return not false: ";
	    var_dump($ldap);
            ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
	    var_dump($ldap);
            // TODO: uncatchable connection error on follow line below
            //$search = ldap_search($ldap, $_ENV["LDAP_SEARCH_ROOT"], '(' . $_ENV["LDAP_RDN"] . '=' . $username . ')');
	    var_dump(ldap_search($ldap, $_ENV["LDAP_SEARCH_ROOT"], ""));
	    echo "die";die();
	    $search = ldap_search($ldap, $_ENV["LDAP_SEARCH_ROOT"], '(' . $_ENV["LDAP_RDN"] . '=' . $username . ')');
            var_dump($search);die();
	    if (!$search) {
		echo "no search";
                return $failure(ldap_error($ldap));
	    }
            $results = ldap_get_entries($ldap, $search);
            if (!$results){
		echo 'no results';
                return $failure(ldap_error($ldap));
	    }
            $dn = $results[0]['dn'];
            $displayUsername = $results[0]['uid'][0];
            $sessionUsername = $results[0]['uid'][1];
            $surnameUsername = $results[0]["sn"][0];
            
            try {
                $bind = ldap_bind($ldap, $dn, $password);
            } catch (Exception $e) {
                if(RIOConfig::isInDebugMode()) {
                    throw new Exception($e->getMessage(). ", dn = ".$dn.", password = ".$password);
                } else {
                    // Username was correct but password was wrong
                    return RIORedirect::redirectResponse(["login", "failure"]);
                }
            }
            if ($bind) {
                ldap_unbind($ldap);
                $this->doLogin($authObjectNoTime, $maybeObject, $auth_find, $sessionId);
            } else {
                // Username or password was wrong
                return RIORedirect::redirectResponse(["login", "failure"]);
            }
        }
    }
    
    public function doLogin($authObjectNoTime, $maybeObject, $auth_find, $sessionId): Response
    {
        if("0" === (string)$this->getSession()->getMetadataBag()->getLifetime()) {
            $this->getSession()->getMetadataBag()->stampNew($_ENV["SESSION_LIFE_TIME"]);
        }
        if(null === $auth_find) {
            $this->getUsers()->insertOne(
                $authObjectNoTime
            );
        } else {
            $this->getUsers()->updateOne(
                $maybeObject,
                [
                    // Update new sessionId from client
                    '$set' => [ 'sessionId' => $sessionId ]
                ]
            );
        }
        return RIORedirect::redirectResponse(["rioadmin", "sessionLogin"]);
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
        //$usernamePost = $request->get("username");
        $usernamePost = $this->getRemoteUser();
        //$passwordPost = $request->get("password");
        if (null !== $usernamePost /*&& null !== $passwordPost*/) {
            // Save remote user in the session
            // TODO: Check if session auth is async to htaccess auth, but should be controlled everything independet only by htaccess matters
            $request->getSession()->set("username", $usernamePost);
            return $this->checkIfNewUser($usernamePost);
            //return $this->userValidate($usernamePost, $passwordPost);
        }
        return RIORedirect::error(500);
    }

    /**
     * If remote user doesn't exist in the mongo db we just create a new one else do nothing
     */
    private function checkIfNewUser($username): Response
    {
        $failure = function ($error) {
            $this->showForm($error);
        };
        $session = $this->getSession();
        $sessionId = $session->getId();
        
	    $maybeObject = [
            'sessionUsername' => $username, //$sessionUsername,
            'displayUsername' => $username, //$displayUsername, TODO: need correct diplay name like first- and lastname
            'surnameUsername' => $username //$surnameUsername
        ];
        $maybeAuthObject = array_merge(
          $maybeObject,
          ['sessionId' => $sessionId]
        );
        $user = new RIOUserObject();
        $authObjectNoTime = array_merge(
            $maybeAuthObject,
            [
                // New created user has by default no time record
                'timeRecordStarted' => false,
                "mandatoryTime" => $user->getMandatoryTime()->format("H:i"),
                "location" => $user->getLocation()
            ]
        );
        $auth_find = $this->getUsers()->findOne(
            $maybeObject
        );
        return $this->doLogin($authObjectNoTime, $maybeObject, $auth_find, $sessionId);
    }
}
