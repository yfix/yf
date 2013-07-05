#!/usr/bin/php
<?php

/**
* Source style auto-formatting HOWTO:
*
* pear upgrade
* pear install channel://pear.php.net/PHP_Beautifier-0.1.15
* php_beautifier --indent_tabs auto_refactoring_of_module2.php -o auto_refactoring_of_module3.php
*/

$module = "programs";
if ($argv[1]) {
	$module = $argv[1];
}
$modules_dir = realpath("../../modules");
$f = $modules_dir. "/". $module.".class.php"
if (file_exists($f)) {
	$code = file_get_contents($f);
} else {
	$modules_dir = realpath("../../priority2/modules");
	$f = $modules_dir. "/". $module.".class.php"
	if (file_exists($f)) {
		$code = file_get_contents($f);
	} else {
		exit('Cannot find module');
	}
}

# First we get list of good function names within module
preg_match_all("/function[\s\t]+(?P<fname>[a-z0-9_]+)[\s\t]*\((?P<fparam>.*?)\)[\s\t]*\{/ims", $code, $m);
$fnames = array();
foreach ($m["fname"] as $fname) {
	$fnames[$fname] = $fname;
}
$fparams = array();
$fparams_orig = array();
foreach ($m["fparam"] as $id => $fparam) {
	$fname = $m["fname"][$id];
	$fparam = trim($fparam);
	$fparams_orig[$fname] = $fparam;
	$tmp = array();
	if ($fparam) {
		foreach (explode(",", $fparam) as $fp) {
			$fp = trim($fp);
			list($k,$v) = explode("=", $fp);
			$k = trim($k);
			if ($k) {
				$tmp[$k] = $k;
			}
		}
	}
	$tmp = $tmp ? implode(", ", $tmp) : "";
	$fparams[$fname] = $tmp;
}
$fcodes = array();
foreach (explode("function ", $code) as $part) {
	preg_match("/(?P<fn2>[a-z0-9_]+)[\s\t]*\(/ims", ltrim($part), $m2);
	if (!$m2["fn2"]) {
		continue;
	}
	$fn2 = $m2["fn2"];
	if (!isset($fnames[$fn2])) {
		continue;
	}
	$fcodes[$fn2] = "\n\tfunction ".$part;
}
foreach ($fcodes as $fname => $fcode) {
	$sm_name = trim($module."_".$fname);
	$sm_path = $modules_dir."/".$module."/".$sm_name.".class.php";
	$sm_dir = dirname($sm_path);
	if (!file_exists($sm_dir)) {
		mkdir($sm_dir, 0777, true);
	}
	echo $sm_name." => ".$sm_path."\n";
#	if (file_exists($sm_path)) {
#		echo "exists...\n";
#		continue;
#	}
	$fcode = str_replace('$this->', 'module("'.$module.'")->', $fcode);
	file_put_contents($sm_path, "<?php\nclass ".$sm_name."{\n".$fcode."\n}");
	passthru("php -l ".$sm_path);
}

$refactored = "";
preg_match("/(?P<chead>^.+class[\s\t]+[a-z0-9_]+[\s\t]*\{.+?)function/ims", $code, $m);
$refactored = trim($m["chead"])."\n\n";
foreach ($fparams as $fname => $fp) {
	$fp_orig = $fparams_orig[$fname];
#	$refactored .= "\n\t/**\n\t*\n\t*/\n\t";
	$refactored .= "\n\tfunction ".$fname."(".$fp_orig.") {";
	$refactored .= "\n\t\treturn _class('".$module."_".$fname."', 'modules/".$module."/')->".$fname."(".$fp.");";
	$refactored .= "\n\t}\n";
}
$refactored .= "\n}\n";
file_put_contents($modules_dir."/".$module.".refactored.class.php", $refactored);
