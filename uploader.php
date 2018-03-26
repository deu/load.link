<?php
/** This file is part of load.link (https://github.com/deuiore/load.link).
 * View the LICENSE file for full license information.
 **/

class Uploader
{
    protected $conf;
    protected $tmp_path;
    protected $name;
    protected $path;
    protected $mime;
    protected $ext;
    protected $uid;

    public function __construct($name, $tmp_path)
    {
        $this->conf = Config::get()->getSection('link');

        $this->tmp_path = $tmp_path;

        $name = str_replace('\\', '\\\\', $name);
        $name = str_replace('"', '\"', $name);
        $this->name = $name;
        $this->mime = Utils::detectMime($tmp_path);
        $this->ext = pathinfo($this->name, PATHINFO_EXTENSION);

        $this->path = $this->conf['upload_dir'] . $this->name;
        while (file_exists($this->path))
        {
            $this->path .= $this->conf['same_name_suffix'];
        }
    }

    public function upload()
    {
        if (move_uploaded_file($this->tmp_path, $this->path))
        {
            try
            {
                $uid = DB::get()->addLink(
                    $this->path, $this->name, $this->mime);
            }
            catch (Exception $e)
            {
                return FALSE;
            }

            $this->uid = $uid;
            return TRUE;
        }
    }

    public function getUID()
    {
        return $this->uid;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getMime()
    {
        return $this->mime;
    }

    public function getExt()
    {
        return $this->ext;
    }

    public function getLink()
    {
        if (!$this->uid)
        {
            throw new Err(Err::FATAL,
                'UID not found.');
        }

        return Router::getURL() . $this->uid
            . (($this->ext
                && Config::get()->getValue('link', 'show_extension')) ?
                '.' . $this->ext : '');
    }
}
