<?php

/**
* Get file differences
*
* @example
*	$DIFF = _class("diff")->get_diff("aaa\n1", "aaa\nav");
*
*/
class yf_diff {

	public $diff_types = array();

	public $module_path = '';

	/*
	 *
	 */
	function __construct() {
		$this->module_path = YF_PATH.'libs/php-diff/lib/';

		$this->diff_types = array(
			'side_by_side' => 'SideBySide',
			'inline'       => 'Inline',
			'unified'      => 'Unified',
			'context'      => 'Context'
		);
	}

	/**
	 * Wrapper function to get differences
	 *
	 * @return	string	Diff data
	 */
	function get_diff($str1, $str2, $type = 'side_by_side') {
		$type = isset($this->diff_types[$type]) ? $this->diff_types[$type] : current($this->diff_types);
		
		require_once $this->module_path.'Diff.php';

		// Options for generating the diff
		$options = array(
			//'context' => 300,
			//'ignoreNewLines' => true,
			//'ignoreWhitespace' => true,
			//'ignoreCase' => true,
		);

		//Prepare content	
		$str1 = explode("\n", $str1);
		$str2 = explode("\n", $str2);

		// Initialize the diff class
		$diff = new Diff($str1, $str2, $options);
		
		require_once $this->module_path.'Diff/Renderer/Html/'.$type.'.php';
		$diff_type_class = 'Diff_Renderer_Html_'.$type;
		$renderer = new $diff_type_class;
		return $this->custom_style().$diff->Render($renderer);
	}

	function custom_style() {
		return '<style>'.PHP_EOL.
		'.Differences .ChangeReplace .Left {text-decoration: none;background-color: #f2dede;}'.PHP_EOL.
		'.Differences .ChangeReplace .Right {text-decoration: none;background-color: #dff0d8;}'.PHP_EOL.
		'.Differences ins, .Differences del {text-decoration: none;font-weight: bold;}'.PHP_EOL.
		'</style>'.PHP_EOL;
	}
}
