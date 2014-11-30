<?php
/** This file is part of load.link (https://github.com/deuiore/load.link).
 * View the LICENSE file for full license information.
 **/

class Path
{
    public static function get($path = NULL)
    {
        switch ($path)
        {
            case 'cache';
                return self::get()
                    . '.cache';
            case 'config';
                return self::get()
                    . '.config.ini';
            case 'config_backup';
                return self::get()
                    . '.config.ini.backup';
            case 'config_tmp';
                return self::get()
                    . '.config.ini.tmp';
            case 'default_config';
                return self::get()
                    . 'default_config.ini';
            case 'theme':
                return self::get()
                    . 'themes/' . Config::get()->getValue('ui', 'theme') . '/';
            default:
                return dirname(__FILE__) . '/';
        }
    }
}
