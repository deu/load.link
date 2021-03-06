<?php
/** This file is part of load.link (https://github.com/deuiore/load.link).
 * View the LICENSE file for full license information.
 **/

class API
{
    const PATH = 'api';

    protected $headers;
    protected $file_path;
    protected $page;
    protected $auth;

    public function __construct()
    {
        $this->auth = new Auth();

        try
        {
            if (isset($_FILES['file']))
            {
                @$this->file_upload($_FILES['file']);
            }
            else
            {
                @$this->parse();
            }
        }
        catch (Exception $e)
        {
            $this->setResponse(400, array(
                'message' => 'Badly Formatted Request.'
            ));
        }
    }

    protected function file_upload($file)
    {
        if (!$this->auth->authorizeFromBearerAuthHeader())
        {
            $this->setRawResponse(403, "Access Denied.");
            return;
        }

        $this->filePath = $file['tmp_name'];

        $upload = new Uploader($file['name'], $this->filePath);

        if ($upload->upload())
        {
            $this->SetRawResponse(201, $upload->getLink());
        }
        else
        {
            $this->setRawResponse(202, "Upload Failed.");
        }
    }

    protected function parse()
    {
        $this->headers = json_decode(
            file_get_contents($_FILES['headers']['tmp_name']), TRUE);

        if ($this->headers['action'] == 'get_token')
        {
            if ($this->auth->authorizeFromLogin(
                $this->headers['username'],
                $this->headers['password']))
            {
                $this->setResponse(200, array(
                    'message' => 'OK.',
                    'token' => $this->auth->getToken()));
            }
            else
            {
                $this->setResponse(403, array(
                    'message' => 'Access Denied.'));
            }
            return;
        }

        if (!$this->auth->authorizeFromToken($this->headers['token']))
        {
            $this->setResponse(403, array(
                'message' => 'Access Denied.'));
            return;
        }

        switch ($this->headers['action'])
        {
            case 'get_links':
                $limit = (isset($this->headers['limit'])) ?
                    $this->headers['limit'] : 0;
                $offset = (isset($this->headers['offset'])) ?
                    $this->headers['offset'] : 0;
                $links = DB::get()->getLinks($limit, $offset);
                $this->setResponse(200, array(
                    'message' => 'OK.',
                    'links' => $links
                ));
                return;

            case 'count':
                $count = DB::get()->countLinks();
                $this->setResponse(200, array(
                    'message' => 'OK.',
                    'count' => $count
                ));
                return;

            case 'get_thumbnail':
                $thumbnail = DB::get()->getThumbnail($this->headers['uid']);
                if ($thumbnail)
                {
                    $this->setResponse(200, array(
                        'message' => 'OK.',
                        'data' => base64_encode($thumbnail['data']),
                        'width' => $thumbnail['width'],
                        'height' => $thumbnail['height'],
                        'mime' => $thumbnail['mime']
                    ));
                }
                else
                {
                    $this->setResponse(202, array(
                        'message' => 'Could not get thumbnail.'
                    ));
                }
                return;

            case 'upload':
                $this->filePath = $_FILES['data']['tmp_name'];
                $upload = new Uploader($this->headers['filename'],
                    $this->filePath);
                if ($upload->upload())
                {
                    $this->setResponse(201, array(
                        'message' => 'OK.',
                        'uid' => $upload->getUID(),
                        'name' => $upload->getName(),
                        'mime' => $upload->getMime(),
                        'ext' => $upload->getExt(),
                        'link' => $upload->getLink()
                    ));
                }
                else
                {
                    $this->setResponse(202, array(
                        'message' => 'Upload Failed.'
                    ));
                }
                return;

            case 'shorten_url':
                $shortener = new Shortener($this->headers['url']);
                if ($shortener->shorten())
                {
                    $this->setResponse(201, array(
                        'message' => 'OK.',
                        'uid' => $shortener->getUID(),
                        'link' => $shortener->getLink()
                    ));
                }
                else
                {
                    $this->setResponse(202, array(
                        'message' => 'Shortening Failed.'
                    ));
                }
                return;

            case 'delete':
                DB::get()->delLink($this->headers['uid']);
                $this->setResponse(200, array(
                    'message' => 'OK.'
                ));
                return;

            case 'edit_settings':
                if (!Auth::checkPassword($this->headers['password']))
                {
                    $this->setResponse(403, array(
                        'message' => 'Could not update settings: '
                        . 'wrong password.'));
                    return;
                }
                try
                {
                    $config = Config::newFromArray(Path::get('config'),
                        Config::get(), $this->headers['settings']);

                    $new_password = $this->headers[
                        'settings']['login']['password'];
                    if ($new_password)
                    {
                        $config->setPassword($new_password);
                    }

                    $config->check(FALSE);
                    $config->write();
                }
                catch (Err $error)
                {
                    $this->setResponse(202, array(
                        'message' => 'Could not update settings. Reason:'
                            . PHP_EOL . $error->getMessage()
                    ));
                    return;
                }
                $this->setResponse(200, array(
                    'message' => 'OK.',
                ));
                return;

            case 'release_token':
                $this->auth->unauthorize();
                $this->setResponse(200, array(
                    'message' => 'OK.'
                ));
                return;

            case 'release_all_tokens':
                $this->auth->unauthorizeAll();
                $this->setResponse(200, array(
                    'message' => 'OK.'
                ));
                return;

            case 'prune_unused':
                $pruned = DB::get()->pruneUnused();
                $this->setResponse(200, array(
                    'message' => 'OK.',
                    'pruned' => $pruned
                ));
                return;

            default:
                throw new Exception('Unknown API function.');
        }
    }

    protected function setResponse($code, $items)
    {
        $this->page = new Page();
        $this->page->setResponseCode($code);
        $this->page->addHeader('Content-Type: application/json');
        $this->page->setBuffer(json_encode($items));
    }

    protected function setRawResponse($code, $data)
    {
        $this->page = new Page();
        $this->page->setResponseCode($code);
        $this->page->addHeader('Content-Type: text/plain');
        $this->page->setBuffer($data);
    }

    public function getPage()
    {
        return $this->page;
    }
}
