<?php
/** This file is part of load.link (https://github.com/deuiore/load.link).
 * View the LICENSE file for full license information.
 **/

class Info
{
    protected $msg;

    public function __construct($message = '', $redirect = NULL)
    {
        $this->msg = new Message('notice', $message, $redirect);
    }

    public function getPage()
    {
        return $this->msg->getPage();
    }
}
