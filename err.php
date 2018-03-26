<?php
/** This file is part of load.link (https://github.com/deuiore/load.link).
 * View the LICENSE file for full license information.
 **/

class Err extends Exception
{
    const FATAL = 'error fatal';
    const FORBIDDEN = 'error forbidden';
    const NOT_FOUND = 'error not_found';
    const WARNING = 'error warning';
    protected static $default_title = array(
        self::FATAL => 'Error',
        self::FORBIDDEN => 'Access Denied',
        self::NOT_FOUND => 'Not Found',
        self::WARNING => 'Warning',
    );

    protected $msg;
    protected $type;

    public function __construct($type = self::FATAL, $message = '',
        $redirect = NULL)
    {
        $this->msg = new Message($type, $message, $redirect);
        $this->msg->setTitle(self::$default_title[$type]);
        $this->type = $type;
        parent::__construct($message);
    }

    public function getPage()
    {
        $page = $this->msg->getPage();

        switch ($this->type)
        {
            case self::FORBIDDEN:
                $page->setResponseCode(403);
                break;

            case self::NOT_FOUND:
                $page->setResponseCode(404);
                break;
        }

        return $page;
    }
}
