<?php

define('DB_TYPE',       'mysqli');
define('DB_HOST',       getenv('YF_DB_HOST') ?: '127.0.0.1');
define('DB_NAME',       getenv('YF_DB_NAME') ?: 'yf_for_unit_tests');
define('DB_USER',       getenv('YF_DB_USER') ?: 'root');
define('DB_PSWD',       is_string(getenv('YF_DB_PSWD')) ? getenv('YF_DB_PSWD') : '123456');
define('DB_PREFIX',     is_string(getenv('YF_DB_PREFIX')) ? getenv('YF_DB_PREFIX') : 't_');
define('DB_CHARSET',    'utf8');
// Means that we use this default connection to connect to mysql slave in production, so no data changed allowed, use master instead
define('DB_REPLICATION_SLAVE',  false);

define('DB_HOST_SLAVE',     'localhost');
define('DB_NAME_SLAVE',     DB_NAME);
define('DB_USER_SLAVE',     DB_USER);
define('DB_PSWD_SLAVE',     DB_PSWD);
define('DB_PREFIX_SLAVE',   DB_PREFIX);
define('DB_CHARSET_SLAVE',  DB_CHARSET);

define('DB_HOST_MASTER',    'central.yf.yfix.net');
define('DB_NAME_MASTER',    DB_NAME);
define('DB_USER_MASTER',    DB_USER);
define('DB_PSWD_MASTER',    DB_PSWD);
define('DB_PREFIX_MASTER',  DB_PREFIX);
define('DB_CHARSET_MASTER', DB_CHARSET);

$PROJECT_CONF['db']['RECONNECT_NUM_TRIES'] = 0;
$PROJECT_CONF['db']['FIX_DATA_SAFE'] = 0;
