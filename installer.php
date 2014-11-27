<?php
/** This file is part of load.link (https://github.com/deuiore/load.link).
 * View the LICENSE file for full license information.
 **/

class Installer
{
    const CONFIG_TMP = '.config.tmp';
    const CONFIG_BACKUP = '.config.backup';

    protected static $php_required_version = '5.4';
    protected static $gd_required_version = '2.0';
    protected static $pecl_extensions = array(
        array('name' => 'hash', 'version' => '1.0',
            'description' => 'PECL hash'),
        array('name' => 'fileinfo', 'version' => '0.1.0',
            'description' => 'PECL fileinfo')
    );
    protected static $database_types = array(
        array('extension' => 'pdo_sqlite', 'version' => '1.0',
            'description' => 'PDO SQLite3',
            'name' => 'sqlite', 'fullname' => 'SQLite'),
        array('extension' => 'pdo_mysql', 'version' => '1.0',
            'description' => 'PDO MySQL',
            'name' => 'mysql', 'fullname' => 'MySQL'),
        array('extension' => 'pdo_pgsql', 'version' => '1.0',
            'description' => 'PDO PostgreSQL',
            'name' => 'pgsql', 'fullname' => 'PostgreSQL'),
    );
    protected static $routing_modes = array(
        'path' => 'PATH',
        'get'  => 'GET'
    );

    public function __construct()
    {
        if (Config::exists())
        {
            $error = new Error(Error::FATAL, 'Already installed.');
            $error->getPage()->render();
            exit();
        }

        if (file_exists(self::CONFIG_TMP))
        {
            $this->install();
        }
        else
        {
            session_start();

            if (isset($_SESSION['uuid']) && isset($_POST['uuid']))
            {
                $this->makeConfig();
            }
            else
            {
                $this->renderForms();
            }
        }
    }

    protected function checkExtension($name)
    {
        try
        {
            $extension = new ReflectionExtension($name);
        }
        catch (ReflectionException $e)
        {
            return '0 (Not Found)';
        }

        return $extension->getVersion();
    }

    protected function checks()
    {
        $checks = array();

        $php_required = self::$php_required_version;
        $php_found = phpversion();
        $checks_ok = $php_result = version_compare(
            $php_found, $php_required, '>=');
        $checks[] = array(
            'description' => 'PHP version >= ' . $php_required,
            'found' => $php_found,
            'result' => $php_result
        );

        $gd_required = self::$gd_required_version;

        if (extension_loaded('gd'))
        {
            $gd_found = gd_info()['GD Version'];
            $checks_ok = $gd_result = version_compare(
                $gd_found, $gd_required, '>=');
        }
        else
        {
            $gd_found = '0 (Not Found)';
            $checks_ok = $gd_result = FALSE;
        }
        $checks[] = array(
            'description' => 'GD version >= ' . $gd_required,
            'found' => $gd_found,
            'result' => $gd_result
        );

        foreach (self::$pecl_extensions as $ext)
        {
            $found = $this->checkExtension($ext['name']);
            $result = version_compare(
                $found, $ext['version'], '>=');

            $checks[] = array(
                'description' => $ext['description']
                    . ' version >= ' . $ext['version'],
                'found' => $found,
                'result' => $result
            );

            if (!$result)
            {
                $checks_ok = FALSE;
            }
        }

        $db_types = array();
        $db_support = 0;
        foreach (self::$database_types as $db)
        {
            $found = $this->checkExtension($db['extension']);
            $result = version_compare(
                $found, $db['version'], '>=');

            $checks[] = array(
                'description' => $db['description']
                    . ' version >= ' . $db['version'],
                'found' => $found,
                'result' => $result
            );

            if ($result)
            {
                $db_types[] = array(
                    'name' => $db['name'],
                    'description' => $db['fullname']
                );
                $db_support++;
            }
        }
        if (!$db_support)
        {
            $checks_ok = FALSE;
        }

        $permissions = Utils::testPermissions('.');

        $checks[] = array(
            'description' => 'Permissions in current directory',
            'found' => ($permissions['ok']) ? 'YES'
                : 'NO (' . $permissions['error']->getMessage() . ')',
            'result' => $permissions['ok']
        );
        if (!$permissions['ok'])
        {
            $checks_ok = FALSE;
        }

        return array(
            'checks' => $checks,
            'result' => $checks_ok
        );
    }

    protected function renderForms()
    {
        $_SESSION['uuid'] = uniqid();

        $checks = $this->checks();

        if (file_exists(self::CONFIG_BACKUP))
        {
            $config = new Config(self::CONFIG_BACKUP);
            $config->read();
        }
        else
        {
            $config = Config::getDefaultConfig();
            $config->set('link', 'upload_dir',
                pathinfo($_SERVER['SCRIPT_FILENAME'],
                PATHINFO_DIRNAME) . 'uploads/');
        }

        $page = new Page('installer');
        $page->set(array(
            'checks' => $checks['checks'],
            'checks_ok' => $checks['result'],
            'uuid' => $_SESSION['uuid'],
            'config' => $config->getAll(),
            'database_types' => self::$database_types,
            'routing_modes' => self::$routing_modes,
            'baseurl' => $_SERVER['REQUEST_URI'],
            'themes' => Utils::getThemes()
        ));

        session_write_close();

        $page->render();
    }

    protected function makeConfig()
    {
        if ($_SESSION['uuid'] != $_POST['uuid'])
        {
            new Error(Error::FATAL, 'Session expired. Try to reload.');
            exit();
        }
        unset($_POST['uuid']);

        $password = $_POST['login']['password'];
        unset($_POST['login']['password']);

        $config = Config::newFromArray(self::CONFIG_TMP,
            Config::getDefaultConfig(), $_POST);

        $config->setPassword($password);

        $config->write();

        $_SESSION = array();
        if (ini_get("session.use_cookies"))
        {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]);
        }
        session_destroy();

        $info = new Info('Installing...', TRUE);
        $info->getPage()->render();
        exit();
    }

    protected function install()
    {
        $abort = NULL;
        try
        {
            rename(self::CONFIG_TMP, Config::PATH);

            $config = Config::get();
            $config->check();

            if ($config->get('database', 'type') == 'sqlite')
            {
                DB::sqlite_create($config->get('database', 'name'));
            }

            DB::get()->install();
        }
        catch (Error $error)
        {
            $abort = $error;
        }
        catch (Exception $e)
        {
            $abort = new Error(Error::FATAL,
                'Could not install. Check your configuration.');
        }

        if ($abort)
        {
            rename(Config::PATH, self::CONFIG_BACKUP);
            $abort->getPage()->render();
            exit();
        }

        if (file_exists(self::CONFIG_BACKUP))
        {
            unlink(self::CONFIG_BACKUP);
        }

        $info = new Info('Installed. Redirecting...', TRUE);
        $info->getPage()->render();
        exit();
    }
}
