<?php

load('cache_driver', '', 'classes/cache/');
class yf_cache_driver_files extends yf_cache_driver
{
    /** @var @conf_skip */
    public $FILE_PREFIX = 'cache_';
    /** @var @conf_skip */
    public $FILE_EXT = '.php';
    /** @var bool Auto-create cache folder */
    public $CREATE_DIRS = true;
    /** @var int Number of levels of subdirs, set to 0 to store everything in plain dir */
    public $DIR_LEVELS = 2;
    /** @var int Number of symbols from name to use in subdirs, example: name = testme, subdir == te/st/ */
    public $DIR_STEP = 2;
    /** @var int Octal value of cache dir and subdirs */
    public $DIR_CHMOD = 0777;


    public function _init()
    {
        $this->CACHE_DIR = STORAGE_PATH . 'core_cache/';
        if ($this->CREATE_DIRS && ! file_exists($this->CACHE_DIR)) {
            mkdir($this->CACHE_DIR, $this->DIR_CHMOD, true);
        }
    }


    public function is_ready()
    {
        return file_exists($this->CACHE_DIR) && is_writable($this->CACHE_DIR);
    }

    /**
     * @param mixed $name
     * @param mixed $ttl
     * @param mixed $params
     */
    public function get($name, $ttl = 0, $params = [])
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        $path = $this->_dir_by_name($name) . $this->FILE_PREFIX . $name . $this->FILE_EXT;
        return $this->_get_cache_file($path, $ttl);
    }

    /**
     * @param mixed $name
     * @param mixed $data
     * @param mixed $ttl
     */
    public function set($name, $data, $ttl = 0)
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        $path = $this->_dir_by_name($name) . $this->FILE_PREFIX . $name . $this->FILE_EXT;
        return $this->_put_cache_file($data, $path) ?: null;
    }

    /**
     * @param mixed $name
     */
    public function del($name)
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        $path = $this->_dir_by_name($name) . $this->FILE_PREFIX . $name . $this->FILE_EXT;
        if (file_exists($path)) {
            unlink($path);
        }
        return ! file_exists($path) ? true : null;
    }


    public function flush()
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        foreach ((array) $this->_get_all_files() as $path) {
            unlink($path);
        }
        return true;
    }


    public function list_keys()
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        $keys = [];
        foreach ((array) $this->_get_all_files() as $path) {
            $name = substr(trim(basename($path)), strlen($this->FILE_PREFIX), -strlen($this->FILE_EXT));
            if ($name) {
                $keys[$name] = $name;
            }
        }
        return array_keys($keys);
    }


    public function _get_all_files()
    {
        if ( ! $this->is_ready()) {
            return null;
        }
        return _class('dir')->rglob($this->CACHE_DIR, '*' . $this->FILE_PREFIX . '*' . $this->FILE_EXT);
    }

    /**
     * @param mixed $name
     */
    public function _dir_by_name($name)
    {
        $dir = $this->CACHE_DIR;
        if ( ! $this->DIR_LEVELS) {
            return $dir;
        }
        $step = $this->DIR_STEP;
        for ($i = 0; $i < $this->DIR_LEVELS; $i++) {
            $dir .= substr($name, $i * $step, $step) . '/';
        }
        if ($this->CREATE_DIRS && ! file_exists($dir)) {
            mkdir($dir, $this->DIR_CHMOD, true);
        }
        return $dir;
    }

    /**
     * Do get cache file contents.
     * @param mixed $path
     * @param mixed $ttl
     */
    public function _get_cache_file($path = '', $ttl = 0)
    {
        if (empty($path)) {
            return null;
        }
        if ( ! file_exists($path)) {
            return null;
        }
        $last_modified = filemtime($path);
        $ttl = (int) ($ttl ?: $this->_parent->TTL);
        if ($last_modified < (time() - $ttl)) {
            return null;
        }
        $data = [];
        if (DEBUG_MODE) {
            $_time_start = microtime(true);
        }

        $data = include $path;

        if (DEBUG_MODE) {
            $_cf = strtolower(str_replace(DIRECTORY_SEPARATOR, '/', $path));
            debug('include_files_exec_time::' . $_cf, microtime(true) - $_time_start);
        }
        return $data;
    }

    /**
     * Do put cache file contents.
     * @param mixed $data
     * @param mixed $path
     */
    public function _put_cache_file($data = [], $path = '')
    {
        if (empty($path)) {
            return null;
        }
        $str = str_replace(' => ' . PHP_EOL . 'array (', '=>array(', preg_replace('/^\s+/m', '', var_export($data, 1)));
        $str = '<?' . 'php' . PHP_EOL . 'return ' . $str . ';' . PHP_EOL;

        // http://php.net/manual/en/function.file-put-contents.php
        // This function returns the number of bytes that were written to the file, or FALSE on failure.
        return (bool) file_put_contents($path, $str);
    }


    public function stats()
    {
        $usage = 0;
        foreach ($this->_get_all_files() as $file) {
            $usage += filesize($file);
        }
        return [
            'hits' => null,
            'misses' => null,
            'uptime' => null,
            'mem_usage' => $usage,
            'mem_avail' => disk_free_space($this->CACHE_DIR),
        ];
    }
}
