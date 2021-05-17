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
     * @throws Exception
     */
    public function showHomepage(): Response
    {
        return $this->sessionLogin();
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
     * Tries to login user by current session if saved
     *
     * @return Response
     * @throws Exception
     */
    public function sessionLogin(): Response
    {
        $customTwigExtension = new RIOCustomTwigExtension($this->getRequest());
        if($customTwigExtension->isLoggedIn()) {
            return RIORedirect::redirectResponse(["rioadmin", "sessionLogin"]);
        }
        return $this->showHome();
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
            return RIORedirect::redirectResponse(["rioadmin", "sessionLogin"]);
        } else {
            return $this->showHomepage();
        }
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
        return $this->showHomepage();
    }
}