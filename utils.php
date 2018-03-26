<?php
/** This file is part of load.link (https://github.com/deuiore/load.link).
 * View the LICENSE file for full license information.
 **/

class Utils
{
    public static function detectMime($path)
    {
        switch (pathinfo($path, PATHINFO_EXTENSION))
        {
            case 'css':
                return 'text/css';
            case 'js':
                return 'application/javascript';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path);
        finfo_close($finfo);

        return $mime;
    }

    public static function testPermissions($path)
    {
        $permissions_ok = TRUE;
        $permissions_error = NULL;

        try
        {
            $test_path = $path . '.test.tmp';
            $test2_path = $path . '.test.tmp.2';
            $test_contents = $path . 'TEST';
            $test_file = fopen($test_path, 'w');
            if (!$test_file)
            {
                throw new Err(Err::FATAL,
                    'Permissions error in "' . $path . '": could not create.');
            }
            if (!fwrite($test_file, $test_contents))
            {
                throw new Err(Err::FATAL,
                    'Permissions error in "' . $path . '": could not write.');
            }
            if (!fclose($test_file))
            {
                throw new Err(Err::FATAL,
                    'Permissions error in "' . $path . '": could not close.');
            }
            if (!rename($test_path, $test2_path))
            {
                throw new Err(Err::FATAL,
                    'Permissions error in "' . $path . '": could not rename.');
            }
            $test_file_contents = file_get_contents($test2_path);
            if ($test_file_contents != $test_contents)
            {
                throw new Err(Err::FATAL,
                    'Permissions error in "' . $path . '": could not read.');
            }
            if (!unlink($test2_path))
            {
                throw new Err(Err::FATAL,
                    'Permissions error in "' . $path . '": could not delete.');
            }
        }
        catch (Exception $e)
        {
            $permissions_ok = FALSE;
            $permissions_error = $e;
        }

        return array(
            'ok' => $permissions_ok,
            'error' => $permissions_error
        );
    }

    public static function getThemes()
    {
        $themes = array();
        foreach (scandir(Path::get() . 'themes') as $theme)
        {
            if (!in_array($theme, array(
                '.', '..')))
            {
                $themes[] = $theme;
            }
        }

        return $themes;
    }

    protected static $languages = array(
        'none'         => array('name' => 'None',         'ext' => 'txt'),
        'bash'         => array('name' => 'Bash',         'ext' => 'sh'),
        'c'            => array('name' => 'C',            'ext' => 'c'),
        'cpp'          => array('name' => 'C++',          'ext' => 'cpp'),
        'csharp'       => array('name' => 'C#',           'ext' => 'cs'),
        'coffeescript' => array('name' => 'CoffeeScript', 'ext' => 'coffee'),
        'css'          => array('name' => 'CSS',          'ext' => 'css'),
        'go'           => array('name' => 'Go',           'ext' => 'go'),
        'haskell'      => array('name' => 'Haskell',      'ext' => 'hs'),
        'ini'          => array('name' => 'INI',          'ext' => 'ini'),
        'java'         => array('name' => 'Java',         'ext' => 'java'),
        'javascript'   => array('name' => 'JavaScript',   'ext' => 'js'),
        'latex'        => array('name' => 'LaTeX',        'ext' => 'tex'),
        'markup'       => array('name' => 'Markup',       'ext' => 'xml'),
        'objectivec'   => array('name' => 'Objective-C',  'ext' => 'm'),
        'php'          => array('name' => 'PHP',          'ext' => 'php'),
        'python'       => array('name' => 'Python',       'ext' => 'py'),
        'ruby'         => array('name' => 'Ruby',         'ext' => 'rb'),
        'scss'         => array('name' => 'SCSS',         'ext' => 'scss'),
        'sql'          => array('name' => 'SQL',          'ext' => 'sql'),
        'swift'        => array('name' => 'Swift',        'ext' => 'swift'),
        'twig'         => array('name' => 'Twig',         'ext' => 'twig')
    );
    protected static $languages_extension = array(
        'txt', 'sh', 'c', 'h', 'c++', 'cpp', 'hpp', 'hxx', 'cxx', 'cc', 'cs',
        'coffee', 'css', 'go', 'hs', 'ini', 'java', 'js', 'tex', 'xml',
        'html', 'm', 'mm','php', 'py', 'rb','twig', 'scss', 'sql', 'swift'
    );
    public static function getLanguagesList()
    {
        return self::$languages;
    }
    public static function getLanguagesExtensionsList()
    {
        return self::$languages_extension;
    }
    public static function detectLanguageFromExtension($extension)
    {
        switch ($extension)
        {
            case 'sh':
                return 'bash';
            case 'c':
            case 'h':
                return 'c';
            case 'c++':
            case 'cpp':
            case 'hpp':
            case 'hxx':
            case 'cxx':
            case 'cc':
                return 'cpp';
            case 'cs':
                return 'csharp';
            case 'coffee':
                return 'coffeescript';
            case 'css':
                return 'css';
            case 'go':
                return 'go';
            case 'hs':
                return 'haskell';
            case 'ini':
                return 'ini';
            case 'java':
                return 'java';
            case 'js':
                return 'javascript';
            case 'tex':
                return 'latex';
            case 'xml':
            case 'html':
                return 'markup';
            case 'm':
            case 'mm':
                return 'objectivec';
            case 'php':
                return 'php';
            case 'py':
                return 'python';
            case 'rb':
                return 'ruby';
            case 'twig':
                return 'twig';
            case 'scss':
                return 'scss';
            case 'sql':
                return 'sql';
            case 'swift':
                return 'swift';
            case 'txt':
            default:
                return 'none';
        }
    }

    public static $media_formats = array(
        /* WebM */
        'video/webm', 'audio/webm',
        /* Ogg */
        'audio/ogg', 'video/ogg', 'application/ogg',
        /* MP3 */
        'audio/mpeg',
        /* MP4 */
        'audio/aac', 'audio/mp4', 'video/mp4',
        /* WAVE */
        'audio/wave', 'audio/wav', 'audio/x-wav', 'audio/x-pn-wav',
    );
    public static function getMediaFormatsList()
    {
        return self::$media_formats;
    }

    const THUMBNAIL_SIZE = 150;
    public static function generateThumbnail($path, $mime = NULL)
    {
        if (!$mime)
        {
            $mime = self::detectMime($path);
        }

        if (!in_array($mime, array('image/jpeg', 'image/png', 'image/gif')))
        {
            return NULL;
        }

        $image = array(
            'create_from' => array(
                'image/jpeg' => 'imagecreatefromjpeg',
                'image/png'  => 'imagecreatefrompng',
                'image/gif'  => 'imagecreatefromgif'
            ),
            'print' => array(
                'image/jpeg' => 'imagejpeg',
                'image/png'  => 'imagepng',
                'image/gif'  => 'imagegif'
            )
        );
        $old_image = $image['create_from'][$mime]($path);

        list($old_width, $old_height) = getimagesize($path);

        $new_width = $new_height = round(min(self::THUMBNAIL_SIZE,
            max($old_width, $old_height)));
        $ratio = $old_width / $old_height;
        if ($ratio < 1)
        {
            $new_width = round($new_height * $ratio);
        }
        else
        {
            $new_height = round($new_width / $ratio);
        }

        $new_image = imagecreatetruecolor($new_width, $new_height);

        imagecopyresampled($new_image, $old_image, 0, 0, 0, 0,
            $new_width, $new_height, $old_width, $old_height);

        ob_implicit_flush(FALSE);
        ob_start();
        $image['print'][$mime]($new_image);
        $thumbnail = ob_get_contents();
        ob_end_clean();

        return array(
            'data'   => $thumbnail,
            'width'  => $new_width,
            'height' => $new_height,
            'mime'   => $mime
        );
    }
}
