<?php

define('DB_TYPE',       'mysql41');
define('DB_HOST',       'localhost');
define('DB_NAME',       'yft3');
define('DB_USER',       'root');
define('DB_PSWD',       '123456');
define('DB_PREFIX',     't_');
define('DB_CHARSET',    'utf8');
// Means that we use this default connection to connect to mysql slave in production, so no data changed allowed, use master instead
define('DB_REPLICATION_SLAVE',  false);

define('DB_HOST_PF',    'central.t3.yfix.net');
define('DB_NAME_PF',    'pf_admin');
define('DB_USER_PF',    DB_USER);
define('DB_PSWD_PF',    DB_PSWD);
define('DB_PREFIX_PF',  'pf_');
define('DB_CHARSET_PF', DB_CHARSET);

define('DB_HOST_RR',    'regreader.t3.yfix.net');
define('DB_NAME_RR',    'regreader2');
define('DB_USER_RR',    DB_USER);
define('DB_PSWD_RR',    DB_PSWD);
define('DB_PREFIX_RR',  '');
define('DB_CHARSET_RR', DB_CHARSET);

define('DB_HOST_CR',    'central.t3.yfix.net');
define('DB_NAME_CR',    'crawler_panel');
define('DB_USER_CR',    DB_USER);
define('DB_PSWD_CR',    DB_PSWD);
define('DB_PREFIX_CR',  'c_');
define('DB_CHARSET_CR', DB_CHARSET);

define('DB_HOST_SLAVE',     'localhost');
define('DB_NAME_SLAVE',     DB_NAME);
define('DB_USER_SLAVE',     DB_USER);
define('DB_PSWD_SLAVE',     DB_PSWD);
define('DB_PREFIX_SLAVE',   DB_PREFIX);
define('DB_CHARSET_SLAVE',  DB_CHARSET);

define('DB_HOST_MASTER',    'central.t3.yfix.net');
define('DB_NAME_MASTER',    DB_NAME);
define('DB_USER_MASTER',    DB_USER);
define('DB_PSWD_MASTER',    DB_PSWD);
define('DB_PREFIX_MASTER',  DB_PREFIX);
define('DB_CHARSET_MASTER', DB_CHARSET);
