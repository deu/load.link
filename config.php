<?php
/** This file is part of load.link (https://github.com/deuiore/load.link).
 * View the LICENSE file for full license information.
 **/

class Config
{
    protected static $config;
    public static function get()
    {
        if (!self::exists())
        {
            return self::getDefaultConfig();
        }

        if (!self::$config)
        {
            self::$config = new self(Path::get('config'));
            self::$config->read();
        }
        return self::$config;
    }
    public static function getDefaultConfig()
    {
        $default_config = new self(Path::get('default_config'));
        $default_config->read();
        return $default_config;
    }

    protected static $exists;
    public static function exists()
    {
        if (!self::$exists)
        {
            self::$exists = file_exists(Path::get('config'));
        }
        return self::$exists;
    }

    protected $path;
    protected $ini;
    protected $cache;

    public function __construct($path = NULL)
    {
        if ($path)
        {
            $this->path = $path;

            if (self::exists() && $path == Path::get('config'))
            {
                $this->cache = Cache::get();
            }
        }
    }

    public function setPath($path)
    {
        $this->path = $path;
    }

    public function getValue($section, $key)
    {
        return $this->ini[$section][$key];
    }

    public function getSection($section)
    {
        return $this->ini[$section];
    }

    public function getAll()
    {
        return $this->ini;
    }

    public function set($section, $key, $value)
    {
        $this->ini[$section][$key] = $value;
    }

    public function setSection($section, $values)
    {
        $this->ini[$section] = $values;
    }

    public function setAllFromForm($form)
    {
        foreach ($form as $section => $keys)
        {
            foreach ($keys as $key => $value)
            {
                $lowercaseValue = strtolower($value);
                if ($lowercaseValue == 'true')
                {
                    $this->ini[$section][$value] = TRUE;
                }
                elseif ($lowercaseValue == 'false')
                {
                    $this->ini[$section][$value] = FALSE;
                }
                else
                {
                    $this->ini[$section][$value] = $value;
                }
            }
        }
    }

    public function setAll($ini)
    {
        $this->ini = $ini;
    }

    public function setPassword($password)
    {
        $hashAndSalt = Auth::passwordToHashAndSalt($password);
        $this->set('login', 'password', $hashAndSalt['hash']);
        $this->set('login', 'salt',     $hashAndSalt['salt']);
    }

    public function read()
    {
        if (!$this->path)
        {
            return FALSE;
        }

        if ($this->retrieveFromCache())
        {
            return $this->ini;
        }

        $this->ini = array();

        $section = '';
        foreach(file($this->path) as $line)
        {
            if (preg_match('/^\s*(;.*)?$/', $line))
            {
                continue;
            }
            elseif (preg_match('/\[(.*)\]/', $line, $match))
            {
                $section = $match[1];
                $this->ini[$section] = array();
            }
            elseif (preg_match('/^\s*(.*?)\s*=\s*(.*?)\s*$/', $line, $match))
            {
                $key = $match[1];
                $value = $match[2];

                $lowerValue = strtolower($value);
                if ($lowerValue == 'true')
                {
                    $value = TRUE;
                }
                elseif ($lowerValue == 'false')
                {
                    $value = FALSE;
                }
                else
                {
                    $value = trim($value, '"');
                }

                $this->ini[$section][$key] = $value;
            }
        }

        $this->storeToCache();

        return $this->ini;
    }

    public function write()
    {
        if (!$this->path)
        {
            return FALSE;
        }

        $ini = '';
        foreach ($this->ini as $section => $keys)
        {
            $ini .= '[' . $section . ']' . PHP_EOL;
            foreach ($keys as $key => $value)
            {
                $ini .= $key . ' = ';
                if (is_bool($value))
                {
                    if ($value)
                    {
                        $ini .= 'TRUE';
                    }
                    else
                    {
                        $ini .= 'FALSE';
                    }
                }
                else
                {
                    $ini .= '"' . $value . '"';
                }
                $ini .= PHP_EOL;
            }
            $ini .= PHP_EOL;
        }
        file_put_contents($this->path, $ini);

        $this->storeToCache();
    }

    protected function retrieveFromCache()
    {
        if ($this->cache && $this->cache->exists('config'))
        {
            $cache = $this->cache->retrieve('config');
            $mtime = filemtime($this->path);
            if ($mtime == $cache['mtime'])
            {
                $this->ini = $cache['data'];
                return $this->ini;
            }
        }
        return NULL;
    }

    protected function storeToCache()
    {
        if ($this->cache)
        {
            $mtime = filemtime($this->path);
            $this->cache->store('config', array(
                'mtime' => $mtime,
                'data' => $this->ini
            ));
            $this->cache->flush();
        }
    }

    public static function newFromArray($path, $base, $new)
    {
        $config = new self($path);
        $config->setAll($base->getAll());

        foreach ($config->getAll() as $section => $items)
        {
            foreach ($items as $key => $value)
            {
                if (isset($new[$section]) && isset($new[$section][$key]))
                {
                    $config->set($section, $key, $new[$section][$key]);
                }
            }
        }

        return $config;
    }

    public function check($test_permissions = TRUE)
    {
        if ($this->ini['database']['name'] == '')
        {
            throw new Error(Error::FATAL,
                'Database name too short.');
        }

        if ($this->ini['link']['length'] < 4)
        {
            throw new Error(Error::FATAL,
                'Link length must be at least 4.');
        }

        if ($this->ini['link']['characters'] == '')
        {
            throw new Error(Error::FATAL,
                'No link characters specified.');
        }

        if ($test_permissions)
        {
            $permissions = Utils::testPermissions(
                $this->ini['link']['upload_dir']);
            if (!$permissions['ok'])
            {
                throw $permissions['error'];
            }
        }

        if (in_array($this->ini['routing']['panel'],
            Router::getReservedRoutes()))
        {
            throw new Error(Error::FATAL,
                'Custom panel route URL reserved. Choose another one.');
        }

        if (in_array($this->ini['routing']['homepage'],
            Router::getReservedRoutes()))
        {
            throw new Error(Error::FATAL,
                'Custom homepage route URL reserved. Choose another one.');
        }

        if ($this->ini['login']['username'] == '')
        {
            throw new Error(Error::FATAL,
                'You must choose a username.');
        }

        if ($this->ini['login']['password'] == '')
        {
            throw new Error(Error::FATAL,
                'You must chooose a password.');
        }
    }
}
