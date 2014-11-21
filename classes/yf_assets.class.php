<?php

class yf_assets {

	public $content = array();
	/** @array List of pre-defined assets. See share/assets.php */
	public $assets = array();

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* $content: string/array
	* $type: = auto|asset|url|file|inline|raw
	*/
	public function add($content, $force_type = 'auto', $params = array()) {
		if (DEBUG_MODE) {
			$trace = main()->trace_string();
		}
		if (!is_array($content)) {
			$content = array($content);
		}
		if (is_array($force_type)) {
			$params = (array)$params + $force_type;
			$force_type = '';
		}
		foreach ($content as $_content) {
			$_content = trim($_content);
			if (!strlen($_content)) {
				continue;
			}
			$type = '';
			if (in_array($force_type, array('url','file','inline','raw','asset'))) {
				$type = $force_type;
			} else {
				$type = $this->_detect_content($_content);
			}
			$md5 = md5($_content);
			if ($type == 'url') {
				$this->content[$md5] = array(
					'type'	=> 'url',
					'text'	=> $_content,
					'params'=> $params,
				);
			} elseif ($type == 'file') {
				if (file_exists($_content)) {
					$text = file_get_contents($_content);
					if (strlen($text)) {
						$this->content[$md5] = array(
							'type'	=> 'file',
							'text'	=> $_content,
							'params'=> $params,
						);
					}
				}
			} elseif ($type == 'inline') {
				$this->content[$md5] = array(
					'type'	=> 'inline',
					'text'	=> $_content,
					'params'=> $params,
				);
			} elseif ($type == 'raw') {
				$this->content[$md5] = array(
					'type'	=> 'raw',
					'text'	=> $_content,
					'params'=> $params,
				);
			} elseif ($type == 'asset') {
				$info = $this->assets[$_content];
				if (is_array($info)) {
					$url = $info['url'];
					if ($info['require']) {
						$this->add($info['require'], 'asset');
					}
				} else {
					$url = $info;
				}
				$md5 = md5($url);
				$this->content[$md5] = array(
					'type'	=> 'url',
					'text'	=> $url,
					'params'=> $params,
				);
			}
			if (DEBUG_MODE) {
				if (false !== strpos(__CLASS__, '_js')) {
					$debug_name = 'core_js';
				} elseif (false !== strpos(__CLASS__, '_css')) {
					$debug_name = 'core_css';
				} else {
					$debug_name = 'assets';
				}
				debug($debug_name.'[]', array(
					'type'		=> $type,
					'md5'		=> $md5,
					'content'	=> $_content,
					'is_added'	=> isset($this->content[$md5]),
					'params'	=> $params,
					'trace'		=> $trace,
				));
			}
		}
		return $this; // Chaining
	}

	/**
	*/
	public function add_url($content, $params = array()) {
		return $this->add($content, 'url', $params);
	}

	/**
	*/
	public function add_file($content, $params = array()) {
		return $this->add($content, 'file', $params);
	}

	/**
	*/
	public function add_inline($content, $params = array()) {
		return $this->add($content, 'inline', $params);
	}

	/**
	*/
	public function add_raw($content, $params = array()) {
		return $this->add($content, 'raw', $params);
	}

	/**
	*/
	public function add_asset($content, $params = array()) {
		return $this->add($content, 'asset', $params);
	}
}
