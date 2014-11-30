<?php
/** This file is part of load.link (https://github.com/deuiore/load.link).
 * View the LICENSE file for full license information.
 **/

class Shortener
{
    protected $url;
    protected $uid;

    public function __construct($url)
    {
        $this->url = $url;
    }

    public function shorten()
    {
        try
        {
            $uid = DB::get()->addLink(
                '', $this->url, 'wwwserver/redirection');
        }
        catch (Exception $e)
        {
            return FALSE;
        }

        $this->uid = $uid;
        return TRUE;
    }

    public function getUID()
    {
        return $this->uid;
    }

    public function getLink()
    {
        if (!$this->uid)
        {
            throw new Error(Error::FATAL,
                'UID not found.');
        }

        return Router::getURL() . $this->uid;
    }
}
