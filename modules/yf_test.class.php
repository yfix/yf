<?php

class yf_test {

	/**
	*/
	function true_for_unittest () {
		return 'true';
	}
	
	/**
	*/
	function show () {
		if (!DEBUG_MODE) {
			return;
		}
		$methods = array();
		$class_name = get_class($this);
		foreach ((array)get_class_methods($class_name) as $_method_name) {
			if ($_method_name{0} == '_' || $_method_name == $class_name || $_method_name == __FUNCTION__) {
				continue;
			}
			$methods[$_method_name] = './?object='.$_GET['object'].'&action='.$_method_name;
		}
		foreach ((array)$methods as $name => $link) {
			$body[] = '<li><a href="'.$link.'">'.$name.'</a></li>';
		}
		return implode(PHP_EOL, $body);
	}

	/**
	*/
	function change_debug () {
		if (!DEBUG_MODE) {
			return;
		}
		if (!empty($_POST)) {
			$_SESSION['stpls_inline_edit']		= intval((bool)$_POST['stpl_edit']);
			$_SESSION['locale_vars_edit']		= intval((bool)$_POST['locale_edit']);
			return js_redirect($_SERVER['HTTP_REFERER'], 0);
		}
		$a = $_POST + $_SESSION;
		return form($a)
			->active_box('locale_edit', array('selected' => $_SESSION['locale_vars_edit']))
//			->active_box('stpl_edit', array('selected' => $_SESSION['stpls_inline_edit']))
			->save()
		;
	}

	/**
	*/
	function oauth () {
		$providers = _class('oauth2')->_get_providers();
		if ($_GET['id']) {
			$provider = $_GET['id'];
#			$settings = $providers($_GET['id']);
			return _class('oauth2')
				->initialize($provider)
			;
#				->process();
		}
		foreach ((array)$providers as $name => $settings) {
			if ($name[0] == '_') {
				continue;
			}
			$href = './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$name;
			$img_web_path = 'https://s3-eu-west-1.amazonaws.com/yfix/oauth/providers/'.$name.'.png';
			$body[] = '<a href="'.$href.'" class="btn">'.'<img src="'.$img_web_path.'" style="height:24px;"> '. $name.'</a>'
//				.'<br /> '.print_r($settings, 1).'<br>'
			;
		}
		return implode(PHP_EOL, $body);
	}
}
