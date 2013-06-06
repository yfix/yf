<?php

//-----------------------------------------------------------------------------
// Module for testing different new stuff
class yf_test {

	/** @conf_skip */
	var $SMTP_OPTIONS = array(
		'smtp_host'		=> 'smtp.gmail.com',
		'smtp_port'		=> '465',
		'smtp_user_name'=> 'test@test.com',
		'smtp_password'	=> 'test password',
		'smtp_from'		=> 'test@test.com',
		'smtp_secure'	=> 'tls',
	);
	/** @conf_skip */
	var $TEST_MAIL = array(
		"email_to"		=> "yfix.dev@gmail.com",
		"name_to"		=> "test receiver",
		"email_from"	=> "of@of.com",
		"name_from"		=> "test from",
		"subject"		=> "blabla test blabla",
		"text"			=> "!!text text!!",
		"html"			=> "<h1>!!html html!!</h1>",
	);

	//-----------------------------------------------------------------------------
	// Profy module constructor
	function _init () {
	}

	//-----------------------------------------------------------------------------
	// 
	function change_debug () {
		// Save data
		if (!empty($_POST)) {
			$_SESSION['stpls_inline_edit']		= intval((bool)$_POST["stpl_edit"]);
			$_SESSION['locale_vars_edit']		= intval((bool)$_POST["locale_edit"]);
			$_SESSION['hide_debug_console']		= intval((bool)$_POST["hide_console"]);
			$_SESSION['force_gzip']				= intval((bool)$_POST["force_gzip"]);
			return js_redirect($_SERVER["HTTP_REFERER"], 0);
		}
		$_trigger = array(
			0 => "<b style='color:red;'>Disabled</b>",
			1 => "<b style='color:green;'>Enabled</b>",
		);
		// Process footer
		$replace = array(
			"form_action"		=> "./?object=".str_replace(PF_PREFIX, "", __CLASS__)."&action=".__FUNCTION__,
			"stpl_edit_box"		=> common()->radio_box("stpl_edit",	$_trigger, $_SESSION['stpls_inline_edit'], false, 2, $atts, false),
			"locale_edit_box"	=> common()->radio_box("locale_edit",	$_trigger, $_SESSION['locale_vars_edit'], false, 2, $atts, false),
			"hide_console_box"	=> common()->radio_box("hide_console",$_trigger, $_SESSION['hide_debug_console'], false, 2, $atts, false),
			"force_gzip_box"	=> common()->radio_box("force_gzip",	$_trigger, $_SESSION['force_gzip'], false, 2, $atts, false),
			"back_url"			=> WEB_PATH."?object=".$_GET["object"].($_GET["action"] != "show" ? "&action=".$_GET["action"] : ""). (!empty($_GET["id"]) ? "&id=".$_GET["id"] : ""). (!empty($_GET["page"]) ? "&page=".$_GET["page"] : ""),
		);
		return tpl()->parse(__CLASS__."/".__FUNCTION__, $replace);
	}
	
	//-----------------------------------------------------------------------------
	// Default function
	function show () {
		if(!DEBUG_MODE){
			return;
		}
	
		$methods = array();
		$class_name = get_class($this);
		foreach ((array)get_class_methods($class_name) as $_method_name) {
			// Skip unwanted methods
			if ($_method_name{0} == "_" || $_method_name == $class_name || $_method_name == __FUNCTION__) {
				continue;
			}
			$methods[] = array(
				"link"	=> "./?object=".$_GET["object"]."&action=".$_method_name,
				"name"	=> $_method_name,
			);
		}
		$this->_avail_methods = $methods;
		// Process template
		$replace = array(
			"methods"	=> $methods,
		);
		return tpl()->parse(__CLASS__."/main", $replace);
	}
	
	//-----------------------------------------------------------------------------
	// 
	function test_stpls () {
		$test_array_1 = array("One", "Two", "Three", "Four");
		$test_array_2 = array(
			"One"	=> array(
				"name"	=> "First",
			),
			"Two"	=> array(
				"name"	=> "Second",
			),
			"Three"	=> array(
				"name"	=> "Third",
			),
			"Four"	=> array(
				"name"	=> "Fourth",
			),
		);
		// Process template
		$replace = array(
			"test_array_1"	=> $test_array_1,
			"test_array_2"	=> $test_array_2,
			"cond_1"		=> 1,
			"cond_2"		=> 2,
			"cond_3"		=> 2,
		);
		return tpl()->parse(__CLASS__."/".__FUNCTION__, $replace);
	}

	//-----------------------------------------------------------------------------
	// 
	function json () {
		$_time_start = microtime(true);
		$data1 = array(1, 1.0, 'hello world', true, null, -1, 11.0, '~!@#$%^&*()_+|', false, null);
		$data2 = array('zero' => $data1, 'one' => $data1, 'two' => $data1, 'three' => $data1, 'four' => $data1, 'five' => $data1, 'six' => $data1, 'seven' => $data1, 'eight' => $data1, 'nine' => $data1);
		$data = array($data2, $data2);
		$encoded = common()->json_encode($data);
		$decoded = common()->json_decode($encoded);
		$body .= "<h1>Source:</h1>".print_r($data, 1)."<br />";
		$body .= "<h1>Encoded:</h1>".$encoded."<br />";
		$body .= "<h1>Decoded:</h1>".print_r($decoded, 1)."<br />";
		$body .= "<br /><b>Time spent: ".round(microtime(true) - $_time_start, 3)." sec<br />";
		return $body;
	}
	
	//-----------------------------------------------------------------------------
	// 
	function boxes () {
		return $this->_call_sub_module(__FUNCTION__);
	}

	//-----------------------------------------------------------------------------
	// 
	function ajax_login () {
		return tpl()->parse(__CLASS__."/test_ajax_login", $replace);
	}

	//-----------------------------------------------------------------------------
	// 
	function poll () {
		$POLL_OBJ = main()->init_class("poll");
		$body .= $POLL_OBJ->show(array(
			"object_name"	=> "forum",
			"object_id"		=> 1,
		));
		$body .= "<br />\r\n";
		$body .= $POLL_OBJ->view(array(
			"object_name"	=> "forum",
			"object_id"		=> 1,
		));
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Test current project mailing system
	function mail () {
		// Only for members!
		if (!$this->USER_ID && !$_SESSION["admin_id"]) {
			return "Access denied. Only for members!";
		}
		if ($_POST["email"]) {
			$msg = "Profy_Framework: Testing ".$_SERVER["HTTP_HOST"]." mailer";
			$result = common()->quick_send_mail($_POST["email"], $msg, $msg."\nMessage part here");
			return "Result: ".$result ? "<b style='color:green;'>success</b>" : "<b style='color:green;'>failed</b>";
		}
		// Display form
		return "<form action='./?object=".$_GET["object"]."&action=".$_GET["action"]."' method='post'><input type='text' name='email'><input type='submit' name='go' value='SEND!'></form>";
	}

	//-----------------------------------------------------------------------------
	// test XPM2 mailer
	function smtp_xpm2 () {
		return $this->_call_sub_module(__FUNCTION__);
	}

	//-----------------------------------------------------------------------------
	// test XPM4 mailer
	function smtp_xpm4 () {
		return $this->_call_sub_module(__FUNCTION__);
	}

	//-----------------------------------------------------------------------------
	// test EasySwift mailer
	function smtp_swift () {
		return $this->_call_sub_module(__FUNCTION__);
	}

	//-----------------------------------------------------------------------------
	// test PHPMailer
	function smtp_phpmailer () {
		return $this->_call_sub_module(__FUNCTION__);
	}

	//-----------------------------------------------------------------------------
	// 
	function send_mail () {
		return common()->quick_send_mail("yfix.dev@gmail.com", "test subject", "blablabla");
	}

	/**
	* PHPAmiler testing
	*/
	function phpmailer () {
		require_once(YF_PATH."libs/phpmailer/class.phpmailer.php");
		$mail             = new PHPMailer(); // defaults to using php "mail()"
		$body             = file_get_contents(INCLUDE_PATH. 'uploads/mail_test.html');
		$mail->SetFrom('yuri.vysotskiy@gmail.com', 'YFix Team');
		$address = "yuri.vysotskiy@gmail.com";
		$mail->AddAddress($address, "yuri.vysotskiy");
		$mail->Subject    = "PHPMailer Test Subject via mail(), basic";
		$mail->AltBody    = "To view the message, please use an HTML compatible email viewer!"; // optional, comment out and test
		$mail->MsgHTML($body);
		$mail->IsHTML(true);
		if(!$mail->Send()) {
			return "Mailer Error: " . $mail->ErrorInfo;
		} else {
			return "Message sent!";
		}
	}

	//-----------------------------------------------------------------------------
	// 
	function rate () {
		$body .= "<img src='".WEB_PATH."uploads/gallery/medium/000/000/001/1_260512.jpg' /><br />";

		$RATE_OBJ = main()->init_class("rate");
		$body .= $RATE_OBJ->_show_for_object(array(
			"object_name"	=> "gallery_photo",
			"object_id"		=> 260512,
		));
		return $body;
	}

	//-----------------------------------------------------------------------------
	// 
	function photo_rating () {
		$OBJ = main()->init_class("photo_rating");
		return $OBJ->_show_photo();
	}

	//-----------------------------------------------------------------------------
	// 
	function diff () {
		return $this->_call_sub_module(__FUNCTION__);
	}

	//-----------------------------------------------------------------------------
	// 
	function geo_country () {
		// UKRTELECOM ISP, Maxmind GeoCity does not recognize it, 
		// but it seems that Maxmind GeoCountry can get at least country
		$cur_ip = "92.113.3.128";
		$ip_data = common()->_get_geo_data_from_db($cur_ip);
		$body = "IP: ".$cur_ip."<br /><br />";
		$body .= "GEO DATA:<br /> <pre>".($ip_data ? print_r($ip_data, 1) : "Unknown... :-(")."</pre>";
		return $body;
	}

	//-----------------------------------------------------------------------------
	// unicode functions
	function unicode () {
		return $this->_call_sub_module(__FUNCTION__);
	}


	//-----------------------------------------------------------------------------
	// Main function
	function maxmind_phone_check() {
		return $this->_call_sub_module(__FUNCTION__);
    }

	//-----------------------------------------------------------------------------
	// 
	function email_verify () {
		if (empty($_GET["id"])) {
			return "Please specify email as \$_GET['id']";
		}
		$_time_start = microtime(true);

		$GLOBALS['_email_verify_debug'] = true;

		$result = common()->email_verify($_GET["id"], 1, 1, 1);

		$body .= "<b>".$_GET["id"]."</b> seems to be: ".($result ? "<b style='color:green;'>OK</b>" : "<b style='color:red;'>WRONG</b>");
		$body .= "<br />Spent time: <b>".common()->_format_time_value(microtime(true) - (float)$_time_start)." secs</b><br />";

		$body .= $GLOBALS['_email_verify_output'];
		return $body;
	}

	//-----------------------------------------------------------------------------
	// 
	function synonym () {
		// NOTE: encoding = utf8
		$source = '{сегодня|вчера|20 лет назад} [test|||test1] [te|s|t3|test4]#3# [te|s|t|5[te|s|t|5]#1,4#|t|e|s|t6]#6,4# {Жак Ив Кусто|Леонид Хрущев{ и его колеги|, а также родственники жены| в компании инопланетян}} {сообщил|утонул|занимался сексом c{ {Хилари Клинтон|Перис Хилтон|Надеждой Крупской|Мерлином Менсоном}}} {на %DEMO% Елисейских полях|на полях|в подезде|в ванной|в ванной с утятами|на унитазе|пьяным|%DEMO%|%DEMO%|}';
		if (!empty($_POST)) {
			$source = $_POST["source"];
			$OBJ = main()->init_class("synonymizer", "classes/");
			$result .= "1) ". $OBJ->process($source)."\r\n\r\n";
			$result .= "2) ". $OBJ->process($source)."\r\n\r\n";
			$result .= "3) ". $OBJ->process($source)."\r\n\r\n";
		}
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"source"		=> _prepare_html($source),
			"result"		=> _prepare_html($result),
		);
		return tpl()->parse(__CLASS__."/".__FUNCTION__, $replace);
	}

	//-----------------------------------------------------------------------------
	// 
	function image_resize () {
		$body .= "<h3>Testing image upload and resize</h3>";
		$img_src = "uploads/tmp/test_resized_image.jpg";
		if (file_exists(INCLUDE_PATH. $img_src)) {
			unlink(INCLUDE_PATH. $img_src);
		}
		// Do upload and resize to 500 x 500 px
		if (!empty($_POST)) {
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

	//-----------------------------------------------------------------------------
	// 
	function openid (){
		if(isset($_POST["openid_url"])){	

			$OBJ = main()->init_class("openid", "modules/");
			$message = $OBJ->_get($_POST["openid_url"]);
		}
		$replace = array(
			"action"	=> WEB_PATH."./?object=".$_GET["object"]."&action=".$_GET["action"],
			"message"	=> $message,
		);
		return tpl()->parse($_GET["object"]."/".__FUNCTION__, $replace);

	}

	//-----------------------------------------------------------------------------
	// 
	function spoiler (){
		$head_text="Cool spoiler head";	
		$body_text="Here is a cool spoiler body text<br />";
/*		"We can also put some spoilers into each other. Like this..."
		.$this->_spoiler($head_text, $body_text);
*/
		$replace = array(
			"head_text" => $head_text,
			"body_text" => $body_text,
		);
		return tpl()->parse($_GET["object"]."/".__FUNCTION__, $replace);
	}

	//-----------------------------------------------------------------------------
	// 
	function redirect (){
		return js_redirect("./?object=".$_GET["object"], true, "Testing redirect", 3);
	}

	//-----------------------------------------------------------------------------
	// 
	function filter_text (){
		// Do process
		if (!empty($_POST)) {
			$BB_CODES_OBJ = main()->init_class("bb_codes", "classes/");
			if (is_object($BB_CODES_OBJ)) {
				$result = $_POST["text"];
				$result = _filter_text($result);
				$res2	= $result;
				$result = $BB_CODES_OBJ->_force_close_bb_codes($result);
				$result = $BB_CODES_OBJ->_process_text($result);
				$result .= "<br /><br /><br />".$res2;
			}
		}
		$replace = array(
			"form_action"	=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"result"		=> $result,
			"source"		=> $_POST["text"] ? $_POST["text"] : "[COLOR=green][U][SIZE=7][B][I]пользовательдолженпользовательдолжен[/I][/B][/SIZE][/U][/COLOR]http://www.gooooooooooooooooooooooooggle.com",
		);
		return tpl()->parse($_GET["object"]."/".__FUNCTION__, $replace);
	}

	//-----------------------------------------------------------------------------
	// 
	function translate (){
		$body .= "<b> t(\"Test var\"): </b> ".t("Test var")."<br /><br />";
		$body .= "<b> t(\"::forum::Test var\"): </b> ".t("::forum::Test var")."<br /><br />";
		$body .= "<b> t(\"::forum__new_post::Test var\"): </b> ".t("::forum__new_post::Test var")."<br /><br />";
		$body .= "<b> t(\"::gallery::Test var\"): </b> ".t("::gallery::Test var")."<br /><br />";
		$body .= "<b> t(\"::bl_ablabla::Test var\"): </b> ".t("::bl_ablabla::Test var")."<br /><br />";
		$body .= "<b> t(\"Read %numreads times\", array(\"%numreads\" => \"0\")): </b> ".t("Read %numreads times", array("%numreads" => "0"))."<br /><br />";
		$body .= "<b> t(\"Read %numreads times\", array(\"%numreads\" => \"1\")): </b> ".t("Read %numreads times", array("%numreads" => "1"))."<br /><br />";
		$body .= "<b> t(\"Read %numreads times\", array(\"%numreads\" => \"2\")): </b> ".t("Read %numreads times", array("%numreads" => "2"))."<br /><br />";
		$body .= "<b> t(\"Read %numreads times\", array(\"%numreads\" => \"11\")): </b> ".t("Read %numreads times", array("%numreads" => "11"))."<br /><br />";
		$body .= "<b> t(\"Read %numreads times\", array(\"%numreads\" => \"20\")): </b> ".t("Read %numreads times", array("%numreads" => "20"))."<br /><br />";
		$body .= "<b> t(\"Read %numreads times\", array(\"%numreads\" => \"10001\")): </b> ".t("Read %numreads times", array("%numreads" => "10001"))."<br /><br />";

		$body .= "<b> t(\"While searching %num folders found\", array(\"%num\" => \"0\")): </b> ".t("While searching %num folders found", array("%num" => "0"))."<br /><br />";
		$body .= "<b> t(\"While searching %num folders found\", array(\"%num\" => \"1\")): </b> ".t("While searching %num folders found", array("%num" => "1"))."<br /><br />";
		$body .= "<b> t(\"While searching %num folders found\", array(\"%num\" => \"2\")): </b> ".t("While searching %num folders found", array("%num" => "2"))."<br /><br />";
		$body .= "<b> t(\"While searching %num folders found\", array(\"%num\" => \"3\")): </b> ".t("While searching %num folders found", array("%num" => "3"))."<br /><br />";
		$body .= "<b> t(\"While searching %num folders found\", array(\"%num\" => \"4\")): </b> ".t("While searching %num folders found", array("%num" => "4"))."<br /><br />";
		$body .= "<b> t(\"While searching %num folders found\", array(\"%num\" => \"5\")): </b> ".t("While searching %num folders found", array("%num" => "5"))."<br /><br />";
		$body .= "<b> t(\"While searching %num folders found\", array(\"%num\" => \"9\")): </b> ".t("While searching %num folders found", array("%num" => "9"))."<br /><br />";
		$body .= "<b> t(\"While searching %num folders found\", array(\"%num\" => \"10\")): </b> ".t("While searching %num folders found", array("%num" => "10"))."<br /><br />";
		$body .= "<b> t(\"While searching %num folders found\", array(\"%num\" => \"11\")): </b> ".t("While searching %num folders found", array("%num" => "11"))."<br /><br />";
		$body .= "<b> t(\"While searching %num folders found\", array(\"%num\" => \"12\")): </b> ".t("While searching %num folders found", array("%num" => "12"))."<br /><br />";
		$body .= "<b> t(\"While searching %num folders found\", array(\"%num\" => \"20\")): </b> ".t("While searching %num folders found", array("%num" => "20"))."<br /><br />";
		$body .= "<b> t(\"While searching %num folders found\", array(\"%num\" => \"100\")): </b> ".t("While searching %num folders found", array("%num" => "100"))."<br /><br />";
		$body .= "<b> t(\"While searching %num folders found\", array(\"%num\" => \"101\")): </b> ".t("While searching %num folders found", array("%num" => "101"))."<br /><br />";
		$body .= "<b> t(\"While searching %num folders found\", array(\"%num\" => \"111\")): </b> ".t("While searching %num folders found", array("%num" => "111"))."<br /><br />";
		$body .= "<b> t(\"While searching %num folders found\", array(\"%num\" => \"10003\")): </b> ".t("While searching %num folders found", array("%num" => "10003"))."<br /><br />";
		/*
		{t(While searching %num folders found,%num=1001)}
		� ����� ���᪠ ������� %num �����
		� ����� ���᪠ {������� %num �����|0:����� �� �������|1:������� %num �����|2,3,4:������� %num �����|11-14:������� %num �����|������� %num �����}
		*/
		return $body;
	}

	//-----------------------------------------------------------------------------
	// 
	function notice () {
		if (!empty($_POST)) {
			common()->set_notice($_POST["notice"]);
			return js_redirect($_SERVER["HTTP_REFERER"]);
		}
		$body .= common()->show_notices()."<br />";
		$body .= "<form action='./?object=".$_GET["object"]."&action=".$_GET["action"]."' method='post'>Set notice here:<br /><textarea name='notice'>Test notice text</textarea><br /><input type='submit'></form>";
		return $body;
	}

	//-----------------------------------------------------------------------------
	// Display sample page with selected user theme name
	function user_theme () {
		return $this->_call_sub_module(__FUNCTION__);
	}

	//-----------------------------------------------------------------------------
	// Display sample page with selected user design id
	function user_design () {
		return $this->_call_sub_module(__FUNCTION__);
	}

	//-----------------------------------------------------------------------------
	// Display sample page with selected user color scheme (apply it to the default theme)
	function color_scheme () {
		return $this->_call_sub_module(__FUNCTION__);
	}

	//-----------------------------------------------------------------------------
	// Display sample page with selected user color scheme
	function graphic_scheme () {
		return $this->_call_sub_module(__FUNCTION__);
	}

	//-----------------------------------------------------------------------------
	// 
	function inline_tooltip () {
		$body .= '
			<label for="google_stats_id">Google stats ID &nbsp;&nbsp;
				{itip("New tooltip text goes here<br />New tooltip text goes here<br />New tooltip text goes here")}
			</label>
		    <input type="text" name="google_stats_id" value="" id="google_stats_id">
		';

		return $body;
	}

	//-----------------------------------------------------------------------------
	// 
	function lang () {
		$OBJ = main()->init_class("dynamic");
		return $OBJ->_change_lang_form();
	}

	/**
	* Client for remote make thumb service
	*/
	function _remote_thumb_client ($url_for_thumb = "") {
		if (!$url_for_thumb) {
			return false;
		}

		$server_url = "http://www.test.com/remote_thumb_server/";

		$new_tmp_local_thumb_name = INCLUDE_PATH."uploads/tmp/".md5(microtime(true)).".jpg";

		_mkdir_m(dirname($new_tmp_local_thumb_name));

		$result_from_server = false;
		if ($ch = curl_init()) {
			curl_setopt($ch, CURLOPT_URL, $server_url);
			curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.01; Windows NT 5.1)");
			curl_setopt($ch, CURLOPT_REFERER, $server_url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, "url_for_thumb=".urlencode($url_for_thumb));
			$result_from_server = curl_exec ($ch);
			curl_close ($ch);
		}
		if ($result_from_server) {
			file_put_contents($new_tmp_local_thumb_name, $result_from_server);
			return $new_tmp_local_thumb_name;
		} else {
			return false;
		}
	}

	/**
	*/
	function remote_thumb_server () {
		main()->NO_GRAPHICS = true;

		$url_for_thumb = $_POST["url_for_thumb"] ? $_POST["url_for_thumb"] : $_GET["id"];
		
		$new_tmp_server_thumb_name = INCLUDE_PATH."uploads/tmp/".md5(microtime(true)).".jpg";
		$DIR_OBJ = main()->init_class("dir", "classes/");
		$DIR_OBJ->delete_dir(dirname($new_tmp_local_thumb_name));
		_mkdir_m(dirname($new_tmp_local_thumb_name));

		// Using Firefox to generate thumbnail
		common()->_make_thumb_remote($url_for_thumb, $new_tmp_server_thumb_name);
		// Throw result
		if (file_exists($new_tmp_server_thumb_name) && filesize($new_tmp_server_thumb_name)) {
			header("Content-type: image/jpeg");
			$result = file_get_contents($new_tmp_server_thumb_name);
		}

		exit($result);
	}

	/**
	* Testing SSH wrapper
	*/
	function ssh () {
		return $this->_call_sub_module(__FUNCTION__);
	}

	/**
	*/
	function bb_code () {
		return $this->_call_sub_module(__FUNCTION__);
	}

	/**
	*/
	function text_typos () {
		return $this->_call_sub_module(__FUNCTION__);
	}

	/**
	* Testing short functions: user(), update_user(), search_user()
	*/
	function user () {
		return $this->_call_sub_module(__FUNCTION__);
	}

	/**
	*/
	function multi_request () {
		$t = array(
			"http://google.com.ua",
			"http://yahoo.com",
			"http://google.ru",
			"http://msn.com",
			"http://live.com",
			"http://facebook.com",
		);
		$t = array_combine($t, $t);
		return print_R(_prepare_html(common()->multi_request($t)), 1);
	}

	//-----------------------------------------------------------------------------
	// 
	function utf8_clean () {
		$text = file_get_contents(YF_PATH. "libs/utf8_funcs/utils/broken_utf8.txt");
		$body .= $text;
		$body .= "<br /><hr />\n\n";
		$body .= common()->utf8_clean($text);
		return $body;
	}

	//-----------------------------------------------------------------------------
	// 
	function threaded_exec () {
		if (MAIN_TYPE_USER || !$_SESSION["admin_id"]) {
			exit("Only for admin");
		}
		for ($i = 1; $i <= 9; $i++) {
			$host = "nginx".$i.".inffinity-internet.com";
			$threads[$host] = array("func" => "gethostbyname", "name" => $host);
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
		return "true";
	}

	/**
	* Call test sub-module
	*/
	function _call_sub_module ($sub_module = "") {
		$OBJ = main()->init_class("test_".$sub_module, "modules/test/");
		return is_object($OBJ) ? $OBJ->run_test() : "";
	}

	/**
	* Quick menu hook
	*/
	function _quick_menu () {
		$menu = array();
		foreach ((array)$this->_avail_methods as $_info) {
			$menu[] = array(
				"name"	=> $_info["name"],
				"url"	=> $_info["link"],
			);
		}
		return $menu;	
	}
}
