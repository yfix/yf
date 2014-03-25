<?php

class yf_docs_generator {

	/**
	*/
	function parse_file($f) {
		$s = file_get_contents($f);
		//$regex = '~function[\s\t]+(?P<name>[a-z][a-z0-9_]+)[\s\t]*\((?P<args>[^\{]+)[\s\t]*\)[\s\t]*\{~ims';
		//preg_match_all($regex, $s, $m);
		$all_tokens = token_get_all($s);
		$s_lines = explode(PHP_EOL, $s);
		return self::find_all_funcs($all_tokens, $s_lines);
	}

	/**
	*/
	function find_all_funcs(&$all_tokens, &$s_lines) {
		$funcs = array();
		foreach ($all_tokens as $id => $t) {
			$token = $t[0];
			$line = $t[2];
			if (!in_array($token, array(T_FUNCTION))) {
				continue;
			}
			$name = self::find_func_name($id, $all_tokens);
			if (substr($name,0,1) == '_') {
				continue;
			}
			$args = self::find_func_args($id, $all_tokens);
			$doc = self::find_func_desc($id, $all_tokens);
			$funcs[$name] = array(
#				'line'	=> $s_lines[$line - 1],
				'func'	=> $name,
				'args'	=> $args,
				'doc'	=> self::cleanup_doc_comment($doc),
			);
		}
		if ($funcs) {
			asort($funcs);
		}
		return $funcs;
	}

	/**
	*/
	function find_func_desc($id, &$all_tokens) {
		$desc = '';
		foreach(array_reverse(array_slice($all_tokens, $id < 20 ? 0 : $id - 20, 20, true), true) as $k => $t) {
			if ($t[0] == T_DOC_COMMENT) {
				$desc = $t[1];
				break;
			}
		}
		return $desc;
	}

	/**
	*/
	function find_func_name($id, &$all_tokens) {
		$name = '';
		foreach(array_slice($all_tokens, $id, 50, true) as $k => $t) {
			if ($t[0] == T_STRING) {
				$name = $t[1];
				break;
			}
		}
		return $name;
	}

	/**
	*/
	function find_func_args($id, &$all_tokens) {
		$args = array();
		foreach(array_slice($all_tokens, $id + 2, 50, true) as $k => $t) {
			if ($t[0] == '{') {
				break;
			}
			if (!is_string($t[0])) {
				if ($t[0] === T_VARIABLE) {
					$args[] = $t[1];
				}
			}
		}
		return $args;
	}

	/**
	*/
	function cleanup_doc_comment($str = '') {
		$str = trim(trim(trim($str),'*/'));
		if (!strlen($str)) {
			return '';
		}
		$tmp = array();
		foreach (explode(PHP_EOL, $str) as $line) {
			$line = trim(ltrim(trim($line), '*'));
			if (strlen($line)) {
				$tmp[] = $line;
			}
		}
		return implode(PHP_EOL, $tmp);
	}
}
