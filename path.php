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
            case 'theme':
                return self::get()
                    . 'themes/' . Config::get()->getValue('ui', 'theme') . '/';
            case 'default_config';
                return self::get()
                    . 'default_config.ini';
            default:
                return dirname(__FILE__) . '/';
        }
    }
}
