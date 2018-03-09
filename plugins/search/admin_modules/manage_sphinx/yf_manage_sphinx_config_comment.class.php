<?php

/**
* sphinx_config_comment
* Represents a comment inside the sphinx configuration
*/
class yf_manage_sphinx_config_comment {

	public $exact_string;

	/**
	* Create a new comment
	*
	* @param	string	$exact_string	The content of the comment including newlines, leading whitespace, etc.
	*/
	function __construct($exact_string)
	{
		$this->exact_string = $exact_string;
	}

	/**
	* Simply returns the comment as it was created
	*
	* @return	string	The exact string that was specified in the constructor
	*/
	function to_string()
	{
		return $this->exact_string;
	}
}