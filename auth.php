<?php
/** This file is part of load.link (https://github.com/deuiore/load.link).
 * View the LICENSE file for full license information.
 **/

class Auth
{
    const LOGIN_PATH = 'login';
    const LOGOUT_PATH = 'logout';

    const COOKIE_EXPIRATION = 31536000;
    protected $isAuthorized;
    protected $token;

    public function __construct()
    {
        $this->isAuthorized = FALSE;
    }

    public function isAuthorized()
    {
        return $this->isAuthorized;
    }

    public static function checkPassword($password)
    {
        $login = Config::get()->getSection('login');

        if (hash_hmac('sha512',
                $password, $login['salt']) == $login['password'])
        {
            return TRUE;
        }

        return FALSE;
    }

    public function authorizeFromToken($token)
    {
        if (DB::get()->getSession($token))
        {
            $this->isAuthorized = TRUE;
            $this->token = $token;
            return TRUE;
        }
        return FALSE;
    }

    public function authorizeFromCookies()
    {
        if (isset($_COOKIE['token']))
        {
            if (DB::get()->getSession($_COOKIE['token']))
            {
                $this->token = $_COOKIE['token'];
                $this->isAuthorized = TRUE;
                return TRUE;
            }
        }
        return FALSE;
    }

    public function authorizeFromBearerAuthHeader()
    {
        if (isset($_SERVER['HTTP_AUTHORIZATION']))
        {
            if (DB::get()->getSession(substr($_SERVER["HTTP_AUTHORIZATION"], 7)))
            {
                $this->token = $_SERVER['HTTP_AUTHORIZATION'];
                $this->isAuthorized = TRUE;
                return TRUE;
            }
        }
        return FALSE;
    }

    public function authorizeFromLogin($username, $password)
    {
        if ($username == Config::get()->getValue('login', 'username')
            && self::checkPassword($password))
        {
            $this->token = DB::get()->addSession();
            $this->isAuthorized = TRUE;
            return TRUE;
        }
        return FALSE;
    }

    public function unauthorize()
    {
        DB::get()->delSession($this->token);
        unset($this->token);
    }

    public function unauthorizeAll()
    {
        DB::get()->delAllSessions();
        unset($this->token);
    }

    public function setCookie()
    {
        setcookie('token', $this->token, time() + self::COOKIE_EXPIRATION,
            Config::get()->getValue('routing', 'baseurl'));
    }

    public function unsetCookie()
    {
        unset($_COOKIE['token']);
        setcookie('token', '', time() - 42000,
            Config::get()->getValue('routing', 'baseurl'));
    }

    public function getToken()
    {
        return $this->token;
    }

    static function passwordToHashAndSalt($password)
    {
        $salt = uniqid();
        $hash = hash_hmac('sha512', $password, $salt);

        return array(
            'hash' => $hash,
            'salt' => $salt
        );
    }
}
