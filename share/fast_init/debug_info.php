<?php

// Fast process debug info
return function() {
	$body .= '<hr>DEBUG INFO:'.PHP_EOL;
	$body .= '<br />exec time: <b>'. round(microtime(true) - main()->_time_start, 5).'</b> sec';
// TODO
	echo $body;
};
