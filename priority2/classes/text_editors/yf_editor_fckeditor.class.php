<?php

/**
* HTML content editor (using FCK Editor)
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_editor_fckeditor {

	/** @var string */
	var $text_field_name	= "text_to_edit";
	/** @var string */
	var $BasePath			= "";
	/** @var string */
	var $Width				= '95%';
	/** @var string */
	var $Height				= '400';
	/** @var string */
//	var $ToolbarSet			= 'Default';
	var $ToolbarSet			= 'yfnet';
	/** @var string @conf_skip */
	var $value				= '';
	/** @var array */
	var $Config				= array(
		"DefaultLanguage"	=> "en",
		"ImageDlgHideLink"	=> 1,
		"ImageUpload"		=> 0,
	);

	/**
	* Framework constructor
	*/
	function _init() {
		$this->BasePath		= WEB_PATH."fckeditor/";
		$this->Config		= array(
			"DefaultLanguage"			=> conf('language') ? conf('language') : "en",
	//		"CustomConfigurationsPath"	=> $this->BasePath. "editor/plugins/bbcode/_sample/sample.config.js",
		);
	}

	/**
	* Check existance
	*/
	function _check_if_editor_exists() {
		$fs_path = INCLUDE_PATH."fckeditor/editor/fckeditor.html";
		return file_exists($fs_path) && filesize($fs_path) > 2 ? 1 : 0;
	}

	/**
	* Helper method
	*/
	function _set_config ($new_config = array()) {
		if (empty($new_config)) {
			return false;
		}
		
		if($new_config["CustomConfigurationsPath"] != ""){
			$new_config["CustomConfigurationsPath"] = $this->BasePath.$new_config["CustomConfigurationsPath"];
		}

		$this->Config = array_merge((array)$this->Config, (array)$new_config);
	}

	/**
	* Do create editor code
	*/
	function _create_code($text_to_edit = "") {
		setcookie("TRY_WEB_PATH", WEB_PATH);
		// Override text to edit (if exists one)
		if (!empty($text_to_edit)) {
			$this->value = $text_to_edit;
		}
		// Set default text field name
		$Htmlvalue = htmlspecialchars($this->value);
//		$Htmlvalue = _prepare_html($this->value, 0);
		$Html = '<div>';
		if ($this->_IsCompatible()) {
			$Link = $this->BasePath."editor/fckeditor.html?InstanceName=".$this->text_field_name;
			if ($this->ToolbarSet != '') {
				$Link .= "&amp;Toolbar=".$this->ToolbarSet;
			}
			// Render the linked hidden field.
			$Html .= "<input type='hidden' id='".$this->text_field_name."' name='".$this->text_field_name."' value=\"".$Htmlvalue."\" style=\"display:none\" />";
			// Render the configurations hidden field.
			$Html .= "<input type='hidden' id='".$this->text_field_name."___Config' value=\"".$this->_GetConfigFieldString()."\" style=\"display:none\" />";
			// Render the editor IFRAME.
			$Html .= "<iframe id='".$this->text_field_name."___Frame' src='".$Link."' width='".$this->Width."' height='".$this->Height."' frameborder='no' scrolling='no'></iframe>";
		} else {
			if (strpos($this->Width, '%') === false) {
				$WidthCSS = $this->Width.'px';
			} else {
				$WidthCSS = $this->Width;
			}
			if (strpos($this->Height, '%') === false) {
				$HeightCSS = $this->Height.'px';
			} else {
				$HeightCSS = $this->Height;
			}
			$Html .= "<textarea name='".$this->text_field_name."' id='".$this->text_field_name."' rows='4' cols='40' style='width: ".$WidthCSS."; height: ".$HeightCSS."' wrap='virtual'>".$Htmlvalue."</textarea>";
		}
		$Html .= '</div>';
		return $Html;
	}

	/**
	* Fast check if current browser is compatible with FCK editor
	*/
	function _IsCompatible() {
		global $HTTP_USER_AGENT ;

		if ( !isset( $_SERVER ) ) {
			global $HTTP_SERVER_VARS ;
			$_SERVER = $HTTP_SERVER_VARS ;
		}
		
		if ( isset( $HTTP_USER_AGENT ) )
			$sAgent = $HTTP_USER_AGENT ;
		else
			$sAgent = $_SERVER['HTTP_USER_AGENT'] ;

		if ( strpos($sAgent, 'MSIE') !== false && strpos($sAgent, 'mac') === false && strpos($sAgent, 'Opera') === false )
		{
			$iVersion = (float)substr($sAgent, strpos($sAgent, 'MSIE') + 5, 3) ;
			return ($iVersion >= 5.5) ;
		}
		else if ( strpos($sAgent, 'Gecko/') !== false )
		{
			$iVersion = (int)substr($sAgent, strpos($sAgent, 'Gecko/') + 6, 8) ;
			return ($iVersion >= 20030210) ;
		}
		else if ( strpos($sAgent, 'Opera/') !== false )
		{
			$fVersion = (float)substr($sAgent, strpos($sAgent, 'Opera/') + 6, 4) ;
			return ($fVersion >= 9.5) ;
		}
		else if ( preg_match( "|AppleWebKit/(\d+)|i", $sAgent, $matches ) )
		{
			$iVersion = $matches[1] ;
			return ( $matches[1] >= 522 ) ;
		}
		else
			return false ;
	}

	/**
	* Prepare config string
	*/
	function _GetConfigFieldString() {
		$sParams	= '';
		$bFirst		= true;
		foreach ((array)$this->Config as $sKey => $sValue) {
			if ($bFirst == false) {
				$sParams .= '&amp;';
			} else {
				$bFirst = false;
			}
			if ($sValue === true) {
				$sParams .= $this->_EncodeConfig($sKey).'=true';
			} elseif ($sValue === false) {
				$sParams .= $this->_EncodeConfig($sKey).'=false';
			} else {
				$sParams .= $this->_EncodeConfig($sKey).'='.$this->_EncodeConfig($sValue);
			}
		}
		return $sParams;
	}

	/**
	* Do encode config string for editor
	*/
	function _EncodeConfig($valueToEncode) {
		$chars = array(
			'&' => '%26',
			'=' => '%3D',
			'"' => '%22'
		);
		return strtr($valueToEncode, $chars);
	}
}
