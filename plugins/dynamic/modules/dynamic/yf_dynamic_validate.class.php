<?php

/**
*/
class yf_dynamic_validate {

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	*/
	function __construct() {
		$this->_parent = module('dynamic');
	}

	/**
	*/
	function ajax_validate() {
		no_graphics(true);
		header('X-Robots-Tag: noindex, nofollow, noarchive, nosnippet');

		$allowed_params = $this->_parent->AJAX_VALIDATE_ALLOWED;

		$rules = [];
		$errors = [];
		if (isset($_POST['rules']) && is_array($_POST['rules'])) {
			$rules = $_POST['rules'];
		} elseif (isset($_GET['rules']) && is_array($_GET['rules'])) {
			$rules = $_GET['rules'];
		} else {
			$rules[] = [
				'func'	=> preg_replace('~[^a-z0-9_]+~ims', '', (isset($_POST['func']) ? $_POST['func'] : (isset($_GET['func']) ? $_GET['func'] : $_GET['id']))),
				'data'	=> isset($_POST['data']) ? $_POST['data'] : $_GET['data'],
				'param'	=> isset($_POST['param']) ? $_POST['param'] : $_GET['param'],
				'field'	=> isset($_POST['field']) ? $_POST['field'] : $_GET['field'],
			];
		}
		$class_validate = _class('validate');
		$is_valid = false;
		foreach ((array)$rules as $rule) {
			if (is_null($rule['data'])) {
				$errors[] = 'empty data';
			}
			if (strlen($rule['param'])) {
				$not_allowed_param = true;
				if (in_array($rule['param'], $allowed_params)) {
					$not_allowed_param = false;
				} else {
					foreach ((array)$allowed_params as $aparam) {
						// is_unique_without[user.login.1]
						if ($rule['param'] && strpos($rule['param'], $aparam.'.') === 0) {
							$not_allowed_param = false;
							break;
						}
					}
				}
				if ($not_allowed_param) {
					$errors[] = 'not allowed param';
				}
			}
			if (!preg_match('~^[a-z][a-z0-9_]+$~ims', $rule['func'])) {
				$errors[] = 'wrong func name';
			} elseif (!method_exists($class_validate, $rule['func'])) {
				$errors[] = 'no such func';
			}
			if ($errors) {
				break;
			}
			if ($rule['param'] == 'user.email') {
				$email_valid = $class_validate->valid_email($rule['data'], [], [], $error_msg);
				if (!$email_valid) {
					break;
				}
			}
			$fname = (string)$rule['func'];
			$is_valid = $class_validate->$fname($rule['data'], ['param' => $rule['param']], [], $error_msg);
			if (!$is_valid) {
				if (!$error_msg) {
					$error_msg = t('form_validate_'.$rule['func'], ['%field' => $rule['field'], '%param' => $rule['param']]);
				}
				break;
			}
		}
		if ($errors) {
			$out = ['error' => $errors];
		} else {
			if ($is_valid) {
				$out = ['ok' => 1];
			} else {
				$out = ['ko' => 1];
			}
		}
		if ($error_msg) {
			$out['error_msg'] = $error_msg;
		}
		$is_ajax = conf('IS_AJAX');
		if ($is_ajax) {
			header('Content-type: application/json');
		}
		print json_encode($out);
		if ($is_ajax) {
			exit;
		}
	}
}
