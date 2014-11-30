<?php
/** This file is part of load.link (https://github.com/deuiore/load.link).
 * View the LICENSE file for full license information.
 **/

class Cache
{
    protected static $cache;
    public static function get()
    {
        if (!self::$cache)
        {
            self::$cache = new Cache();
        }
        return self::$cache;
    }

    protected $entities;
    protected $modified;

    public function __construct()
    {
        $this->entities = (file_exists(Path::get('cache'))) ?
            unserialize(file_get_contents(Path::get('cache'))) : array();

        $this->modified = FALSE;
    }

    public function exists($key)
    {
        return array_key_exists($key, $this->entities);
    }

    public function store($key, $value, $flush = FALSE)
    {
        $this->entities[$key] = $value;

        $this->modified = TRUE;

        if ($flush)
        {
            $this->flush();
        }
    }

    public function retrieve($key)
    {
        if (!$this->exists($key))
        {
            return NULL;
        }
        return $this->entities[$key];
    }

    public function flush()
    {
        if ($this->modified)
        {
            file_put_contents(Path::get('cache'), serialize($this->entities));
        }
    }
}
