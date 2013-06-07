<?php
/**
 * Call preg_match_all() and convert byte offsets into (UTF-8) character offsets for PREG_OFFSET_CAPTURE flag.
 * This is regardless of whether you use /u modifier.
 *
 * @created  2008-12-11
 * @license  http://creativecommons.org/licenses/by-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  1.0.1
 */
function utf8_preg_match_all(
	/*string*/ $pattern,
	/*string*/ $subject,
	/*array*/  &$matches,
	/*int*/	$flags = PREG_PATTERN_ORDER,
	/*int*/	$char_offset = 0
)
{
	if ($char_offset)
	{
		if (! function_exists('utf8_substr')) include_once 'utf8_substr.php';
		$byte_offset = strlen(utf8_substr($subject, 0, $char_offset));
	}
	else $byte_offset = $char_offset;

	if (preg_match_all($pattern, $subject, $matches, $flags, $byte_offset) === false) return false;

	if ($flags & PREG_OFFSET_CAPTURE)
	{
		if (! function_exists('utf8_strlen')) include_once 'utf8_strlen.php';
		foreach($matches as &$match)
		{
			foreach($match as &$a) $a[1] = utf8_strlen(substr($subject, 0, $a[1]));
		}
	}

	return $return;
}
?>