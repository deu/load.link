<?php
/** This file is part of load.link (https://github.com/deuiore/load.link).
 * View the LICENSE file for full license information.
 **/

class Content
{
    const STATIC_PATH = 'static/';

    protected $page;

    public function __construct($route)
    {
        $pathinfo = pathinfo($route);
        $link = $pathinfo['filename'];
        $extension = (isset($pathinfo['extension'])) ? $pathinfo['extension']
            : '';

        $item = DB::get()->getLink($link);

        /* 404 -*/
        if (!$item)
        {
            $error = new Err(Err::NOT_FOUND);
            $this->page = $error->getPage();
            return;
        }

        /* Redirect */
        if ($item['mime'] == 'wwwserver/redirection')
        {
            $this->page = new Page();
            $this->page->setResponseCode(303);
            $this->page->addHeader('Location: ' . $item['name']);
            return;
        }

        /* Syntax Highlighter */
        if (Config::get()->getValue('ui', 'syntax_highlighter')
            && $extension != 'raw')
        {
            $original_ext = pathinfo($item['name'], PATHINFO_EXTENSION);
            if (in_array($original_ext, Utils::getLanguagesExtensionsList()))
            {
                $this->page = new Page('syntax_highlighter');
                $language = Utils::detectLanguageFromExtension($original_ext);
                $this->page->set(array(
                    'static_path' =>
                        Config::get()->getValue('routing', 'baseurl')
                        . self::STATIC_PATH,
                    'name' => $item['name'],
                    'text' => file_get_contents($item['path']),
                    'language' => $language,
                    'raw_url' => Router::getURL() . $link . '.raw'
                ));
                return;
            }
        }

        /* Media player */
        if (Config::get()->getValue('ui', 'media_player')
            && $extension != 'raw')
        {
            if (in_array($item['mime'], Utils::getMediaFormatsList()))
            {
                $this->page = new Page('media_player');
                $this->page->set(array(
                    'static_path' =>
                        Config::get()->getValue('routing', 'baseurl')
                        . self::STATIC_PATH,
                    'type' => explode('/', $item['mime'])[0],
                    'name' => $item['name'],
                    'mime' => $item['mime'],
                    'raw_url' => Router::getURL() . $link . '.raw'
                ));
                return;
            }
        }

        /* Default, just display the file as is */
        $this->page = new Page();
        $this->page->setFile($item);
    }

    public function getPage()
    {
        return $this->page;
    }

    public static function getStaticPage($path)
    {
        $file = Path::get('theme') . self::STATIC_PATH . $path;

        if (strpos($path, '..') || !file_exists($file))
        {
            $error = new Err(Err::NOT_FOUND);
            return $error->getPage();
        }

        $page = new Page();
        $page->addHeader('Content-Type: ' . Utils::detectMime($file));
        $page->addHeader('Content-Length: ' . filesize($file));
        $page->setBuffer(file_get_contents($file));

        return $page;
    }
}
