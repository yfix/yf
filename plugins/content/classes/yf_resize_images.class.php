<?php

/**
 * Image resizing class.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_resize_images
{
    /** @var string @conf_skip Image source file name */
    public $source_file = null;
    /** @var string @conf_skip Image type (like 'jpeg', 'gif', 'png' etc) detected from source file */
    public $source_type = null;
    /** @var int @conf_skip Image width (source) */
    public $source_width = null;
    /** @var int @conf_skipImage height (source) */
    public $source_height = null;
    /** @var mixed @conf_skip Image attributes (info from source) */
    public $source_atts = null;
    /** @var int Current limits for generating thumbnails */
    public $limit_x = 100;
    /** @var int Current limits for generating thumbnails */
    public $limit_y = 100;
    /** @var bool Reduce only or fit limits proportionally */
    public $reduce_only = false;
    /** @var bool Force processing even when source and dest x/y are equal */
    public $force_process = false;
    /** @var int @conf_skip Thumbnail width */
    public $output_width = null;
    /** @var int @conf_skip Thumbnail height */
    public $output_height = null;
    /** @var string Output image type (could be 'jpeg' or 'png') */
    public $output_type = 'jpeg';
    /** @var mixed @conf_skip For internal manipulation */
    public $tmp_img = null;
    /** @var mixed @conf_skip Temporary resampled file stored here */
    public $tmp_resampled = null;
    /** @var array @conf_skip Available image types */
    public $_avail_types = [
        1 => 'gif',
        2 => 'jpeg',
        3 => 'png',
        6 => 'wbmp',
    ];
    /** @var bool */
    public $SILENT_MODE = false;
    /** @var array */
    public $BACKGROUND_COLOR = [255, 255, 255];

    /**
     * @param mixed $img_file
     */
    public function __construct($img_file = '')
    {
        if (main()->is_unit_test()) {
            $this->SILENT_MODE = false;
        }
        if (strlen($img_file)) {
            $this->set_source($img_file);
        }
    }

    /**
     * Set source file for processing.
     * @param mixed $img_file
     */
    public function set_source($img_file = '')
    {
        $this->_clean_data();
        if (empty($img_file) || ! file_exists($img_file) || ! is_readable($img_file)) {
            if ( ! $this->SILENT_MODE) {
                trigger_error('Wrong image file!', E_USER_WARNING);
            }
            return false;
        }
        $this->source_file = $img_file;
        $this->_get_img_info();
        if (empty($this->source_width) || empty($this->source_height) || empty($this->source_type)) {
            if ( ! $this->SILENT_MODE) {
                trigger_error('Cant get correct image info!', E_USER_WARNING);
            }
            return false;
        }
        $this->_set_new_size_auto();
        $func_name = 'imagecreatefrom' . $this->source_type;
        $this->tmp_img = strlen($this->source_type) ? $func_name($this->source_file) : null;

        if (empty($this->tmp_img)) {
            return false;
        }

        return true;
    }

    /**
     * Set output image type.
     * @param mixed $new_type
     */
    public function set_output_type($new_type = '')
    {
        // If image type is set by number (1,2,3)
        if ( ! empty($new_type) && is_numeric($new_type) && isset($this->_avail_types[$new_type])) {
            $this->output_type = $this->_avail_types[$new_type];
        // If type is set by string name (gif, jpeg, png)
        } elseif ( ! empty($new_type) && in_array($new_type, $this->_avail_types)) {
            $this->output_type = $new_type;
        // Send error message if type is unknown
        } else {
            if ( ! $this->SILENT_MODE) {
                trigger_error('Unknown new output image type "' . $new_type . '"', E_USER_WARNING);
            }
            return false;
        }
        return true;
    }

    /**
     * Set new limits for processing image.
     * @param null|mixed $limit_x
     * @param null|mixed $limit_y
     */
    public function set_limits($limit_x = null, $limit_y = null)
    {
        if (is_numeric($limit_x) && is_numeric($limit_y) && $limit_x > 0 && $limit_y > 0) {
            $this->limit_x = $limit_x;
            $this->limit_y = $limit_y;
            return true;
        }
        return false;
    }

    /**
     * Save processed image into specified location.
     * @param mixed $output_file
     */
    public function save($output_file = '')
    {
        if (empty($this->tmp_img)) {
            if ( ! $this->SILENT_MODE) {
                trigger_error('No temporary image for resizing!', E_USER_WARNING);
            }
            return false;
        }
        $this->_set_new_size_auto();
        // Detect if need to resize image (if something has changed)
        if (
            $this->output_width != $this->source_width
            || $this->output_height != $this->source_height
            || $this->output_type != $this->source_type
            || $this->force_process
        ) {
            $this->tmp_resampled = imagecreatetruecolor($this->output_width, $this->output_height);
            if ( ! $this->tmp_resampled) {
                return false;
            }
            // png transparency
            if ($this->source_type == 'png' && $this->output_type == 'jpeg') {
                $bg_img = imagecreatetruecolor($this->source_width, $this->source_height);
                $bg = imagecolorallocate($bg_img, $this->BACKGROUND_COLOR[0], $this->BACKGROUND_COLOR[1], $this->BACKGROUND_COLOR[2]);
                imagefilledrectangle($bg_img, 0, 0, $this->source_width, $this->source_height, $bg);
                imagecopy($bg_img, $this->tmp_img, 0, 0, 0, 0, $this->source_width, $this->source_height);
                $this->tmp_img = $bg_img;
            }
            imagecopyresampled($this->tmp_resampled, $this->tmp_img, 0, 0, 0, 0, $this->output_width, $this->output_height, $this->source_width, $this->source_height);
            $func_name = 'image' . $this->output_type;
            $thumb_quality = defined('THUMB_QUALITY') ? THUMB_QUALITY : 85;
            if ($this->output_type == 'png') {
                $thumb_quality = $thumb_quality / 10;
            }
            strlen($this->output_type) ? $func_name($this->tmp_resampled, $output_file, $thumb_quality) : null;
            imagedestroy($this->tmp_resampled);
        } else {
            if ($this->source_file != $output_file) {
                $out_dir = dirname($output_file);
                if ( ! file_exists($out_dir)) {
                    mkdir($out_dir, 0755, $recursive = true);
                }
                copy($this->source_file, $output_file);
            }
        }
        return true;
    }

    /**
     * Show image (resample on the fly) NOTE: Expensive for the server CPU usage.
     */
    public function output_image()
    {
        if (empty($this->tmp_img)) {
            if ( ! $this->SILENT_MODE) {
                trigger_error('No temporary image for resizing!', E_USER_WARNING);
            }
            return false;
        }
        header('Content-Type: image/' . $this->output_type);
        $this->_set_new_size_auto();
        if ($this->output_width != $this->source_width || $this->output_height != $this->source_height || $this->output_type != $this->source_type) {
            $this->tmp_resampled = imagecreatetruecolor($this->output_width, $this->output_height);
            imagecopyresampled($this->tmp_resampled, $this->tmp_img, 0, 0, 0, 0, $this->output_width, $this->output_height, $this->source_width, $this->source_height);
        }
        $func_name = 'image' . $this->output_type;
        strlen($this->output_type) ? $func_name($this->tmp_resampled) : null;
        return true;
    }

    /**
     * Get image details and put them into class property.
     */
    public function _get_img_info()
    {
        if ( ! file_exists($this->source_file) || ! filesize($this->source_file)) {
            return false;
        }
        list($this->source_width, $this->source_height, $type, $this->source_atts) = getimagesize($this->source_file);
        return isset($this->_avail_types[$type]) ? ($this->source_type = $this->_avail_types[$type]) : false;
    }

    /**
     * Set new size of the thumbnail automatically (preserve proportions).
     */
    public function _set_new_size_auto()
    {
        if (empty($this->source_width) || empty($this->source_height)) {
            if ( ! $this->SILENT_MODE) {
                trigger_error('Missing source image sizes!', E_USER_WARNING);
            }
            return false;
        }
        $k1 = $this->source_width / $this->limit_x;
        $k2 = $this->source_height / $this->limit_y;
        $k = $k1 >= $k2 ? $k1 : $k2;
        if ($this->reduce_only && $k1 <= 1 && $k2 <= 1) {
            $this->output_width = $this->source_width;
            $this->output_height = $this->source_height;
        } else {
            $this->output_width = round($this->source_width / $k, 0);
            $this->output_height = round($this->source_height / $k, 0);
        }
        return true;
    }

    /**
     * Clean all data.
     */
    public function _clean_data()
    {
        $this->source_file = null;
        $this->source_type = null;
        $this->source_width = null;
        $this->source_height = null;
        $this->source_atts = null;
        $this->limit_x = 100;
        $this->limit_y = 100;
        $this->output_width = null;
        $this->output_height = null;
        $this->output_type = 'jpeg';
        $this->tmp_img = null;
        $this->tmp_resampled = null;
        return true;
    }
}
