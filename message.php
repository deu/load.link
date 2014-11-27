<?php
/** This file is part of load.link (https://github.com/deuiore/load.link).
 * View the LICENSE file for full license information.
 **/

class Message
{
    const REDIRECT_WAIT = 3;
    protected $name;
    protected $message;
    protected $redirect;
    protected $refreshable;
    protected $title;

    public function __construct($name, $message = '', $redirect = NULL,
        $refreshable = TRUE)
    {
        $this->title = NULL;
        $this->name = $name;
        $this->message = $message;
        $this->redirect = (is_bool($redirect) && $redirect == TRUE) ?
            array('wait' => self::REDIRECT_WAIT) : $redirect;
    }

    public function setTitle($title)
    {
        $this->title = $title;
    }

    public function getPage()
    {
        $page = new Page('message');

        $page->set(array(
            'redirect' => $this->redirect,
            'refreshable' => $this->refreshable,
            'name' => $this->name,
            'title' => $this->title,
            'message' => $this->message
        ));

        return $page;
    }
}
