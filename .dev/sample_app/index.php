<?php

define('DEBUG_MODE', isset($_GET['debug']));
define('STORAGE_PATH', __DIR__.'/tests_tmp_storage/');
define('PROJECT_PATH', __DIR__.'/');
define('SITE_DEFAULT_PAGE', './?object=docs');
define('YF_PATH', dirname(dirname(__DIR__)).'/');
require YF_PATH.'classes/yf_main.class.php';
new yf_main('user', $no_db_connect = 0, $auto_init_all = 1);
