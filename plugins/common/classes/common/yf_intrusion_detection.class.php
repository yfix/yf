<?php

/**
 * Intrusion detection methods here.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_intrusion_detection
{
    /**
     * Contructor.
     */
    public function _init()
    {
        // TODO: check and enable
        return false;
        require_php_lib('phpids');

        $this->config = [
            // basic settings - customize to make the PHPIDS work at all
            'General' => [
                'filter_type' => 'xml',
//				"base_path"			=> $this->BASE_PATH,
                'use_base_path' => false,
//				"filter_path"		=> $this->BASE_PATH."IDS/default_filter.xml",
//				"tmp_path"			=> INCLUDE_PATH. "uploads/tmp/",
                'scan_keys' => false,
                // in case you want to use a different HTMLPurifier source, specify it here
                // By default, those files are used that are being shipped with PHPIDS
//				//"HTML_Purifier_Path"	=> $this->BASE_PATH."IDS/vendors/htmlpurifier/HTMLPurifier.auto.php",
//				//"HTML_Purifier_Cache"	=> $this->BASE_PATH."IDS/vendors/htmlpurifier/HTMLPurifier/DefinitionCache/Serializer",
                // define which fields contain html and need preparation before
                // hitting the PHPIDS rules (new in PHPIDS 0.5)
                'html' => [
                    '__wysiwyg',
                ],
                // define which fields contain JSON data and should be treated as such
                // for fewer false positives (new in PHPIDS 0.5.3)
                'json' => [
                    '__jsondata',
                ],
                // define which fields shouldn't be monitored (a[b]=c should be referenced via a.b)
                'exceptions' => [
                    '__utmz',
                    '__utmc',
                ],
            ],
            // If you use the PHPIDS logger you can define specific configuration here
            'Logging' => [
                // file logging
//				"path"			=> INCLUDE_PATH. "phpids_log.txt",
                // email logging
                // note that enabling safemode you can prevent spam attempts,
                // see documentation
                //"recipients"	=> array(
                //	"test@test.com.invalid",
                //),
                //"subject"		=> "PHPIDS detected an intrusion attempt!",
                //"header"		=> "From: <PHPIDS> info@php-ids.org",
                //"envelope"		=> "",
                //"safemode"		=> true,
                //"allowed_rate"	=> 15,
                // database logging
// CHANGE_ME
                //"wrapper"		=> "mysql:host=".DB_HOST.";port=3306;dbname=".DB_NAME,
                //"user"			=> DB_USER,
                //"password"		=> DB_PSWD,
// TODO
                //"table"			=> db('intrusions'),
                // If you would like to use other methods than file caching you can configure them here
            ],
            'Caching' => [
                // caching:	  session|file|database|memcached|none
                'caching' => 'none',
                //"expiration_time" => 600,
                // file cache
//				//"path"			=> INCLUDE_PATH. "uploads/tmp/default_filter.cache",
                // database cache
                //"wrapper"		=> "mysql:host=".DB_HOST.";port=3306;dbname=".DB_NAME,
                //"user"		=> DB_USER
                //"password"	=> DB_PSWD,
                //"table"		=> db('phpids_cache'),
                // memcached
                //"host"		=> "localhost",
                //"port"		=> "11211",
                //"key_prefix"	=> "PHPIDS",
                //"tmp_path"	=> "tmp/memcache.timestamp",
            ],
        ];
        _mkdir_m($this->config['General']['tmp_path']);
    }

    /**
     * Do check.
     */
    public function check()
    {
        $request = [
            'REQUEST' => $_REQUEST,
            'GET' => $_GET,
            'POST' => $_POST,
            'COOKIE' => $_COOKIE,
        ];
        $init = IDS_Init::init();
        $init->setConfig($this->config, true);
        $ids = new IDS_Monitor($request, $init);
        $result = $ids->run();
        if ( ! $result->isEmpty()) {
            // Take a look at the result object
            //			echo $result;
            trigger_error('IDS: Possible intrusion detected, result: ' . $result, E_USER_WARNING);
        }
        return false;
    }
}
