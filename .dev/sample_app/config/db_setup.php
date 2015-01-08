<?php

define('DB_TYPE',    'mysql5');
define('DB_HOST',    getenv('YF_DB_HOST') ?: 'localhost');
define('DB_NAME',    getenv('YF_DB_NAME') ?: 'yf_for_unit_tests');
define('DB_USER',    getenv('YF_DB_USER') ?: 'root');
define('DB_PSWD',    is_string(getenv('YF_DB_PSWD')) ? getenv('YF_DB_PSWD') : '123456');
define('DB_PREFIX',  is_string(getenv('YF_DB_PREFIX')) ? getenv('YF_DB_PREFIX') : 't_');
define('DB_CHARSET', 'utf8');

// Means that we use this default connection to connect to mysql slave in production, so no data changed allowed, use master instead
define('DB_REPLICATION_SLAVE',  false);

$PROJECT_CONF['db']['RECONNECT_NUM_TRIES'] = 1;
$PROJECT_CONF['db']['FIX_DATA_SAFE'] = 1;
