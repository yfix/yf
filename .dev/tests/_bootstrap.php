<?php
// This is global bootstrap for autoloading 
exec('if [ -z "$(netstat -anlpt | grep 33380)" ]; then ((../sample_app/run_server.sh >/dev/null 2>&1) &) ; fi');
