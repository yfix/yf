<?php

if (!function_exists('html_table_to_array')) {
function html_table_to_array($html) {
	if (!preg_match_all('~<tr[^>]*>(.*?)</tr>~ims', $html, $m)) {
		return array();
	}
	$tmp_tbl = array();
	foreach ($m[1] as $v) {
		if (!preg_match_all('~<td[^>]*>(.*?)</td>~ims', $v, $m2)) {
			continue;
		}
		$val = $m2[1];
		// Get contents of within the tags, cannot be done with strip_tags
		foreach ($val as &$v1) {
			if (preg_match('~<[^>]+>([^<]+?)</[^>]+>~ims', $v1, $mm)) {
				$v1 = $mm[1];
			}
			$v1 = trim(strip_tags($v1));
			$v1 = trim(preg_replace('~&[#]?[0-9a-z]+;~ims', '', $v1));
			$v1 = trim(preg_replace('~\!.+~ims', '', $v1));
			$v1 = trim(preg_replace('~\[[^\]]+\]~ims', '', $v1));
			$v1 = trim(trim($v1, '!][#'));
		}
		$tmp_tbl[] = $val;
	}
	return $tmp_tbl;
}
}
