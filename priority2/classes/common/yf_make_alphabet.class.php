<?php

/**
 * Generate alphabet for search names
 * 
 * @package		YF
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_make_alphabet {

	/**
	* Create Alphabet search criteria.Make alphabet html and query limit for selected chars
	* 
	* @example list($body, $like) = $this->make_alphabet('./?object='.__CLASS__.'&action=show', $_GET['id'], 'id');
	* 
	* @param $url	: default URL for all link
	* @param $chars : current selected first and second chars for limit
	* @param $get_var_name : variable name for $_GET requert. Default: 'id'
	* @param $q_var : name variable for query
	* 
	* @return array($html, $like) : $html - variable contain html text for show in browser,
	*								$like - variable contain where clause fo mysql query
	* @return array($html, $char1, $char2): $html - variable contain html text for show in browser,
	*										$char1 - first char
	*										$char2 - second char
	* @public
	*/
	function go($url, &$chars, $get_var_name = 'id', $q_var = '`id`') {
		$nUrl = $url.'&'.$get_var_name.'=';
		$chars = strtolower(substr($chars.'11', 0 , 2));
		$sel_style = ' style="background-color:#ccc;"';
		$html = '<a href="'.$nUrl.'1"'.(($chars[0] == '1')?$sel_style:'').'> # </a>&nbsp;';
		for ($i = 'a'; $i <= 'z'; $i = chr(ord($i)+1)) {
			$html .= '<a href="'.$nUrl.$i.'"'.(($chars[0] == $i)?$sel_style:'').'> '._ucfirst($i).' </a>&nbsp;';
		}
		$html .= '<br /><span class="small_alphabet">';
		$html .= '<a href="'.$nUrl.$chars[0].'1"'.(($chars[1] == '1')?$sel_style:'').'>'.(($chars[0] == '1')?'#':$chars[0]).'#</a> ';
		for ($i = 'a'; $i <= 'z'; $i = chr(ord($i)+1)) {
			$html .= '<a href="'.$nUrl.$chars[0].$i.'"'.(($i == $chars[1])?$sel_style:'').'>'.(($chars[0] == '1')?'#':$chars[0]).$i.'</a> ';
		}
		$html .= '</span>';
		if		($chars[0] == '1' && $chars[1] == '1')	$like = '';
		elseif	($chars[0] != '1' && $chars[1] == '1')	$like = " AND ".$q_var." LIKE '".$chars[0]."%' ";
		elseif	($chars[0] == '1' && $chars[1] != '1')	$like = " AND ".$q_var." REGEXP '^[a-zA-Z0-9\_\-\s]{1}".$chars[1].".*' ";
		else											$like = " AND ".$q_var." LIKE '".$chars[0].$chars[1]."%' ";
		return array($html, $like);
	}
}
