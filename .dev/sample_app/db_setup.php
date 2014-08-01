<?php

define('DB_TYPE',       'mysql5');
define('DB_HOST',       'localhost');
define('DB_NAME',       'yf_for_unit_tests');
define('DB_USER',       'root');
define('DB_PSWD',       '123456');
define('DB_PREFIX',     't_');
define('DB_CHARSET',    'utf8');

// Means that we use this default connection to connect to mysql slave in production, so no data changed allowed, use master instead
define('DB_REPLICATION_SLAVE',  false);

$PROJECT_CONF['db']['RECONNECT_NUM_TRIES'] = 1;
$PROJECT_CONF['db']['FIX_DATA_SAFE'] = 1;
