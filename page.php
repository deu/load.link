<?php
/** This file is part of load.link (https://github.com/deuiore/load.link).
 * View the LICENSE file for full license information.
 **/

class Page
{
    protected $response_code;
    protected $headers;
    protected $buffer;
    protected $file;
    protected $template;
    protected $cache;

    protected $config;

    protected $elements;

    protected static $extensions = array(
        'templates' => 'twig',
        'stylesheets' => 'scss',
        'scripts' => 'js'
    );

    public function __construct($template = NULL, $cache_enabled = TRUE)
    {
        $this->response_code = 200;
        $this->headers       = array();
        $this->buffer        = NULL;
        $this->cache         = NULL;

        if (!$template)
        {
            return;
        }

        $this->template = $template;
        $this->headers[] = 'Content-Type: text/html; charset=UTF-8';

        $this->config = Config::get();

        if (!in_array($template, array('installer', 'message')))
        {
            $this->cache = Cache::get();
        }

        $this->elements = array();
    }

    public function setResponseCode($response_code)
    {
        $this->response_code = $response_code;
    }

    public function addHeader($header)
    {
        $this->headers[] = $header;
    }

    public function setBuffer($buffer)
    {
        $this->buffer = $buffer;
    }

    public function setFile($file)
    {
        $this->file = $file;
    }

    public function set($elements)
    {
        foreach ($elements as $key => $value)
        {
            $this->elements[$key] = $value;
        }
    }

    protected $scss;
    protected $jsqueeze;
    protected function loadAsset($type, $filename, $function = NULL)
    {
        if ($function)
        {
            return $function(file_get_contents($filename));
        }

        switch ($type)
        {
            case 'stylesheets':
                if (!$this->scss)
                {
                    $this->scss = new scssc();
                    $this->scss->setImportPaths(
                        Path::get('theme') . 'stylesheets/');
                    $this->scss->setFormatter(
                        '\Leafo\ScssPhp\Formatter\Compressed');
                }
                return $this->scss->compile('@import "'
                    . basename($filename) . '";');

            case 'scripts':
                if (!$this->jsqueeze)
                {
                    $this->jsqueeze = new JSqueeze();
                }
                return $this ->jsqueeze->squeeze(
                    file_get_contents($filename));

            default:
                return file_get_contents($filename);
        }
    }

    protected function getAsset($type, $name, $function = NULL)
    {
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        $name = ($ext) ? $name : $name . '.' . self::$extensions[$type];
        $filename = Path::get('theme') . $type . '/' . $name;

        if (!file_exists($filename))
        {
            return '';
        }

        if (!$this->cache)
        {
            return $this->loadAsset($type, $filename, $function);
        }

        $key = 'page@' . $type . '@' . $name;
        $mtime = filemtime($filename);

        if ($this->cache->exists($key))
        {
            $cache = $this->cache->retrieve($key);
            if ($mtime == $cache['mtime'])
            {
                return $cache['data'];
            }
        }

        $asset =  $this->loadAsset($type, $filename, $function);
        $this->cache->store($key, array(
            'mtime' => $mtime,
            'data' => $asset
        ));
        return $asset;
    }

    protected function startResponse()
    {
        http_response_code($this->response_code);

        foreach ($this->headers as $header)
        {
            header($header);
        }
    }

    public function render()
    {
        if ($this->file)
        {
            if (!file_exists($this->file['path']))
            {
                $error = new Err(Err::NOT_FOUND);
                $error->getPage()->render();
                exit();
            }

            $this->response_code = 200;
            $this->headers = array(
                'Content-Type: ' . $this->file['mime'],
                'Content-Disposition: inline; ' . 'filename="'
                    . $this->file['name'] . '"',
                'Content-Length: ' . filesize($this->file['path'])
            );

            $this->startResponse();

            $chunk_size = Config::get()->getValue('link', 'chunk_size');
            $fh = fopen($this->file['path'], 'rb');
            while (!feof($fh))
            {
                $buffer = fread($fh, $chunk_size);
                echo $buffer;
                ob_flush();
                flush();
            }
            fclose($fh);

            return;
        }

        if ($this->template)
        {
            $stylesheets = array( '_global', $this->template );
            $this->elements['css'] = '';
            foreach ($stylesheets as $stylesheet)
            {
                $this->elements['css'] .= $this->getAsset(
                    'stylesheets', $stylesheet);
            }

            $scripts = array( '_global', $this->template );
            $dependencies = $this->getAsset('scripts', '_dependencies.json',
                function($json) { return json_decode($json, TRUE); });
            if ($dependencies)
            {
                if (array_key_exists($this->template, $dependencies))
                {
                    $scripts = array_merge($scripts,
                        $dependencies[$this->template]);
                }
            }
            $this->elements['scripts'] = '';
            foreach ($scripts as $script)
            {
                $this->elements['scripts'] .= $this->getAsset(
                    'scripts', $script);
            }

            if ($this->cache)
            {
                $this->cache->flush();
            }

            $twig_loader = new Twig_Loader_Filesystem();
            $twig_loader->addPath(Path::get('theme') . 'templates/');
            $twig = new Twig_Environment($twig_loader);
            $this->setBuffer($twig->render($this->template
                . '.' . self::$extensions['templates'], $this->elements));
        }

        $this->startResponse();
        echo $this->buffer;
    }
}
