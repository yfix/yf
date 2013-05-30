<?php

define("NETPBM_PATH",	"d:\\www\\GnuWin32\\bin\\");

$data = exec(NETPBM_PATH."jpegtopnm image_that_crashes_gd.jpg | ".NETPBM_PATH."pnmfile.exe");
echo $data."<br />\r\n";

preg_match("/([0-9]+?) by ([0-9]+)/i", $data, $m);

print_r($m);

$width	= intval($m[1]);
$height	= intval($m[2]);

//echo "";

?>