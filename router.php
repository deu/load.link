<?php
/** This file is part of load.link (https://github.com/deuiore/load.link).
 * View the LICENSE file for full license information.
 **/

class Router
{
    const PANEL_ROUTE = '@panel';
    const GALLERY_PATH = 'gallery';

    protected $conf;
    protected $page;
    protected $baseroute;

    public function __construct()
    {
        if (!Config::exists())
        {
            new Installer();
            exit();
        }

        $this->conf = Config::get()->getSection('routing');
        $this->page = NULL;

        $this->baseroute = $this->conf['baseurl']
            . (($this->conf['mode'] == 'get') ? '?' : '');
    }

    public function route()
    {
        /* Routing mode */
        switch ($this->conf['mode'])
        {
            case 'path':
                $route = substr($_SERVER['REQUEST_URI'],
                    strlen($this->conf['baseurl']));
                break;

            case 'get':
                $route = (!empty($_GET)) ? array_keys($_GET)[0] : NULL;
                break;
        }

        /* Serve static pages */
        if (strncmp($route, Content::STATIC_PATH,
            strlen(Content::STATIC_PATH)) == 0)
        {
            $static_content = substr($route, strlen(Content::STATIC_PATH));
            $this->page = Content::getStaticPage($static_content);
            return;
        }

        /* Handle the empty route case */
        if ($route == '')
        {
            /* Homepage if it's been set */
            if ($this->conf['homepage'])
            {
                $this->page = new Page();
                $this->page->setResponseCode(301);
                $this->page->addHeader('Location: ' . $this->conf['homepage']);
                return;
            }
            /* Panel */
            elseif ($this->conf['panel'] == '')
            {
                $route = self::PANEL_ROUTE;
            }
            /* 404 */
            else
            {
                return;
            }
        }

        /* API */
        if ($route == API::PATH)
        {
            $api = new API();
            $this->page = $api->getPage();
            return;
        }

        /* Adjust the route for a custom panel */
        if ($this->conf['panel'] != '' && $route == $this->conf['panel'])
        {
            $route = self::PANEL_ROUTE;
        }

        $this->page = NULL;
        $this->auth = new Auth();
        $this->auth->authorizeFromCookies();

        if ($this->auth->isAuthorized())
        {
            switch ($route)
            {
                /* Panel */
                case self::PANEL_ROUTE:
                    $this->page = new Page('panel');
                    $this->page->set(array(
                        'baseroute' => $this->baseroute,
                        'api' => $this->baseroute . API::PATH,
                        'token' => $this->auth->getToken(),
                        'languages' => Utils::getLanguagesList(),
                        'config' => Config::get()->getAll(),
                        'themes' => Utils::getThemes(),
                        'gallery_path' => $this->baseroute
                            . self::GALLERY_PATH,
                        'logout_path' => $this->baseroute
                            . Auth::LOGOUT_PATH));
                    return;

                /* Logout */
                case Auth::LOGOUT_PATH:
                    if (isset($_POST['purge']))
                    {
                        $this->auth->unauthorizeAll();
                    }
                    else
                    {
                        $this->auth->unauthorize();
                    }

                    $this->auth->unsetCookie();
                    $info = new Info('Logged out. Redirecting...',
                        $this->panelRedirect());
                    $this->page = $info->getPage();
                    return;

                /* Gallery */
                case self::GALLERY_PATH:
                    $this->page = new Page('gallery');
                    $this->page->set(array(
                        'baseroute' => $this->baseroute,
                        'api' => $this->baseroute . API::PATH,
                        'token' => $this->auth->getToken(),
                        'config' => Config::get()->getAll(),
                    ));
                    return;
            }
        }
        else
        {
            switch ($route)
            {
                /* Login form */
                case self::PANEL_ROUTE:
                    $this->page = new Page('login');
                    $this->page->set(array(
                        'login_path' => $this->baseroute . Auth::LOGIN_PATH));
                    return;

                /* Login */
                case Auth::LOGIN_PATH:
                    if ($this->auth->authorizeFromLogin(
                        $_POST['login']['username'],
                        $_POST['login']['password']))
                    {
                        $this->auth->setCookie();
                        $info = new Info('Logged in. Redirecting...',
                            $this->panelRedirect());
                        $this->page = $info->getPage();
                    }
                    else
                    {
                        $error = new Error(Error::FORBIDDEN,
                            'Access Denied', NULL, FALSE);
                        $this->page = $error->getPage();
                    }
                    return;
            }
        }

        /* Just normal content. Serve it. */
        $content = new Content($route);
        $this->page = $content->getPage();
    }

    public function getPage()
    {
        if ($this->page)
        {
            return $this->page;
        }
        else
        {
            $error = new Error(Error::NOT_FOUND);
            return $error->getPage();
        }
    }

    protected function panelRedirect()
    {
        $redirect = array(
            'wait' => Config::get()->getValue('ui', 'wait_time'),
            'url' => $this->conf['baseurl']
        );

        if ($this->conf['panel'] != '')
        {
            $redirect['url'] .= $this->conf['panel'];
        }

        return $redirect;
    }

    protected static $reserved_routes;
    public static function getReservedRoutes()
    {
        if (!self::$reserved_routes)
        {
            self::$reserved_routes = array(
                API::PATH,
                Auth::LOGIN_PATH,
                Auth::LOGOUT_PATH,
                Content::STATIC_PATH,
                self::PANEL_ROUTE,
                self::GALLERY_PATH
            );
        }
        return self::$reserved_routes;
    }

    protected static $baseurl;
    public static function getURL()
    {
        if (!self::$baseurl)
        {
            $https = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ?
                's' : '';

            $port = (!$https && $_SERVER['SERVER_PORT'] != 80
                || ($https && $_SERVER['SERVER_PORT'] != 443)) ?
                ':' . $_SERVER['SERVER_PORT'] : '';

            $query = (Config::get()->getValue('routing', 'mode') == 'get') ?
                '?' : '';

            self::$baseurl = 'http' . $https . '://'
                . $_SERVER['SERVER_NAME'] . $port
                . Config::get()->getValue('routing', 'baseurl')
                . $query;
        }
        return self::$baseurl;
    }
}
