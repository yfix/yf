<?php

/**
* Abstraction layer over YF services
*/
class yf_services {

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* We cleanup object properties when cloning
	*/
	function __clone() {
		foreach ((array)get_object_vars($this) as $k => $v) {
			$this->$k = null;
		}
	}

	/**
	* Need to avoid calling render() without params
	*/
	function __toString() {
		try {
			return (string) $this->render();
		} catch (Exception $e) {
			return '';
		}
	}

	/**
	*/
	function require_php_lib($name, $params = []) {
		if (isset($this->php_libs[$name])) {
			return $this->php_libs[$name];
		}
		if (!isset($this->_paths_cache)) {
			$suffix = '.php';
			$pattern = '{,plugins/*/}{services/,share/services/}{*,*/*}'. $suffix;
			$globs = [
				'framework'	=> YF_PATH. $pattern,
				'app'		=> APP_PATH. $pattern,
			];
			$slen = strlen($suffix);
			$paths = [];
			foreach((array)$globs as $gname => $glob) {
				foreach(glob($glob, GLOB_BRACE) as $_path) {
					$_name = substr(basename($_path), 0, -$slen);
					$paths[$_name] = $_path;
				}
			}
			// This double iterating code ensures we can inherit/replace services with same name inside project
			foreach((array)$paths as $_name => $_path) {
				$this->_paths_cache[$_name] = $_path;
			}
		}
		$path = $this->_paths_cache[$name];
		if (!$path || !file_exists($path)) {
			throw new Exception('services '.__FUNCTION__.' "'.$name.'" not found');
			return false;
		}
		ob_start();
		require_once $path;
		$this->php_libs[$name] = $path;
		return ob_get_clean();
	}

	/**
	* phpmailer fresh instance, intended to use its helper methods
	*/
	function phpmailer($content, $params = []) {
		$this->require_php_lib('phpmailer');
		return new PHPMailer(true);
	}

	/**
	* Process and output JADE content
	*/
	function jade($content, $params = []) {
		$this->require_php_lib('jade');
		$dumper = new \Everzet\Jade\Dumper\PHPDumper();
		$parser = new \Everzet\Jade\Parser(new \Everzet\Jade\Lexer\Lexer());
		$jade   = new \Everzet\Jade\Jade($parser, $dumper);
		return $jade->render($content);
	}

	/**
	*/
	function sass($content, $params = []) {
		$this->require_php_lib('scss');
		$scss = new scssc();
		return $scss->compile($content);
	}

	/**
	*/
	function less($content, $params = []) {
		$this->require_php_lib('less');
		$less = new \lessc;
		return $less->compile($content);
	}

	/**
	*/
	function coffee($content, $params = []) {
		$this->require_php_lib('coffeescript');
		return \CoffeeScript\Compiler::compile($content, ['header' => false]);
	}

	/**
	* Process and output HAML content
	*/
	function haml($content, $params = []) {
		$this->require_php_lib('mthaml');
		$haml = new MtHaml\Environment('php');
		$executor = new MtHaml\Support\Php\Executor($haml, [
			'cache' => sys_get_temp_dir().'/haml',
		]);
		$path = tempnam(sys_get_temp_dir(), 'haml');
		file_put_contents($path, $content);
		return $executor->render($path, $params);
	}

	/**
	*/
	function markdown($content, $params = []) {
		$this->require_php_lib('parsedown');
		$parsedown = new Parsedown();
		return $parsedown->text($input);
	}

	/**
	*/
	function base58_encode($in) {
		$this->require_php_lib('base58');
		$base58 = new StephenHill\Base58();
		return $base58->encode($in);
	}

	/**
	*/
	function base58_decode($in) {
		$this->require_php_lib('base58');
		$base58 = new StephenHill\Base58();
		return $base58->decode($in);
	}

	/**
	*/
	function google_translate($text, $lang_from, $lang_to, $params = [], &$cache_used = false) {
		if (!strlen($text) || !$lang_from || !$lang_to) {
			return false;
		}
		$md5 = md5($lang_from.'|'.$lang_to.'|'.$text);
		$table = 'cache_google_translate';
		$cached = db()->from($table)->where('lang_from', $lang_from)->where('lang_to', $lang_to)->where('md5', $md5)->get();
		if (isset($cached['translated'])) {
			$cache_used = true;
			return $cached['translated'];
		} else {
			$this->require_php_lib('google_translate');
			try {
				$translated = Stichoza\GoogleTranslate\TranslateClient::translate($lang_from, $lang_to, $text);
			} catch (Exception $e) {
				echo 'Error: exception caught: '.$e->getMessage(). PHP_EOL;
			}
			db()->insert_safe($table, [
				'md5'			=> $md5,
				'lang_from'		=> $lang_from,
				'lang_to'		=> $lang_to,
				'source'		=> $text,
				'translated'	=> $translated,
				'date'			=> date('Y-m-d H:i:s'),
			]);
		}
		return $translated;
	}
}
