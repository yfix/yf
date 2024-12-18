<?php

/**
 * Custom error handler.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_core_errors
{
    /** @var bool Log errors to the error file? */
    public $LOG_ERRORS_TO_FILE = false;
    /** @var bool Log warnings to the error file? */
    public $LOG_WARNINGS_TO_FILE = false;
    /** @var bool Log notices to the error file? */
    public $LOG_NOTICES_TO_FILE = false;
    /** @var int Error reporting level */
    public $ERROR_REPORTING = 0;
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
        1 => 'E_ERROR',
        2 => 'E_WARNING',
        4 => 'E_PARSE',
        8 => 'E_NOTICE',
        16 => 'E_CORE_ERROR',
        32 => 'E_CORE_WARNING',
        64 => 'E_COMPILE_ERROR',
        128 => 'E_COMPILE_WARNING',
        256 => 'E_USER_ERROR',
        512 => 'E_USER_WARNING',
        1024 => 'E_USER_NOTICE',
        2047 => 'E_ALL',
        2048 => 'E_STRICT',
        4096 => 'E_RECOVERABLE_ERROR',
        8192 => 'E_DEPRECATED',
        16384 => 'E_USER_DEPRECATED',
    ];

    /**
     * Constructor.
     */
    public function __construct()
    {
        if (defined('ERROR_REPORTING')) {
            conf('ERROR_REPORTING', (int) constant('ERROR_REPORTING'));
        }
        if (conf('ERROR_REPORTING')) {
            error_reporting((int) conf('ERROR_REPORTING'));
        }

        $file_path = $this->_get_file_path();
        $this->set_log_file_name($file_path);

        $this->set_flags(defined('error_handler_FLAGS') ? constant('error_handler_FLAGS') : '110000');
        $this->set_reporting_level();
        ini_set('ignore_repeated_errors', 1);
        ini_set('ignore_repeated_source', 1);
        set_error_handler([$this, 'error_handler'], E_ALL);
        register_shutdown_function([$this, 'error_handler_destructor']);
        set_exception_handler([$this,  'exception_handler']);
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
     * @param mixed $exception
     */
    public function exception_handler($exception)
    {
        // these are our templates
        $traceline = '#%s %s(%s): %s(%s)';
        $msg = "PHP Fatal error:  Uncaught exception '%s' with message '%s' in %s:%s\nStack trace:\n%s\n  thrown in %s on line %s";

        // alter your trace as you please, here
        $trace = $exception->getTrace();
        foreach ($trace as $key => $stackPoint) {
            // I'm converting arguments to their type
            // (prevents passwords from ever getting logged as anything other than 'string')
            $trace[$key]['args'] = array_map('gettype', $trace[$key]['args']);
        }

        // build your tracelines
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
        // trace always ends with {main}
        $result[] = '#' . ++$key . ' {main}';

        // write tracelines into main template
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

        // log or echo as you please
        error_log($msg);

        if (DEBUG_MODE) {
            echo '<pre>' . _prepare_html($msg) . '</pre>';
        }
        _class('core_events')->fire('core.exception', $exception);
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
        // $pattern_ignore_warnings = '~^(Undefined array key|Undefined variable)~';
        // $pattern_ignore_warnings = '~^(Undefined array key|Undefined property|Undefined variable)~';
        $pattern_ignore_warnings = '~^(Undefined array key|Undefined variable|Trying to access array offset on value of type)~';
        // $pattern_ignore_warnings = '~^(Undefined array key|Undefined property|Undefined variable|Trying to access array offset on value of type)~';

        if (preg_match($pattern_ignore_warnings, $error_msg)) {
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
            if (preg_match($pattern_ignore_warnings, $error_msg)) {
                return true;
            }
            if ($this->LOG_WARNINGS_TO_FILE) {
                $save_log = true;
            }
            // Process notices
        } elseif ($error_type == E_NOTICE || $error_type == E_USER_NOTICE) {
            if ($this->LOG_NOTICES_TO_FILE) {
                $save_log = true;
            }
        } elseif ($error_type == E_DEPRECATED) {
            return true;
        }
        if (in_array($error_type, [E_USER_ERROR, E_USER_WARNING, E_WARNING])) {
            $msg = $this->error_types[$error_type] . ':' . $error_msg;
            main()->_last_core_error_msg = $msg;
            main()->_all_core_error_msgs[] = $msg;
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
                'qs' => WEB_PATH . (strlen($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''),
                'url' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                'ref' => @$_SERVER['HTTP_REFERER'],
                'get' => $this->_log_display_array('GET'),
                'post' => $this->_log_display_array('POST'),
                'files' => $this->_log_display_array('FILES'),
                'cookie' => $this->_log_display_array('COOKIE'),
                'session' => $this->_log_display_array('SESSION'),
                'us' => $_SERVER['HTTP_USER_AGENT'],
            ]) . PHP_EOL;

            // $data = [
            //     'error_level' => (int) $error_type,
            //     'error_text' => $error_msg,
            //     'source_file' => $error_file,
            //     'source_line' => (int) $error_line,
            //     'date' => time(),
            //     'site_id' => (int) conf('SITE_ID'),
            //     'user_id' => (int) ($_SESSION[MAIN_TYPE_ADMIN ? 'admin_id' : 'user_id'] ?? 0),
            //     'user_group' => (int) ($_SESSION[MAIN_TYPE_ADMIN ? 'admin_group' : 'user_group'] ?? 0),
            //     'is_admin' => MAIN_TYPE_ADMIN ? 1 : 0,
            //     'ip' => $IP,
            //     'query_string' => WEB_PATH . (strlen($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''),
            //     'user_agent' => $_SERVER['HTTP_USER_AGENT'],
            //     'referer' => @$_SERVER['HTTP_REFERER'],
            //     'request_uri' => $_SERVER['REQUEST_URI'],
            //     'object' => $_GET['object'],
            //     'action' => $_GET['action'],
            //     'trace' => implode(PHP_EOL, $trace),
            // ];

            if ( ! $this->_LOG_STARTED) {
                if ($this->_SHOW_BORDERS) {
                    $this->_do_save_log_info('START EXECUTION' . PHP_EOL, 1);
                }
                $this->_LOG_STARTED = true;
            }
            $this->_do_save_log_info($msg);
        }
        if (DEBUG_MODE && ($this->ERROR_REPORTING & $error_type) && strlen($msg)) {
            echo '<b>' . $this->error_types[$error_type] . '</b>: <pre>' . _prepare_html($error_msg) . '</pre> (<i>' . $error_file . ' on line ' . $error_line . '</i>)<pre>' . _prepare_html(main()->trace_string()) . '</pre><br />' . PHP_EOL;
        }
        _class('core_events')->fire('core.error', $data);
        // For critical errors stop execution here
        if ($error_type == E_ERROR || $error_type == E_USER_ERROR) {
            exit('Fatal error: ' . ($error_type == E_USER_ERROR ? '<br>' . _prepare_html($error_msg) : ''));
        }
        return true;
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
     * Method that changes the logging flags.
     * @param mixed $input
     */
    public function set_flags($input = [])
    {
        $this->LOG_ERRORS_TO_FILE = (bool) $input[0];
        $this->LOG_WARNINGS_TO_FILE = (bool) $input[1];
        $this->LOG_NOTICES_TO_FILE = (bool) $input[2];
    }

    /**
     * Method that changes the error reporting level.
     * @param mixed $level
     */
    public function set_reporting_level($level = false)
    {
        $this->ERROR_REPORTING = $level === false ? ini_get('error_reporting') : $level;
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

    /**
     * This will print the associative array populated by backtrace data.
     */
    public function show_backtrace()
    {
        debug_print_backtrace();
    }

    /**
     * Track user error message.
     */
    public function _prepare_env()
    {
        $this->ENV_ARRAYS = strtoupper($this->ENV_ARRAYS);
        $data = [];
        // Include only desired arrays
        if (false !== strpos($this->ENV_ARRAYS, 'G') && ! empty($_GET)) {
            $data['_GET'] = $_GET;
        }
        if (false !== strpos($this->ENV_ARRAYS, 'P') && ! empty($_GET)) {
            $data['_POST'] = $_POST;
        }
        if (false !== strpos($this->ENV_ARRAYS, 'F') && ! empty($_GET)) {
            $data['_FILES'] = $_FILES;
        }
        if (false !== strpos($this->ENV_ARRAYS, 'C') && ! empty($_GET)) {
            $data['_COOKIE'] = $_COOKIE;
        }
        if (false !== strpos($this->ENV_ARRAYS, 'S') && ! empty($_SESSION)) {
            $data['_SESSION'] = $_SESSION;
        }
        return ! empty($data) ? serialize($data) : '';
    }
}
