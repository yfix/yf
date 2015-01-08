<?php

$CONF['css_framework'] = 'bs3';
$CONF['DEF_BOOTSTRAP_THEME'] = 'bootstrap';
$PROJECT_CONF['tpl']['REWRITE_MODE'] = 1;
define('DEBUG_MODE', isset($_GET['debug']));
define('APP_PATH', __DIR__.'/');
define('STORAGE_PATH', APP_PATH.'tests_tmp_storage/');
define('PROJECT_PATH', APP_PATH.'public/');
define('SITE_DEFAULT_PAGE', './?object=docs');
define('YF_PATH', dirname(dirname(__DIR__)).'/');
require YF_PATH.'classes/yf_main.class.php';
new yf_main('user', $no_db_connect = 0, $auto_init_all = 1);
