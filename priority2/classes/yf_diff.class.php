<?php

/**
* Differences class
*
* Get file differences
*
* @example
*	$DIFF = main()->init_class("diff", "classes/");
*	$DIFF->method = "PHP";
*	echo $DIFF->get_differences("aaa\r\n1", "aaa\r\nav");
*
*/
class yf_diff {
	
	/**
	* Shell command
	* @var string
	*/
	var $diff_command = 'diff';
	
	/**
	* Type of diff to use
	* @var int
	*/
	var $method	   = 'exec';
	
	/**
	* Differences found?
	* @var int
	*/
	var $diff_found   = 0;
	
	/**
	* Post process DIFF result?
	* @var int
	*/
	var $post_process = 1;
	
	/**
	* Constructor (PHP 4.x)
	*/
	function yf_diff () {
		return $this->__construct();
	}

	/**
	* Constructor (PHP 5.x)
	*/
	function __construct () {
		// Server?
		if ((substr(PHP_OS, 0, 3) == 'WIN') || (!function_exists('exec'))) {
//			$this->method = 'CGI';
			$this->method = 'PHP';
		}
	}

	/**
	* Wrapper function to get differences
	*
	* Produce difference output
	* //@header("Content-type: text/plain"); print_r($diff_res_array); exit();
	*
	* @return	string	Diff data
	*/
	function get_differences($str1, $str2) {
		$this->diff_found = 0;

		$str1		= $this->_diff_tag_space($str1);
		$str2		= $this->_diff_tag_space($str2);
		$str1_lines = $this->_diff_explode_string_into_words($str1);
		$str2_lines = $this->_diff_explode_string_into_words($str2);

		if ($this->method == 'CGI') {
			$diff_res   = $this->_get_cgi_diff(implode(chr(10), $str1_lines).chr(10), implode(chr(10), $str2_lines).chr(10));
		} elseif ($this->method == 'PHP') {
			$diff_res   = $this->_get_php_diff(implode(chr(10), $str1_lines).chr(10), implode(chr(10), $str2_lines).chr(10));
		} else {
			$diff_res   = $this->_get_exec_diff(implode(chr(10), $str1_lines).chr(10), implode(chr(10), $str2_lines).chr(10));
		}
		// Post process?
		if ($this->post_process) {
			if (is_array($diff_res)) {
				reset($diff_res);
				$c				= 0;
				$diff_res_array = array();
				foreach ((array)$diff_res as $bleh => $l_val)	{
					if (intval($l_val)) {
						$c = intval($l_val);
						$diff_res_array[$c]['changeInfo'] = $l_val;
					}
					if (substr($l_val,0,1) == '<') {
						$diff_res_array[$c]['old'][] = substr($l_val,2);
					}
					if (substr($l_val,0,1) == '>') {
						$diff_res_array[$c]['new'][] = substr($l_val,2);
					}
				}
				$out_str	= '';
				$clr_buffer = '';
				for ($a = -1; $a < count($str1_lines); $a++) {
					if (is_array($diff_res_array[$a+1])) {
						if (strstr($diff_res_array[$a+1]['changeInfo'], 'a')) {
							$this->diff_found = 1;
							$clr_buffer .= htmlspecialchars($str1_lines[$a]).' ';
						}
						$out_str	 .= $clr_buffer;
						$clr_buffer   = '';
						if (is_array($diff_res_array[$a+1]['old'])) {
							$this->diff_found = 1;
							$out_str.='<del style="-ips-match:1">'.htmlspecialchars(implode(' ',$diff_res_array[$a+1]['old'])).'</del> ';
						}
						
						if (is_array($diff_res_array[$a+1]['new']))
						{
							$this->diff_found = 1;
							$out_str.='<ins style="-ips-match:1">'.htmlspecialchars(implode(' ',$diff_res_array[$a+1]['new'])).'</ins> ';
						}
						$cip = explode(',',$diff_res_array[$a+1]['changeInfo']);
						if (! strcmp($cip[0], $a + 1)) {
							$new_line = intval($cip[1])-1;
							if ($new_line > $a) {
								$a = $new_line;
							}
						}
					} else {
						$clr_buffer .= htmlspecialchars($str1_lines[$a]).' ';
					}
				}
				$out_str .= $clr_buffer;
				$out_str  = str_replace('  ',chr(10),$out_str);
				$out_str  = $this->_diff_tag_space($out_str,1);
				return $out_str;
			}
		} else {
			return $diff_res;
		}
	}

	/**
	* Adds space character after HTML tags
	*
	* @return	string	Converted string
	*/
	function _diff_tag_space($str, $rev = 0) {
		if ($rev) {
			return str_replace(' &lt;','&lt;',str_replace('&gt; ','&gt;',$str));
		} else {
			return str_replace('<',' <',str_replace('>','> ',$str));
		}
	}

	/**
	* Explodes input string into words
	*
	* @return	array
	*/
	function _diff_explode_string_into_words($str) { 
		$str_array = $this->_explode_trim(chr(10), $str);
		$out_array = array();

		reset($str_array);

		foreach ((array)$str_array as $hehe => $low) {
			$all_words   = $this->_explode_trim(' ', $low, 1);
			$out_array   = array_merge($out_array, $all_words);
			$out_array[] = '';
			$out_array[] = '';
		}
		return $out_array;
	}
	
	/**
	* Explode into array and trim
	*
	* @return	array
	*/
	function _explode_trim($delim, $str, $remove_blank = 0) {
		$tmp   = explode($delim, trim($str));
		$final = array();
	
		foreach ((array)$tmp as $i) {
			if ($remove_blank && !$i) {
				continue;
			} else {
				$final[] = trim($i);
			}
		}
		return $final;
	}

	/**
	* Produce differences using PHP
	*
	* @param	string	comapre string 1
	* @param	string	comapre string 2
	* @return	string
	*/
	function _get_php_diff($str1 , $str2) {
		$str1 = explode("\n", str_replace("\r\n", "\n", $str1));
		$str2 = explode("\n", str_replace("\r\n", "\n", $str2));

		include_once YF_PATH.'libs/pear/Text/Diff.php';
		include_once YF_PATH.'libs/pear/Text/Diff/Renderer.php';
		include_once YF_PATH.'libs/pear/Text/Diff/Renderer/inline.php';
//		include_once YF_PATH.'libs/pear/Text/Diff/Renderer/context.php';
//		include_once YF_PATH.'libs/pear/Text/Diff/Renderer/unified.php';

		$diff = new Text_Diff($str1, $str2);

		$renderer = new Text_Diff_Renderer_inline();
//		$renderer = new Text_Diff_Renderer_context();
//		$renderer = new Text_Diff_Renderer_unified();
		
		$result   = $renderer->render($diff);
		
		// Inline formatting adjustments
		$result = htmlspecialchars($result);
		
		$result = str_replace("&lt;ins&gt;", '<ins style="-ips-match:1;color:green;">', $result);
		$result = str_replace("&lt;del&gt;", '<del style="-ips-match:1;color:red;">', $result);
		$result = preg_replace("#&lt;/(ins|del)&gt;#", "</\\1>", $result);
		
		// Got a match?
		if (strstr($result, 'style = "-ips-match:1"')) {
			$this->diff_found = 1;
		}

		// No post processing please
		$this->post_process = 0;

		// Convert lines to a space, and two spaces to a single line
		$result = str_replace('  ', chr(10), str_replace("\n", " ", $result));
		$result = $this->_diff_tag_space($result,1);

		return $result;
	}

	/**
	* Produce differences using unix
	*
	* @return	string
	*/
	function _get_exec_diff($str1, $str2)	{
		// Write the tmp files
		$file1 = YF_PATH.'uploads/'.time().'-1';
		$file2 = YF_PATH.'uploads/'.time().'-2';
		if ($FH1 = @fopen($file1, 'w'))	{
			@fwrite($FH1, $str1, strlen($str1));
			@fclose($FH1);
		}
		if ($FH2 = @fopen($file2, 'w'))	{
			@fwrite($FH2, $str2, strlen($str2));
			@fclose($FH2);
		}
		// Check
 		if (file_exists($file1) and file_exists($file2)) {
			exec($this->diff_command.' '.$file1.' '.$file2, $result);
			
			@unlink($file1);
			@unlink($file2);
			
			return $result;
		} else {
			return "Error, files not written to disk";
		}
	}
	
	/**
	* Produce differences using CGI
	*
	* @return	string
	*/
	function _get_cgi_diff($str1, $str2) {
		// Write the tmp files
		$file1 = 'tmp-1';
		$file2 = 'tmp-2';
		if ($FH1 = @fopen(YF_PATH.'uploads/'.$file1, 'w')) {
			@fwrite($FH1, $str1, strlen($str1));
			@fclose($FH1);
		}
		if ($FH2 = @fopen(YF_PATH.'uploads/'.$file2, 'w')) {
			@fwrite($FH2, $str2, strlen($str2));
			@fclose($FH2);
		}
		// Check
		if (file_exists(YF_PATH.'uploads/'.$file1) && file_exists(YF_PATH.'uploads/'.$file2)) {
// FIXME: need to check cgi paths
			$result = file_get_contents(YF_PATH."cgi/cgi_getdifference.cgi");

			@unlink(YF_PATH.'uploads/'.$file1);
			@unlink(YF_PATH.'uploads/'.$file2);

			return explode("\n", $result);
		} else {
			return "Error, files not written to disk";
		}
	}
}
