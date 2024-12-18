<?php

/**
 * Generating thumbs from images handler.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_make_thumb
{
    /** @var array */
    public $ALLOWED_MIME_TYPES = [
        'image/jpeg' => 'jpeg',
        'image/pjpeg' => 'jpeg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/x-ms-bmp' => 'wbmp',
    ];

    public $IMAGICK_MIME_TYPES_NEED_TRANSPARENCY = [
        'PNG' => 'PNG32',
        'GIF' => 'PNG32',
    ];


    /** @var bool */
    public $ALLOW_IMAGICK = true;
    /** @var array */
    public $LIBS_PRIORITY = [
        'imagick',
        'gd',
    ];
    /** @var bool */
    public $ENABLE_DEBUG_LOG = false;
    /** @var bool */
    public $LOG_EXEC_CMDS = false;
    /** @var string */
    public $DEBUG_LOG_FILE = 'logs/make_thumb.log';
    /** @var bool Depends on ENABLE_DEBUG_LOG */
    public $LOG_TO_FILE = true;
    /** @var bool Depends on ENABLE_DEBUG_LOG */
    public $DB_LOG_ENV = true;
    /** @var string Folder for temporary images */
    public $BAD_IMAGES_DIR = 'logs/bad_images/';
    /** @var bool Collect wrong images */
    public $COLLECT_BAD_IMAGES = false;
    /** @var bool Delete wrong images from destination folder */
    public $DELETE_BAD_DEST_IMAGES = false;
    /** @var bool Force resizing for images with lower sizes than limits, but possibly with not optimal size */
    public $FORCE_PROCESSING = false;
    /** @var string */
    public $WATERMARK_ALIGN_X = 'center';
    /** @var string */
    public $WATERMARK_ALIGN_Y = 'middle';
    /** @var array */
    public $IMAGE_ALIGN_X = [
        'center',
        'right',
        'left',
    ];
    /** @var array */
    public $IMAGE_ALIGN_Y = [
        'middle',
        'top',
        'bottom',
    ];


    public function _init()
    {
        if ( ! extension_loaded('imagick')) {
            $this->ALLOW_IMAGICK = false;
        }
        if ($this->COLLECT_BAD_IMAGES) {
            $bad_images_path = APP_PATH . $this->BAD_IMAGES_DIR;
            if ( ! file_exists($bad_images_path)) {
                _mkdir_m($bad_images_path, 0777);
            }
        }
        if (empty($this->LIBS_PRIORITY)) {
            $this->LIBS_PRIORITY = ['gd'];
        }
        if ( ! empty($this->WATERMARK_POSITION)) {
            if (strpos($this->WATERMARK_POSITION, '-')) {
                $position = explode('-', $this->WATERMARK_POSITION);
                $this->WATERMARK_ALIGN_X = $position[0];
                $this->WATERMARK_ALIGN_Y = $position[1];
            }
            if ($this->WATERMARK_POSITION == 'random') {
                $this->WATERMARK_ALIGN_X = $this->IMAGE_ALIGN_X[rand(0, 2)];
                $this->WATERMARK_ALIGN_Y = $this->IMAGE_ALIGN_Y[rand(0, 2)];
            }
        }
    }

    /**
     * Make thumbnail using best available method.
     * @param mixed $source_file_path
     * @param mixed $dest_file_path
     * @param mixed $LIMIT_X
     * @param mixed $LIMIT_Y
     * @param mixed $watermark_path
     * @param mixed $ext
     */
    public function go($source_file_path = '', $dest_file_path = '', $LIMIT_X = -1, $LIMIT_Y = -1, $watermark_path = '', $ext = '')
    {
        $LIMIT_X = (int) ($LIMIT_X != -1 ? $LIMIT_X : THUMB_WIDTH);
        $LIMIT_Y = (int) ($LIMIT_Y != -1 ? $LIMIT_Y : THUMB_HEIGHT);
        if (empty($source_file_path) || empty($dest_file_path)) {
            trigger_error('MAKE_THUMB: Source or destination path is missing', E_USER_WARNING);
            return false;
        }
        if ( ! file_exists($source_file_path) || ! filesize($source_file_path) || ! is_readable($source_file_path)) {
            trigger_error('MAKE_THUMB: Source file is empty', E_USER_WARNING);
            return false;
        }
        if ($this->ENABLE_DEBUG_LOG) {
            $source_size = filesize($source_file_path);
            $_start_time = microtime(true);
        }
        $USED_LIB = '';
        $tried_libs = [];
        $tried_cmds = [];
        // Use libs in specified priority order
        foreach ((array) $this->LIBS_PRIORITY as $cur_lib) {
            $lib_result_error = false;
            if ($cur_lib == 'gd') {
                $result = $this->_use_gd($source_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y, $watermark_path, $ext);
            } elseif ($cur_lib == 'imagick' && $this->ALLOW_IMAGICK) {
                $result = $this->_use_imagick($source_file_path, $dest_file_path, $LIMIT_X, $LIMIT_Y, $watermark_path);
            }
            $USED_LIB = $cur_lib;
            if ( ! $result) {
                $lib_result_error = true;
            }
            $tried_libs[$USED_LIB] = $USED_LIB;
            $resize_success = false;
            if ( ! $lib_result_error) {
                $resize_success = (file_exists($dest_file_path) && filesize($dest_file_path) > 0 && is_readable($dest_file_path));
            }
            // Stop on first tried library
            break;
        }
        if ( ! $resize_success && $this->COLLECT_BAD_IMAGES) {
            $bad_file_path = APP_PATH . $this->BAD_IMAGES_DIR . basename($source_file_path);
            copy($source_file_path, $bad_file_path);
        }
        if ( ! $resize_success && $this->DELETE_BAD_DEST_IMAGES && file_exists($dest_file_path)) {
            unlink($dest_file_path);
        }
        if ($watermark_path && $dest_file_path) {
            $this->add_watermark($dest_file_path, $watermark_path);
        }
        return $resize_success;
    }

    /**
     * @param mixed $source_img_path
     * @param mixed $watermark_path
     */
    public function add_watermark($source_img_path, $watermark_path)
    {
        $img_info = getimagesize($source_img_path);
        $source_mime_type = $this->ALLOWED_MIME_TYPES[$img_info['mime']];
        if ( ! $source_mime_type) {
            $source_mime_type = 'jpeg';
        }
        $img_create_func = 'imagecreatefrom' . $source_mime_type;
        $img = $img_create_func($source_img_path);
        $width_orig = imagesx($img);
        $height_orig = imagesy($img);

        $watermark = imagecreatefrompng($watermark_path);
        imagealphablending($watermark, true);
        imagesavealpha($watermark, true);

        $watermarkwidth = imagesx($watermark);
        $watermarkheight = imagesy($watermark);
        if (($watermarkwidth > $watermarkheight && $width_orig > $height_orig)
        || ($watermarkwidth < $watermarkheight && $width_orig < $height_orig)) {
            $thumb_watermark_w = (int) ($width_orig / 1.2);
            $thumb_watermark_h = (int) (($thumb_watermark_w / $watermarkwidth) * $watermarkheight);
        } else {
            $thumb_watermark_h = (int) ($height_orig / 1.2);
            $thumb_watermark_w = (int) (($thumb_watermark_h / $watermarkheight) * $watermarkwidth);
        }
        $thumb_watermark = imagecreatetruecolor($thumb_watermark_w, $thumb_watermark_h);
        imagefill($thumb_watermark, 0, 0, imagecolorallocatealpha($thumb_watermark, 255, 255, 255, 127));
        imagecopyresampled($thumb_watermark, $watermark, 0, 0, 0, 0, $thumb_watermark_w, $thumb_watermark_h, $watermarkwidth, $watermarkheight);

        $watermarkwidth = imagesx($thumb_watermark);
        $watermarkheight = imagesy($thumb_watermark);

        $startwidth = [
            'left' => 0,
            'right' => $width_orig - $watermarkwidth,
            'center' => (($width_orig - $watermarkwidth) / 2),
        ];
        $startheight = [
            'top' => 0,
            'bottom' => $height_orig - $watermarkheight,
            'middle' => (($height_orig - $watermarkheight) / 2),
        ];
        imagecopy($img, $thumb_watermark, $startwidth[$this->WATERMARK_ALIGN_X], $startheight[$this->WATERMARK_ALIGN_Y], 0, 0, $watermarkwidth, $watermarkheight);
        $img_save_func = 'image' . $source_mime_type;
        $img_save_func($img, $source_img_path);
    }


    /**
     * Use GD library.
     * @param mixed $source_file_path
     * @param mixed $dest_file_path
     * @param mixed $LIMIT_X
     * @param mixed $LIMIT_Y
     * @param mixed $watermark_path
     * @param mixed $output_type
     */
    public function _use_gd($source_file_path = '', $dest_file_path = '', $LIMIT_X = -1, $LIMIT_Y = -1, $watermark_path = '', $output_type = '')
    {
        $I = _class('resize_images');
        $I->reduce_only = 1;
        if ($this->FORCE_PROCESSING) {
            $I->force_process = true;
        }
        $result = false;
        if ($I->set_source($source_file_path)) {
            $I->set_limits($LIMIT_X, $LIMIT_Y);
            if ($output_type) {
                $I->set_output_type($output_type);
            }
            _class('dir')->mkdir(dirname($dest_file_path));
            $I->save($dest_file_path);
            $result = true;
        }
        $I->_clean_data();

        return $result;
    }

    /**
     * Use Image Magick library	http://www.imagemagick.org/.
     * @param mixed $source
     * @param mixed $dest
     * @param mixed $x
     * @param mixed $y
     */
    public function _use_imagick($source, $dest, $x = -1, $y = -1)
    {
        $img = new Imagick($source);
        $image_format = $img->getImageFormat();
        if (isset($this->IMAGICK_MIME_TYPES_NEED_TRANSPARENCY[$image_format])) {
            $img->setImageCompressionQuality(85);
            $img->resizeImage($x, $y, Imagick::FILTER_LANCZOS, 1.1, $bestfit = true);
        } else {
            $img->resizeImage($x, $y, null, null, $bestfit = true);
        }
        return $img->writeImage($dest);
    }

    /**
     * detect animated gif for imagick.
     * @param mixed $source_file_path
     **/
    public function _is_gif_animated_imagick($source_file_path)
    {
        $nb_image_frame = 0;
        $img = new Imagick($source_file_path);
        foreach ($img->deconstructImages() as $i) {
            $nb_image_frame++;
            if ($nb_image_frame > 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * Get image info using imagick extension.
     * @param mixed $file_path
     */
    public function _image_info_imagick($file_path = '')
    {
        $img = new Imagick($file_path);
        $type_by_mime = [
            'image/jpeg' => 'jpeg',
            'image/pjpeg' => 'jpeg',
            'image/png' => 'png',
            'image/gif' => 'gif',
        ];
        return [
            'type' => $type_by_mime[$img->getImageMimeType()],
            'width' => $img->getImageWidth(),
            'height' => $img->getImageHeight(),
        ];
    }
}
