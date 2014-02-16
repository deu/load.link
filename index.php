<?php
$passwordHash = 'a94a8fe5ccb19ba61c4c0873d391e987982fbbd3';
// --------------------------------------------------------
// DO NOT TOUCH THE FIRST TWO LINES.


$path = '.';
$database = '.db';

$length = 6; // must be longer than 3 characters.
$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';


session_start();
#session_unset();

$loggedIn = isset($_SESSION['passwordHash']) && $_SESSION['passwordHash'] === $passwordHash ? true : false;


/* template[login] {{{ */
$template['login'] = <<<'HTML'
            <form id="login" method="post" action="?lgn">
                <input id="password" type="password" name="password" placeholder="password">
                <br />
                <button class="submitButton" type="submit">login</button>
            </form>
HTML;
/* }}} */

/* template[installer] {{{ */
$template['installer'] = <<<'HTML'
            <form id="login" method="post" action="?ins">
                <input id="password" type="password" name="password" placeholder="choose a password">
                <br />
                <button class="submitButton" type="submit">install</button>
            </form>
HTML;
/* }}} */

/* template[upload] {{{ */
$template['upload'] = <<<'HTML'
            <form id="upload" method="post" action="?l" enctype="multipart/form-data">
                <input id="fileHidden" type="file" name="file" onchange="javascript: document.getElementById('fileName').value = this.value">
                <input id="fileName" type="text" name="fileName" placeholder="select file">
                <br />
                <button class="submitButton" type="submit"><span class="gray">up</span>load</button>
            </form>
            <a id="accessString" href="#" onclick="window.prompt('Copy the following string: (CTRL/CMD+C)', '{ACCESS_STRING}');">Click here and copy the access string to the load.link app.</a>
HTML;
/* }}} */

/* template[link] {{{ */
$template['link'] = <<<'HTML'
<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="UTF-8">
        <title>load.link</title>
    </head>
    <body>
        <div style="text-align: center;">
            Link to uploaded content:<br /><br />
            <a href="{LINK}">{LINK}</a>
        </div>
    </body>
</html>

HTML;
/* }}} */

/* template[global] {{{ */
$template['global'] = <<<'HTML'
<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="UTF-8">
        <title>load.link</title>
        <link rel="stylesheet" href="?css">
    </head>
    <body>
        <div id="contents">
            <div id="logo">load<span class="gray">link</span></div>
            {CONTENT}
        </div>
    </body>
</html>
HTML;
/* }}} */

/* template[redirect] {{{ */
$template['redirect'] = <<<'HTML'
<!DOCTYPE HTML>
<html>
    <head>
        <meta charset="UTF-8">
        <title>load.link</title>
        <meta http-equiv="refresh" content="0; url={URL}" />
    </head>
    <body>
    </body>
</html>
HTML;
/* }}} */

/* template[css] {{{ */
$template['css'] = <<<'CSS'
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
CSS;
/* }}} */

function upload($fileName, $tempFile)
{
    global $database, $length, $characters, $path;

    if (!file_exists($database))
    {
        fopen($database, 'w');
        $db = array();
        file_put_contents($database, serialize($db));
    }

    $db = unserialize(file_get_contents($database));

    do
    {
        $random = '';
        for ($i = 0; $i < $length; $i++)
        {
            $random .= $characters[rand(0, strlen($characters) - 1)];
        }
    }
    while (in_array($random, array_keys($db)));

    $name = $fileName;

    if (file_exists($path . '/' . $fileName))
    {
        $fileName .= '.1';
    }

    move_uploaded_file($tempFile, $path . '/' . $fileName);

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $path . '/' . $fileName);
    finfo_close($finfo);

    $db[$random] = array(
        'name' => $name,
        'fileName' => $fileName,
        'mime' => $mime);

    file_put_contents($database, serialize($db));

    return array(
        'url' => 'http://' . $_SERVER['HTTP_HOST'] . ($_SERVER['SERVER_PORT'] != 80 ? ':' . $_SERVER['SERVER_PORT'] : '') . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?')) . '?' . $random,
        'mime' => $mime
    );
}

$page = (!empty($_GET)) ? array_keys($_GET)[0] : null;

if (strlen($page) > 3)
{
    $db = unserialize(file_get_contents($database));

    if (array_key_exists($page, $db) && file_exists($path . '/' . $db[$page]['fileName']))
    {
        $name     = $db[$page]['name'];
        $filePath = $path . '/' . $db[$page]['fileName'];
        $mime     = $db[$page]['mime'];

        header('Content-Type: ' . $mime);
        header('Content-Disposition: ' . (in_array(substr($mime, 0, strpos($mime, '/')), array('text', 'image')) ? 'inline' : 'attachment') . '; filename=' . $name);
        header('Content-Length: ' . filesize($filePath));

        $output = file_get_contents($filePath);
    }
    else
    {
        $output = ''; // TODO: Error pages. Well, actually, error handling in general.
    }
}
else
{
    switch($page)
    {
        case 'ins':

            if ($passwordHash == '')
            {
                $password = $_SESSION['passwordHash'] = sha1($_POST['password']);
                $loggedIn = true;

                $file = file(__FILE__);
                $file[1] = '$passwordHash = \'' . $password . '\';' . "\n";
                file_put_contents(__FILE__, implode('', $file));

                $_SESSION['passwordHash'] = $password;
                $loggedIn = true;

                $output = str_replace('{URL}', '?', $template['redirect']);
            }
            else
            {
                $output = '';
            }

            break;

        case 'css':

            $output = $template['css'];
            header('Content-Type: text/css');

            break;

        case 'j':

            $json = json_decode(file_get_contents('php://input'));

            $response['token'] = $json['token'];

            if ($json['passwordHash'] === $passwordHash)
            {
                if ($json['hash'] != md5($json['data']))
                {
                    $tmp = tempnam(sys_get_temp_dir(), $json['hash']);
                    file_put_contents($tmp, $json['data']);
                    $response['url'] = upload($json['name'], $tmp)['url'];
                    unlink($tmp);
                }
                else
                {
                    $response['error'] = 'WRONG HASH';
                }
            }
            else
            {
                $response['error'] = 'ACCESS DENIED';
            }

            header('Content-Type: application/json');
            $output = $response;

            break;

        case 'l':

            $uploadedFile = upload($_FILES["file"]["name"], $_FILES["file"]["tmp_name"]);

            $mime = $uploadedFile['mime'];
            if (in_array(substr($mime, 0, strpos($mime, '/')), array('text', 'image')))
            {
                $output = str_replace('{URL}', $uploadedFile['url'], $template['redirect']);
            }
            else
            {
                $output = str_replace('{LINK}', $uploadedFile['url'], $template['link']);
            }

            /* TODO: Function to clear unused URLs, possibly at the user's request. */

            break;

        case 'lgn':

            if (isset($_POST['password']) && sha1($_POST['password']) === $passwordHash)
            {
                $_SESSION['passwordHash'] = sha1($_POST['password']);
                $loggedIn = true;
            }

            $output = str_replace('{URL}', '?', $template['redirect']);

            break;

        default:

            $accessString = $_SERVER['HTTP_HOST'] . '|' . $_SERVER['SERVER_PORT'] . '|' . substr($_SERVER['REQUEST_URI'], 0, strpos($_SERVER['REQUEST_URI'], '?')) . '|' . $passwordHash;

            $output = str_replace('{CONTENT}',
                $loggedIn ? str_replace('{ACCESS_STRING}', $accessString, $template['upload'])
                    : $template['login'], $template['global']);

            break;
    }
}

// "Installer":
if ($passwordHash == '' && !in_array($page, array('css', 'ins')))
{
    $output = str_replace('{CONTENT}', $template['installer'], $template['global']);
}

echo $output;
