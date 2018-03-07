<?php

// Module for testing different new stuff
class yf_test {

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		$obj = _class('test_'.$name, 'modules/test/');
		if (method_exists($obj, 'test')) {
			return $obj->test($arguments);
		} else {
			trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		}
		return false;
	}

	//
	function change_debug () {
		// Save data
		if (main()->is_post()) {
			$_SESSION['stpls_inline_edit']		= intval((bool)$_POST['stpl_edit']);
			$_SESSION['locale_vars_edit']		= intval((bool)$_POST['locale_edit']);
			$_SESSION['hide_debug_console']		= intval((bool)$_POST['hide_console']);
			$_SESSION['force_gzip']				= intval((bool)$_POST['force_gzip']);
			return js_redirect($_SERVER['HTTP_REFERER'], 0);
		}
		$_trigger = [
			0 => '<b style="color:red;">Disabled</b>',
			1 => '<b style="color:green;">Enabled</b>',
		];
		// Process footer
		$replace = [
			'form_action'		=> './?object='.str_replace(YF_PREFIX, '', __CLASS__).'&action='.__FUNCTION__,
			'stpl_edit_box'		=> common()->radio_box('stpl_edit',	$_trigger, $_SESSION['stpls_inline_edit'], false, 2, $atts, false),
			'locale_edit_box'	=> common()->radio_box('locale_edit',	$_trigger, $_SESSION['locale_vars_edit'], false, 2, $atts, false),
			'hide_console_box'	=> common()->radio_box('hide_console',$_trigger, $_SESSION['hide_debug_console'], false, 2, $atts, false),
			'force_gzip_box'	=> common()->radio_box('force_gzip',	$_trigger, $_SESSION['force_gzip'], false, 2, $atts, false),
			'back_url'			=> WEB_PATH.'?object='.$_GET['object'].($_GET['action'] != 'show' ? '&action='.$_GET['action'] : ''). (!empty($_GET['id']) ? '&id='.$_GET['id'] : ''). (!empty($_GET['page']) ? '&page='.$_GET['page'] : ''),
		];
		return tpl()->parse(__CLASS__.'/'.__FUNCTION__, $replace);
	}

	// Default function
	function show () {
		if(!DEBUG_MODE){
			return;
		}

		$methods = [];
		$class_name = get_class($this);
		foreach ((array)get_class_methods($class_name) as $_method_name) {
			// Skip unwanted methods
			if ($_method_name{0} == '_' || $_method_name == $class_name || $_method_name == __FUNCTION__) {
				continue;
			}
			$methods[] = [
				'link'	=> './?object='.$_GET['object'].'&action='.$_method_name,
				'name'	=> $_method_name,
			];
		}
		$this->_avail_methods = $methods;
		// Process template
		$replace = [
			'methods'	=> $methods,
		];
		return tpl()->parse(__CLASS__.'/main', $replace);
	}

	//
	function ajax_login () {
		return tpl()->parse(__CLASS__.'/test_ajax_login', $replace);
	}

	//
	function poll () {
		$POLL_OBJ = main()->init_class('poll');
		$body .= $POLL_OBJ->show([
			'object_name'	=> 'forum',
			'object_id'		=> 1,
		]);
		$body .= '<br />'.PHP_EOL;
		$body .= $POLL_OBJ->view([
			'object_name'	=> 'forum',
			'object_id'		=> 1,
		]);
		return $body;
	}

	// Test current project mailing system
	function mail () {
		// Only for members!
		if (!main()->USER_ID && !$_SESSION['admin_id']) {
			return 'Access denied. Only for members!';
		}
		if ($_POST['email']) {
			$msg = 'YF_Framework: Testing '.$_SERVER['HTTP_HOST'].' mailer';
			$result = common()->quick_send_mail($_POST['email'], $msg, $msg. PHP_EOL. 'Message part here');
			return 'Result: '.$result ? '<b style="color:green;">success</b>' : '<b style="color:green;">failed</b>';
		}
		// Display form
		return '<form action="./?object='.$_GET['object'].'&action='.$_GET['action'].'" method="post"><input type="text" name="email"><input type="submit" name="go" value="SEND!"></form>';
	}

	// test PHPMailer
	function smtp_phpmailer () {
		return _class('test_'.__FUNCTION__, 'modules/test/')->test();
	}

	//
	function send_mail () {
		return common()->quick_send_mail('yfix.dev@gmail.com', 'test subject', 'blablabla');
	}

	/**
	* PHPAmiler testing
	*/
	function phpmailer () {
		require_php_lib('phpmailer');
		$mail			 = new PHPMailer(); // defaults to using php 'mail()'
		$body			 = file_get_contents(INCLUDE_PATH. 'uploads/mail_test.html');
		$mail->SetFrom('yuri.vysotskiy@gmail.com', 'YFix Team');
		$address = 'yuri.vysotskiy@gmail.com';
		$mail->AddAddress($address, 'yuri.vysotskiy');
		$mail->Subject	= 'PHPMailer Test Subject via mail(), basic';
		$mail->AltBody	= 'To view the message, please use an HTML compatible email viewer!'; // optional, comment out and test
		$mail->MsgHTML($body);
		$mail->IsHTML(true);
		if(!$mail->Send()) {
			return 'Mailer Error: ' . $mail->ErrorInfo;
		} else {
			return 'Message sent!';
		}
	}

	//
	function rate () {
		$body .= '<img src="'.WEB_PATH.'uploads/gallery/medium/000/000/001/1_260512.jpg" /><br />';
		$body .= module('rate')->_show_for_object([
			'object_name'	=> 'gallery_photo',
			'object_id'		=> 260512,
		]);
		return $body;
	}

	//
	function photo_rating () {
		return _class('photo_rating')->_show_photo();
	}

	//
	function diff () {
		return _class('test_'.__FUNCTION__, 'modules/test/')->test();
	}

	//
	function geo_country () {
		// UKRTELECOM ISP, Maxmind GeoCity does not recognize it,
		// but it seems that Maxmind GeoCountry can get at least country
		$cur_ip = '92.113.3.128';
		$ip_data = common()->_get_geo_data_from_db($cur_ip);
		$body = 'IP: '.$cur_ip.'<br /><br />';
		$body .= 'GEO DATA:<br /> <pre>'.($ip_data ? print_r($ip_data, 1) : 'Unknown... :-(').'</pre>';
		return $body;
	}

	// unicode functions
	function unicode () {
		return _class('test_'.__FUNCTION__, 'modules/test/')->test();
	}

	//
	function email_verify () {
		if (empty($_GET['id'])) {
			return 'Please specify email as $_GET["id"]';
		}
		$_time_start = microtime(true);

		$GLOBALS['_email_verify_debug'] = true;

		$result = common()->email_verify($_GET['id'], 1, 1, 1);

		$body .= '<b>'.$_GET['id'].'</b> seems to be: '.($result ? '<b style="color:green;">OK</b>' : '<b style="color:red;">WRONG</b>');
		$body .= '<br />Spent time: <b>'.common()->_format_time_value(microtime(true) - (float)$_time_start).' secs</b><br />';

		$body .= $GLOBALS['_email_verify_output'];
		return $body;
	}

	//
	function synonym () {
		// NOTE: encoding = utf8
		$source = '{сегодня|вчера|20 лет назад} [test|||test1] [te|s|t3|test4]#3# [te|s|t|5[te|s|t|5]#1,4#|t|e|s|t6]#6,4# {Жак Ив Кусто|Леонид Хрущев{ и его колеги|, а также родственники жены| в компании инопланетян}} {сообщил|утонул|занимался сексом c{ {Хилари Клинтон|Перис Хилтон|Надеждой Крупской|Мерлином Менсоном}}} {на %DEMO% Елисейских полях|на полях|в подезде|в ванной|в ванной с утятами|на унитазе|пьяным|%DEMO%|%DEMO%|}';
		if (main()->is_post()) {
			$source = $_POST['source'];
			$OBJ = _class('synonymizer');
			$result .= '1) '. $OBJ->process($source). PHP_EOL;
			$result .= '2) '. $OBJ->process($source). PHP_EOL;
			$result .= '3) '. $OBJ->process($source). PHP_EOL;
		}
		$replace = [
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'],
			'source'		=> _prepare_html($source),
			'result'		=> _prepare_html($result),
		];
		return tpl()->parse(__CLASS__.'/'.__FUNCTION__, $replace);
	}

	//
	function image_resize () {
		$body .= '<h3>Testing image upload and resize</h3>';
		$img_src = 'uploads/tmp/test_resized_image.jpg';
		if (file_exists(INCLUDE_PATH. $img_src)) {
			unlink(INCLUDE_PATH. $img_src);
		}
		// Do upload and resize to 500 x 500 px
		if (main()->is_post()) {
			$img_dir = INCLUDE_PATH. dirname($img_src);
			_mkdir_m($img_dir);
			$upload_result	= common()->upload_image(INCLUDE_PATH. $img_src);
			$body .= "<b>UPLOAD:</b> ".($upload_result ? "<b style='color:green;'>success</b>" : "<b style='color:red;'>failed</b>")."<br />";
			if ($upload_result) {
				$thumb_result	= common()->make_thumb(INCLUDE_PATH. $img_src, INCLUDE_PATH. $img_src, 500, 500);
			}
			$body .= "<b>MAKE_THUMB:</b> ".($thumb_result ? "<b style='color:green;'>success</b>" : "<b style='color:red;'>failed</b>")."<br />";
			if (file_exists(INCLUDE_PATH. $img_src)) {
				$body .= "<img src='".WEB_PATH. $img_src."'><br /><br />";
			}
		}
		$body .= _e();
		$body .= "<form action='./?object=".$_GET["object"]."&action=".$_GET["action"]."' method='post' enctype='multipart/form-data'><input type='file' name='image'><input type='submit' name='go' value='GO'></form>";
		return $body;
	}

	//
	function spoiler (){
		$head_text='Cool spoiler head';
		$body_text='Here is a cool spoiler body text<br />';
/*		'We can also put some spoilers into each other. Like this...'
		.$this->_spoiler($head_text, $body_text);
*/
		$replace = [
			'head_text' => $head_text,
			'body_text' => $body_text,
		];
		return tpl()->parse($_GET['object'].'/'.__FUNCTION__, $replace);
	}

	//
	function redirect (){
		return js_redirect('./?object='.$_GET['object'], true, 'Testing redirect', 3);
	}

	//
	function filter_text (){
		// Do process
		if (main()->is_post()) {
			$BB_CODES_OBJ = _class('bb_codes');
			if (is_object($BB_CODES_OBJ)) {
				$result = $_POST['text'];
				$result = _filter_text($result);
				$res2	= $result;
				$result = $BB_CODES_OBJ->_force_close_bb_codes($result);
				$result = $BB_CODES_OBJ->_process_text($result);
				$result .= '<br /><br /><br />'.$res2;
			}
		}
		$replace = [
			'form_action'	=> './?object='.$_GET['object'].'&action='.$_GET['action'],
			'result'		=> $result,
			'source'		=> $_POST['text'] ? $_POST['text'] : '[COLOR=green][U][SIZE=7][B][I]пользовательдолженпользовательдолжен[/I][/B][/SIZE][/U][/COLOR]http://www.gooooooooooooooooooooooooggle.com',
		];
		return tpl()->parse($_GET['object'].'/'.__FUNCTION__, $replace);
	}

	//
	function notice () {
		if (main()->is_post()) {
			common()->set_notice($_POST['notice']);
			return js_redirect($_SERVER['HTTP_REFERER']);
		}
		$body .= common()->show_notices().'<br />';
		$body .= "<form action='./?object=".$_GET["object"]."&action=".$_GET["action"]."' method='post'>Set notice here:<br /><textarea name='notice'>Test notice text</textarea><br /><input type='submit'></form>";
		return $body;
	}

	//
	function lang () {
		$OBJ = main()->init_class('dynamic');
		return $OBJ->_change_lang_form();
	}

	/**
	* Testing SSH wrapper
	*/
	function ssh () {
		return _class('test_'.__FUNCTION__, 'modules/test/')->test();
	}

	/**
	*/
	function bb_code () {
		return _class('test_'.__FUNCTION__, 'modules/test/')->test();
	}

	/**
	*/
	function text_typos () {
		return _class('test_'.__FUNCTION__, 'modules/test/')->test();
	}

	//
	function threaded_exec () {
		if (MAIN_TYPE_USER || !$_SESSION['admin_id']) {
			exit('Only for admin');
		}
		for ($i = 1; $i <= 9; $i++) {
			$host = 'nginx'.$i.'.inffinity-internet.com';
			$threads[$host] = ['func' => 'gethostbyname', 'name' => $host];
		}

		echo "<br />\nNon-Threaded result: <br />\n<br />\n";
		$time_start = microtime(true);

		foreach ((array)$threads as $k => $v) {
			$results[$k] = gethostbyname($k);
		}

		print_R($results);
		echo "\n<br /><br />exec time: ". (microtime(true) - $time_start)." sec\n";

		echo "<br />\nThreaded result: <br />\n<br />\n";
		$time_start = microtime(true);

		$results = common()->threaded_exec("dynamic", "php_func", $threads);

		print_R($results);
		echo "\n<br /><br />exec time: ". (microtime(true) - $time_start)." sec\n";

		exit();
	}

	/**
	*/
	function true_for_unittest () {
		return 'true';
	}

	/**
	* form2 reference examples
	*/
	function form2 () {
		return _class('test_'.__FUNCTION__, 'modules/test/')->test();
	}
}
