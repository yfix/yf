<?php

#error_reporting(E_ALL ^ E_NOTICE ^ E_DEPRECATED ^ E_STRICT);

// This is global bootstrap for autoloading 
exec('../sample_app/run_server_in_bg.sh');

\Codeception\Util\Autoload::registerSuffix('Page', __DIR__.DIRECTORY_SEPARATOR.'_pages');