<?php

/**
 * Custom error handler.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_core_errors
{
    public $HTML_ERRORS = true;
    public $ERROR_MODE = false;
    /** @var int Error reporting level */
    public $ERROR_LEVEL = 0;
    /** @var int Error reporting level for production/non-debug mode (int from built-in constants) */
    public $ERROR_LEVEL_PROD = 0;

    /** @var int Error reporting level for DEBUG_MODE enabled */
    // public $ERROR_LEVEL_DEBUG = E_ALL & ~E_NOTICE;
    public $ERROR_LEVEL_DEBUG = E_ALL & ~E_NOTICE;

    // public $pattern_ignore = '~^(Undefined array key)~';
    public $pattern_ignore = '~^(Undefined array key|Undefined variable)~';
    // public $pattern_ignore = '~^(Undefined array key|Undefined property|Undefined variable)~';

    /** @var bool Log errors to the error file? */
    public $LOG_ERRORS_TO_FILE = true;
    /** @var bool Log warnings to the error file? */
    public $LOG_WARNINGS_TO_FILE = true;
    /** @var bool Log notices to the error file? */
    public $LOG_NOTICES_TO_FILE = false;
    /** @var bool Log deprecated to the error file? */
    public $LOG_DEPRECATED_TO_FILE = true;
    /** @var string Log errors switcher, keep empty to disable logging */
    public $ERROR_LOG_PATH = '{LOGS_PATH}yf_core_errors.log';
    /** @var string
     * The filename of the log file.
     * NOTE: $error_log_filename will only be used if you have log_errors Off and ;error_log filename in php.ini
     * if log_errors is On, and error_log is set, the filename in error_log will be used.
     */
    public $error_log_filename = 'yf_core_errors{suffix}.log';
    /** @var bool Show start and end log headers or not */
    public $_SHOW_BORDERS = false;
    /** @var bool @conf_skip Started log output or not */
    public $_LOG_STARTED = false;
    /** @var Use compact format */
    public $USE_COMPACT_FORMAT = true;
    /** @var string Could be any sequence from GPFCS */
    public $ENV_ARRAYS = 'GPF';
    /** @var bool Quickly turn off notices */
    public $NO_NOTICES = true;
    /** @var array @conf_skip Standard error types */
    public $error_types = [
        E_ERROR => 'E_ERROR',
        E_WARNING => 'E_WARNING',
        E_PARSE => 'E_PARSE',
        E_NOTICE => 'E_NOTICE',
        E_CORE_ERROR => 'E_CORE_ERROR',
        E_CORE_WARNING => 'E_CORE_WARNING',
        E_COMPILE_ERROR => 'E_COMPILE_ERROR',
        E_COMPILE_WARNING => 'E_COMPILE_WARNING',
        E_USER_ERROR => 'E_USER_ERROR',
        E_USER_WARNING => 'E_USER_WARNING',
        E_USER_NOTICE => 'E_USER_NOTICE',
        E_ALL => 'E_ALL',
        E_RECOVERABLE_ERROR => 'E_RECOVERABLE_ERROR',
        E_DEPRECATED => 'E_DEPRECATED',
        E_USER_DEPRECATED => 'E_USER_DEPRECATED',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        if ($this->ERROR_LOG_PATH) {
            ini_set('error_log', main()->_replace_core_paths($this->ERROR_LOG_PATH));
        }
        $this->set_log_file_name($this->_get_file_path());

        $this->init_reporting();
    }

    /**
     * Method that changes the error reporting level.
     */
    public function init_reporting($force_error_mode = null)
    {
        # New mode designed to show errors without enabling DEBUG_MODE
        if (defined('ERROR_MODE')) {
            $this->ERROR_MODE = constant('ERROR_MODE');
        }
        $conf_error_mode = conf('ERROR_MODE');
        if ($conf_error_mode) {
            $this->ERROR_MODE = $conf_error_mode;
        }
        if (DEBUG_MODE) {
            $this->ERROR_MODE = true;
        }
        if (isset($force_error_mode)) {
            $this->ERROR_MODE = $force_error_mode;
        }

        $this->ERROR_LEVEL = $this->ERROR_LEVEL_PROD;
        if ($this->ERROR_MODE) {
            $this->ERROR_LEVEL = $this->ERROR_LEVEL_DEBUG;
        }
        if (defined('ERROR_LEVEL')) {
            $this->ERROR_LEVEL = constant('ERROR_LEVEL');
        }
        # Set value to E_ERROR is you want to turn off error level to minimum
        $conf_error_level = conf('ERROR_LEVEL');
        if ($conf_error_level) {
            $this->ERROR_LEVEL = $conf_error_level;
        }

        error_reporting($this->ERROR_LEVEL);
        ini_set('ignore_repeated_errors', 1);
        ini_set('ignore_repeated_source', 1);

        if( php_sapi_name() == 'cli' ) {
            $this->HTML_ERRORS = false;
            ini_set('html_errors', false);
        }

        set_error_handler([$this, 'error_handler'], E_ALL);
        set_exception_handler([$this,  'exception_handler']);

        register_shutdown_function([$this, 'error_handler_destructor']);
    }

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args);
    }

    /**
     * The error handling routine set by set_error_handler().
     * return true = Don't execute PHP internal error handler
     * @param mixed $error_type
     * @param mixed $error_msg
     * @param mixed $error_file
     * @param mixed $error_line
     * @param mixed $error_context
     */
    public function error_handler($error_type, $error_msg, $error_file, $error_line)
    {
        if ($this->pattern_ignore && preg_match($this->pattern_ignore, $error_msg)) {
            return true;
        }

        // quickly turn off notices logging
        if ($this->NO_NOTICES && ($error_type == E_NOTICE || $error_type == E_USER_NOTICE)) {
            return true;
        }
        $msg = '';
        $save_log = false;
        // Process critical errors
        if ($error_type == E_ERROR || $error_type == E_USER_ERROR) {
            if ($this->LOG_ERRORS_TO_FILE) {
                $save_log = true;
            }
        // Process warnings errors
        } elseif ($error_type == E_WARNING || $error_type == E_USER_WARNING) {
            if ($this->LOG_WARNINGS_TO_FILE) {
                $save_log = true;
            }
        // Process notices
        } elseif ($error_type == E_NOTICE || $error_type == E_USER_NOTICE) {
            if ($this->LOG_NOTICES_TO_FILE) {
                $save_log = true;
            }
        } elseif ($error_type == E_DEPRECATED) {
            if ($this->LOG_DEPRECATED_TO_FILE) {
                $save_log = true;
            }
        }
        $IP = is_object(common()) ? common()->get_ip() : false;
        if ( ! $IP) {
            $IP = $_SERVER['REMOTE_ADDR'];
        }
        $trace = array_slice(explode(PHP_EOL, main()->trace_string()), 0, 5);
        if ($save_log) {
            $msg = json_encode([
                'time' => date('Y-m-d H:i:s'),
                'type' => $this->error_types[$error_type],
                'msg' => str_replace(["\r", PHP_EOL], '', $error_msg) . ';',
                'src' => implode(';', $trace),
                'site' => conf('SITE_ID'),
                'ip' => $IP,
                'qs' => WEB_PATH . (strlen($_SERVER['QUERY_STRING'] ?? '') ? '?' . $_SERVER['QUERY_STRING'] : ''),
                'url' => 'http://' . ( $_SERVER['HTTP_HOST'] ?? '' ) . ( $_SERVER['REQUEST_URI'] ?? '' ),
                'ref' => @$_SERVER['HTTP_REFERER'],
                'get' => $this->_log_display_array('GET'),
                'post' => $this->_log_display_array('POST'),
                'files' => $this->_log_display_array('FILES'),
                'cookie' => $this->_log_display_array('COOKIE'),
                'session' => $this->_log_display_array('SESSION'),
                'us' => ( $_SERVER['HTTP_USER_AGENT'] ?? '' ),
            ]) . PHP_EOL;

            if ( ! $this->_LOG_STARTED) {
                if ($this->_SHOW_BORDERS) {
                    $this->_do_save_log_info('START EXECUTION' . PHP_EOL, 1);
                }
                $this->_LOG_STARTED = true;
            }
            $this->_do_save_log_info($msg);
        }
        // if ($this->ERROR_MODE && ($this->ERROR_LEVEL & $error_type) && strlen($msg)) {
        if (($this->ERROR_LEVEL & $error_type) && strlen($msg)) {
            if( $this->HTML_ERRORS ) {
                $tpl   = '<b>%s</b>: <pre>%s</pre> (<i>%s on line %d</i>)<pre>%s</pre><br>' . PHP_EOL;
                $msg   = _prepare_html($error_msg);
                $trace = _prepare_html(main()->trace_string());
            } else {
                $tpl = '%s: %s (%s on line %d)'. PHP_EOL .'%s'. PHP_EOL;
                $msg   = $error_msg;
                $trace = main()->trace_string();
            }
            printf( $tpl, $this->error_types[$error_type], $msg,
                $error_file, $error_line, $trace
            );
        }
        return true;
    }

    /**
     * @param mixed $exception
     */
    public function exception_handler($exception)
    {
        $traceline = '#%s %s(%s): %s(%s)';
        $msg = "YF core errors: Uncaught exception '%s' with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in %s on line %s";

        $trace = $exception->getTrace();
        foreach ($trace as $key => $stackPoint) {
            $trace[$key]['args'] = array_map('gettype', $trace[$key]['args'] ?? []);
        }

        $result = [];
        foreach ($trace as $key => $stackPoint) {
            $result[] = sprintf(
                $traceline,
                $key,
                isset($stackPoint['file']) ? $stackPoint['file'] : '',
                isset($stackPoint['line']) ? $stackPoint['line'] : '',
                isset($stackPoint['function']) ? $stackPoint['function'] : '',
                implode(', ', isset($stackPoint['args']) ? $stackPoint['args'] : [])
            );
        }
        $result[] = '#' . ++$key . ' {main}';

        $msg = sprintf(
            $msg,
            get_class($exception),
            $exception->getMessage(),
            $exception->getFile(),
            $exception->getLine(),
            implode(PHP_EOL, $result),
            $exception->getFile(),
            $exception->getLine()
        );

        error_log($msg);

        if ($this->ERROR_MODE) {
            if( $this->HTML_ERRORS ) {
                echo '<pre>' . _prepare_html($msg) . '</pre>';
            } else {
                echo $msg;
            }
        }
    }

    /**
     * Destructor.
     */
    public function error_handler_destructor()
    {
        // Restore startup working directory
        chdir(main()->_CWD);
        // Send the endian log text if errors exists
        if ($this->_LOG_STARTED && $this->_SHOW_BORDERS) {
            $this->_do_save_log_info('END EXECUTION' . PHP_EOL, 1);
        }
    }

    /**
     * Display array.
     * @param mixed $array_name
     */
    public function _log_display_array($array_name = '')
    {
        if (empty($array_name)) {
            return '';
        }
        $A = eval('return $_' . $array_name . ';');
        if (empty($A)) {
            return '';
        }
        $output = str_replace(["\r", PHP_EOL], '', var_export($A, 1));
        $output = preg_replace('/^array \((.*?)[\,]{0,1}\)$/i', '$1', $output);
        return '_' . $array_name . '=' . $output;
    }

    /**
     * Save log info to file or stdout.
     * @param mixed $msg
     * @param mixed $add_time
     */
    public function _do_save_log_info($msg, $add_time = false)
    {
        if ($add_time) {
            $msg = date('Y-m-d H:i:s') . ' - ' . $msg;
        }
        // Save log to file
        if ($this->error_log_filename == '') {
            error_log($msg, 0);
        } else {
            $log_dir = dirname($this->error_log_filename);
            if (! file_exists($log_dir)) {
                mkdir($log_dir, 0755, true);
            }
            error_log($msg, 3, $this->error_log_filename);
        }
    }

    public function _get_file_path()
    {
        $main = main();
        $is = [
            'unit' => $main->is_unit_test(),
            'admin' => MAIN_TYPE == 'admin',
            'user' => MAIN_TYPE == 'user',
            'console' => $main->is_console(),
            'ajax' => $main->is_ajax(),
            'debug' => $main->is_debug(),
        ];
        $suffix = '';
        foreach ($is as $name => $enabled) {
            if ($enabled) {
                $suffix .= '_' . $name;
            }
        }
        $file_path = defined('ERROR_LOGS_FILE') ? constant('ERROR_LOGS_FILE') : APP_PATH . 'logs/' . $this->error_log_filename;
        $file_path = str_replace('{suffix}', $suffix, $file_path);
        $this->error_log_filename = $file_path;
        return $this->error_log_filename;
    }

    /**
     * Method that changes the filename of the generated log file.
     * @param mixed $filename
     */
    public function set_log_file_name($filename)
    {
        $dir = dirname($filename);
        if (! file_exists($dir)) {
            mkdir($dir, 0755, true);
        }
        $this->error_log_filename = $filename;
    }

    /**
     * Method that restores the error handler to the default error handler.
     */
    public function restore_handler()
    {
        restore_error_handler();
    }

    /**
     * Method that returns the error handler to error_handler().
     */
    public function return_handler()
    {
        set_error_handler([$this, 'error_handler']);
    }
}
