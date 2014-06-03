<?php
/*
 * This file is part of the Microweber framework.
 *
 * (c) Microweber LTD
 *
 * For full license information see
 * http://microweber.com/license/
 *
 */

namespace Microweber;

/**
 * Content class is used to get and save content in the database.
 *
 * @package Content
 * @category Content
 * @desc  These functions will allow you to get and save content in the database.
 *
 */
class Template
{

    /**
     * An instance of the Microweber Application class
     *
     * @var $app
     */
    public $app;

    function __construct($app = null)
    {
        if (!is_object($this->app)) {
            if (is_object($app)) {
                $this->app = $app;
            } else {
                $this->app = Application::getInstance();
            }
        }
    }
    public function dir()
    {
        if (!defined('TEMPLATE_DIR')) {
            $this->app->content->define_constants();
        }
        if (defined('TEMPLATE_DIR')) {
            return TEMPLATE_DIR;
        }
    }
    public function url()
    {
        if (!defined('TEMPLATE_URL')) {
            $this->app->content->define_constants();
        }
        if (defined('TEMPLATE_URL')) {
            return TEMPLATE_URL;
        }

    }

    public function name()
    {

        if (!defined('TEMPLATE_NAME')) {
            $this->app->content->define_constants();
        }
        if (defined('TEMPLATE_NAME')) {
            return TEMPLATE_NAME;
        }
    }

    public function header($script_src)
    {
        static $mw_template_headers;
        if ($mw_template_headers == null) {
            $mw_template_headers = array();
        }

        if (is_string($script_src)) {
            if (!in_array($script_src, $mw_template_headers)) {
                $mw_template_headers[] = $script_src;
                return $mw_template_headers;
            }
        } else if (is_bool($script_src)) {
            //   return $mw_template_headers;
            $src = '';
            if (is_array($mw_template_headers)) {
                foreach ($mw_template_headers as $header) {
                    $ext = get_file_extension($header);
                    switch (strtolower($ext)) {


                        case 'css':
                            $src .= '<link rel="stylesheet" href="' . $header . '" type="text/css" media="all">' . "\n";
                            break;

                        case 'js':
                            $src .= '<script type="text/javascript" src="' . $header . '"></script>' . "\n";
                            break;


                        default:
                            $src .= $header . "\n";
                            break;
                    }
                }
            }
            return $src;
        }
    }

    /**
     * @desc  Get the template layouts info under the layouts subdir on your active template
     * @param $options
     * $options ['type'] - 'layout' is the default type if you dont define any. You can define your own types as post/form, etc in the layout.txt file
     * @return array
     * @author    Microweber Dev Team
     * @since Version 1.0
     */
    public function site_templates($options = false)
    {

        $args = func_get_args();
        $function_cache_id = '';
        foreach ($args as $k => $v) {
            $function_cache_id = $function_cache_id . serialize($k) . serialize($v);
        }
        $cache_id = __FUNCTION__ . crc32($function_cache_id);
        $cache_group = 'templates';
        $cache_content = $this->app->cache->get($cache_id, $cache_group, 'files');
        if (($cache_content) != false) {
            return $cache_content;
        }
        if (!isset($options['path'])) {
            $path = MW_TEMPLATES_DIR;
        } else {
            $path = $options['path'];
        }

        $path_to_layouts = $path;
        $layout_path = $path;
        $map = $this->directory_map($path, TRUE, TRUE);
        $to_return = array();
        if (!is_array($map) or empty($map)) {
            return false;
        }
        foreach ($map as $dir) {
            //$filename = $path . $dir . DIRECTORY_SEPARATOR . 'layout.php';
            $filename = $path . DIRECTORY_SEPARATOR . $dir;
            $filename_location = false;
            $filename_dir = false;
            $filename = normalize_path($filename);
            $filename = rtrim($filename, '\\');
            $filename = (substr($filename, 0, 1) === '.' ? substr($filename, 1) : $filename);
            if (is_dir($filename)) {
                $fn1 = normalize_path($filename, true) . 'config.php';
                $fn2 = normalize_path($filename);
                if (is_file($fn1)) {
                    $config = false;
                    include ($fn1);
                    if (!empty($config)) {
                        $c = $config;
                        $c['dir_name'] = $dir;
                        $screensshot_file = $fn2 . '/screenshot.png';
                        $screensshot_file = normalize_path($screensshot_file, false);
                        if (is_file($screensshot_file)) {
                            $c['screenshot'] = $this->app->url->link_to_file($screensshot_file);
                        }
                        $to_return[] = $c;
                    }
                } else {
                    $filename_dir = false;
                }
                //	$path = $filename;
            }

        }
        $this->app->cache->save($to_return, $function_cache_id, $cache_group, 'files');
        return $to_return;
    }

    /**
     * Create a Directory Map
     *
     *
     * Reads the specified directory and builds an array
     * representation of it.  Sub-folders contained with the
     * directory will be mapped as well.
     *
     * @author        ExpressionEngine Dev Team
     * @link        http://codeigniter.com/user_guide/helpers/directory_helper.html
     * @access    public
     * @param    string    path to source
     * @param    int        depth of directories to traverse (0 = fully recursive, 1 = current dir, etc)
     * @return    array
     */
    function directory_map($source_dir, $directory_depth = 0, $hidden = FALSE, $full_path = false)
    {
        if ($fp = @opendir($source_dir)) {
            $filedata = array();
            $new_depth = $directory_depth - 1;
            $source_dir = rtrim($source_dir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

            while (FALSE !== ($file = readdir($fp))) {
                // Remove '.', '..', and hidden files [optional]
                if (!trim($file, '.') OR ($hidden == FALSE && $file[0] == '.')) {
                    continue;
                }

                if (($directory_depth < 1 OR $new_depth > 0) && @is_dir($source_dir . $file)) {
                    $filedata[$file] = $this->directory_map($source_dir . $file . DIRECTORY_SEPARATOR, $new_depth, $hidden, $full_path);
                } else {
                    if ($full_path == false) {
                        $filedata[] = $file;
                    } else {
                        $filedata[] = $source_dir . $file;
                    }

                }
            }

            closedir($fp);
            return $filedata;
        }

        return FALSE;
    }

}