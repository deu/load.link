<?php
$passwordHash = 'ee26b0dd4af7e749aa1a8ee3c10ae9923f618980772e473f8819a5d4940e0db27ac185f8a0e1d5f84f88bc887fd67b143732c304cc5fa9ad8e6f57f50028a8ff'; // For now it stays like this because it's a pain to delete it every time I push it. The password is "test". Feel free to delete the hash and try installing it.
// --------------------------------------------------------
// DO NOT TOUCH THE FIRST TWO LINES.


// Optional configuration:
$uploadDir      = '.';
$databasePath   = '.db';
$idLength       = 6; // must be longer than 3 characters.
$idCharacters   = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
$sameNameSuffix = '.1';
$https          = false;


// Here be dragons:

class Config
{
    public static function __callStatic($methodName, $methodArgs)
    {
        if (substr($methodName, 0, 3) == 'get')
        {
            return $GLOBALS[lcfirst(substr($methodName, 3))];
        }
        else
        {
            return false;
        }
    }
}

class Template
{
    protected $templates = array(
        'global' => <<<'HTML'
<!DOCTYPE HTML>
<html>
    <head>
{HEADERS}
    </head>
    <body>
        <div id="contents">
{CONTENT}
        </div>
    </body>
</html>
HTML
    ,

    'logo' => <<<'HTML'
            <div id="logo">load<span class="gray">link</span></div>
HTML
    ,

    'defaultHeaders' => <<<'HTML'
        <meta charset="UTF-8">
        <title>load.link</title>
        <link rel="stylesheet" type="text/css" href="?css">
HTML
    ,

    'metaRefresh' => <<<'HTML'
        <meta http-equiv="refresh" content="0; url={URL}" />
HTML
    ,

    'loginForm' => <<<'HTML'
            <form id="login" method="post" action="?lgn">
                <input id="password" type="password" name="password" placeholder="password">
                <br />
                <button class="submitButton" type="submit">login</button>
            </form>
HTML
    ,

    'logoutLink' => <<<'HTML'
        <div id="logoutLink"><a href="?lgt">Logout</a></div>
HTML
    ,

    'installForm' => <<<'HTML'
            <form id="login" method="post" action="?ins">
                <input id="password" type="password" name="password" placeholder="choose a password">
                <br />
                <button class="submitButton" type="submit">install</button>
            </form>
HTML
    ,

    'uploadForm' => <<<'HTML'
            <form id="upload" method="post" action="?l" enctype="multipart/form-data">
                <input id="fileHidden" type="file" name="file" onchange="javascript: document.getElementById('fileName').value = this.value">
                <input id="fileName" type="text" name="fileName" placeholder="select file">
                <br />
                <button class="submitButton" type="submit"><span class="gray">up</span>load</button>
            </form>
            <a id="accessString" href="#" onclick="window.prompt('Copy the following string: (CTRL/CMD+C)', '{ACCESS_STRING}');">Click here and copy the access string to the load.link app.</a>
HTML
    ,

    'linkToUploadedFile' => <<<'HTML'
            <div id="link">
                Link to uploaded content:<br /><br />
                <a href="{URL}">{URL}</a>
            </div>
HTML
    ,

    'css' => <<<'CSS'
@charset "UTF-8";
* {
    margin: 0;
    padding: 0;
    font-family: Arial, Helvetica, sans-serif;
}
BODY, #contents {
    height: 190px;
}
BODY {
    position: absolute;
    bottom: 50%;
    right: 50%;
    max-width: 100%;
    max-height: 100%;
    width: 500px;
}
#contents {
    position: relative;
    top: 50%;
    left: 50%;
    background-color: #F5F5F5;
    text-align: center;
}
#logo {
    position: absolute;
    top: -50px;
    width: 500px;
    font-size: 30px;
    color: #555555;
}
#logo .gray {
    color: #E0E0E0;
}
@media screen and (max-height: 400px) {
    #logo {
        display: none;
    }
}
INPUT, BUTTON {
    border: 0;
    height: 50px;
    font-size: 30px;
    text-align: center;
    padding: 0 10px 0 10px;
    background-color: #FFFFFF;
    color: #555555;
}
INPUT {
    width: 80%;
    margin-top: 30px;
    margin-bottom: 30px;
}
INPUT[placeholder] {
    color: #E0E0E0;
}
BUTTON {
    position: relative;
    background-color: #FFFFFF;
    z-index: 11;
}
BUTTON:hover {
    background-color: #DDEEDD;
}
BUTTON .gray {
    color: #A0A0A0;
}
#fileHidden {
    position: absolute;
    top: -30px;
    left: -15px;
    width: 500px;
    height: 190px;
    opacity: 0;
    z-index: 10;
}
#accessString {
    position: relative;
    bottom: -50px;
    font-size: 12px;
    color: #A0A0A0;
    text-decoration: none;
}
#accessString:hover {
    color: #555555;
}
#logoutLink {
    position: relative;
    bottom: -70px;
    font-size: 14px;
}
#logoutLink A {
    color: #555555;
    text-decoration: none;
}
#logoutLink A:hover {
    color: #000000;
}
#link {
    padding-top: 65px;
    font-size: 14px;
    color: #555555;
}
#link A {
    color: #000000;
    text-decoration: none;
}
#link A:hover {
    text-decoration: underline;
}
CSS
    );

    protected $template;
    protected $buffer;

    public function __construct($template)
    {
        $this->template = $template;

        if ($this->template == 'css')
        {
            $this->buffer = $this->templates['css'];

            return $this;
        }

        $this->buffer = $this->templates['global'];

        if ($this->template == 'redirect')
        {
            $this->replace('HEADERS', $this->templates['metaRefresh']);
            $this->replace('CONTENT', '');

            return $this;
        }

        $this->replace('HEADERS', $this->templates['defaultHeaders']);
        $this->replace('CONTENT', $this->templates['logo'] . "\n" . '{CONTENT}');

        switch ($this->template)
        {
            case 'installer':

                $this->replace('CONTENT', $this->templates['installForm']);

                break;

            case 'uploader':

                $this->replace('CONTENT', $this->templates['uploadForm']
                    . $this->templates['logoutLink']);

                break;

            case 'login':

                $this->replace('CONTENT', $this->templates['loginForm']);

                break;

            case 'link':

                $this->replace('CONTENT', $this->templates['linkToUploadedFile']);

                break;
        }

        return $this;
    }

    protected function replace($search, $replace)
    {
        $this->buffer = str_replace('{' . $search . '}', $replace, $this->buffer);
    }

    public function with($search, $replace)
    {
        $this->replace($search, $replace);

        return $this;
    }

    public function getRaw()
    {
        return $this->templates[$this->template];
    }

    public function get()
    {
        return $this->buffer;
    }
}

class Uploader
{
    protected $db;
    protected $tmpFilePath;
    protected $fileName;
    protected $filePath;
    protected $fileMimeType;
    protected $id;

    public function getFileName()
    {
        return $this->fileName;
    }

    public function getFileMimeType()
    {
        return $this->fileMimeType;
    }

    public function __construct($fileName, $tmpFilePath)
    {
        $this->db           = $this->getDB();
        $this->tmpFilePath  = $tmpFilePath;
        $this->fileName     = $fileName;
        $this->filePath     = $this->getFilePath();
        $this->fileMimeType = self::detectFileMimeType($tmpFilePath);

        return $this;
    }

    protected function getFilePath()
    {
        $filePath = Config::getUploadDir() . '/' . $this->fileName;

        if (file_exists($filePath))
        {
            $filePath .= Config::getSameNameSuffix();
        }

        return $filePath;
    }

    protected static function detectFileMimeType($filePath)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $mimeType;
    }

    protected function getDB()
    {
        $databasePath = Config::getDatabasePath();

        if (!file_exists($databasePath))
        {
            fopen($databasePath, 'w');
            $db = array();
            file_put_contents($databasePath, serialize($db));
        }

        return unserialize(file_get_contents($databasePath));
    }

    protected function generateUniqueId()
    {
        $characters = Config::getIdCharacters();

        do
        {
            $id = '';
            for ($i = 0; $i < Config::getIdLength(); $i++)
            {
                $id .= $characters[rand(0, strlen($characters) - 1)];
            }
        }
        while (in_array($id, array_keys($this->db)));

        return $id;
    }

    protected function addToDB()
    {
        $this->id = $this->generateUniqueId();

        $this->db[$this->id] = array(
            'fileName'     => $this->fileName,
            'filePath'     => $this->filePath,
            'fileMimeType' => $this->fileMimeType);

        file_put_contents(Config::getDatabasePath(), serialize($this->db));
    }

    public function upload()
    {
        move_uploaded_file($this->tmpFilePath, $this->filePath);
        $this->addToDB();

        return $this;
    }

    public function getURL()
    {
        return 'http' . (Config::getHttps() ? 's' : '') . '://' . $_SERVER['HTTP_HOST'] . (!in_array($_SERVER['SERVER_PORT'], array(80, 443)) ? ':' . $_SERVER['SERVER_PORT'] : '') . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?')) . '?' . $this->id;
    }
}

class JsonApi
{
    protected $headers;
    protected $filePath;
    protected $response;

    public function __construct()
    {
        $this->headers  = json_decode(file_get_contents($_FILES['headers']['tmp_name']), true);
        $this->filePath = $_FILES['data']['tmp_name'];

        $this->headers['error'] = null;

        if ($this->headers['passwordHash'] !== Config::getPasswordHash())
        {
            $this->response['error'] = 'ACCESS DENIED';
        }

        if ($this->headers['fileHash'] !== hash('sha512', file_get_contents($this->filePath)))
        {
            $this->response['error'] = 'WRONG FILE HASH';
        }

        return $this;
    }

    public function upload()
    {
        if (!$this->response['error'])
        {
            $u = new Uploader($this->headers['fileName'], $this->filePath);
            $u->upload();
            $this->response['url'] = $u->getURL();
        }

        return $this;
    }

    public function getResponse()
    {
        return json_encode($this->response);
    }
}

class Installer
{
    public static function isAlreadyInstalled()
    {
        return (Config::getPasswordHash() != '') ? true : false;
    }

    public static function install()
    {
        $ph = $_SESSION['passwordHash'] = hash('sha512', ($_POST['password']));

        $thisFile = file(__FILE__);
        $thisFile[1] = '$passwordHash = \'' . $ph . '\';' . "\n";
        file_put_contents(__FILE__, implode('', $thisFile));
    }
}

class Page
{
    protected $headers;
    protected $buffer;
    protected $page;

    public function __construct()
    {
        $this->headers = array();
        $this->page    = self::getPage();

        session_start();

        switch ($this->page)
        {
            case 'css':

                $this->headers[] = 'Content-Type: text/css';

                $t = new Template('css');
                $this->buffer = $t
                    ->getRaw();

                return $this;

            case 'ins':

                if (!Installer::isAlreadyInstalled())
                {
                    Installer::install();

                    $t = new Template('redirect');
                    $this->buffer = $t
                        ->with('URL', '?')
                        ->get();
                }
                else
                {
                    $this->buffer = 'ACCESS DENIED';
                }

                return $this;

            case 'lgn':

                if (isset($_POST['password'])
                    && (hash('sha512', $_POST['password'])) === Config::getPasswordHash())
                {
                    $_SESSION['passwordHash'] = hash('sha512', $_POST['password']);
                    setcookie('passwordHash', $_SESSION['passwordHash']);
                }

                $t = new Template('redirect');
                $this->buffer = $t
                    ->with('URL', '?')
                    ->get();

                return $this;

            case 'lgt':

                unset($_SESSION['passwordHash']);
                session_destroy();
                unset($_COOKIE['passwordHash']);
                setcookie('passwordHash', '');

                $t = new Template('redirect');
                $this->buffer = $t
                    ->with('URL', '?')
                    ->get();

                return $this;

            case 'l':

                $u = new Uploader($_FILES["file"]["name"], $_FILES["file"]["tmp_name"]);
                $u->upload();

                if (in_array(substr($u->getFileMimeType(), 0, strpos($u->getFileMimeType(), '/')), array('text', 'image')))
                {
                    $t = new Template('redirect');
                    $this->buffer = $t
                        ->with('URL', $u->getURL())
                        ->get();
                }
                else
                {
                    $t = new Template('link');
                    $this->buffer = $t
                        ->with('URL', $u->getURL())
                        ->get();
                }

                return $this;

                /* TODO: Function to clear unused URLs, possibly at the user's request. */

            case 'j':

                $this->headers[] = 'Content-Type: application/json';

                $j = new JsonApi();
                $this->buffer = $j
                    ->upload()
                    ->getResponse();

                return $this;

            default:

                if (strlen($this->page) > 3)
                {
                    $db = unserialize(file_get_contents(Config::getDatabasePath()));

                    if (array_key_exists($this->page, $db)
                        && file_exists($db[$this->page]['fileName']))
                    {
                        $contentDisposition = (in_array(substr($db[$this->page]['fileMimeType'], 0, strpos($db[$this->page]['fileMimeType'], '/')), array('text', 'image')) ? 'inline' : 'attachment') . '; filename=' . $db[$this->page]['fileName'];

                        $this->headers = array_merge($this->headers, array(
                            'Content-Type: ' . $db[$this->page]['fileMimeType'],
                            'Content-Disposition: ' . $contentDisposition,
                            'Content-Length: ' . filesize($db[$this->page]['filePath'])
                        ));

                        $this->buffer = file_get_contents($db[$this->page]['filePath']);

                        return $this;
                    }
                    else
                    {
                        $this->headers[] = 'HTTP/1.1 404 Not Found';
                        $this->buffer    = 'Not Found';

                        return $this;
                    }
                }

                elseif (!Installer::isAlreadyInstalled())
                {
                    $t = new Template('installer');
                    $this->buffer = $t
                        ->get();

                    return $this;
                }

                else
                {
                    if (isset($_COOKIE['passwordHash'])
                        && $_COOKIE['passwordHash'] === Config::getPasswordHash())
                    {
                        if (!isset($_SESSION['passwordHash']))
                        {
                            $_SESSION['passwordHash'] === $_COOKIE['passwordHash'];
                        }

                        $loggedIn = true;
                    }
                    else
                    {
                        $loggedIn = false;
                    }

                    if ($loggedIn)
                    {
                        $accessString = $_SERVER['HTTP_HOST'] . '|' . $_SERVER['SERVER_PORT'] . '|' . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?')) . '|' . Config::getPasswordHash();

                        $t = new Template('uploader');
                        $this->buffer = $t
                            ->with('ACCESS_STRING', $accessString)
                            ->get();

                        return $this;
                    }
                    else
                    {
                        $t = new Template('login');
                        $this->buffer = $t
                            ->get();

                        return $this;
                    }
                }
        }
    }

    protected static function getPage()
    {
        return (!empty($_GET)) ? array_keys($_GET)[0] : null;
    }

    public function printBuffer()
    {
        foreach ($this->headers as $header)
        {
            header($header);
        }

        echo $this->buffer;
    }
}

$page = new Page();
$page->printBuffer();
