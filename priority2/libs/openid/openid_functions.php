<?php

	function check_user_name($nickname)
	{
		echo 'Проверяю ник '.$nickname.' на допустимость.<Br/>';
		return mt_rand(0,1);
	}

	function create_user($nickname,$fullname,$email,$gender)
	{
		echo 'nickname = '.$nickname.'<BR> fullname = '.$fullname.'<BR> email = '.$email.'<BR> gender = '.$gender.'<br/>';
		return true;
	}
	
	function link_openid_user($user_id,$openid)
	{
		echo 'Привязываю OpenID к пользователю<br/>';
		$user_id = intval($user_id);
		if(DB::query("INSERT INTO openid_user_ref(openid,user_id) VALUES('".mysql_real_escape_string($openid)."',".$user_id.")"))
			return true;
		return false;
	}	
	
	function login_by_openid($openid)
	{
		echo 'Пытаюсь залогинить пользователя по OpenID '.$openid.'<br/>';
		$res = mysql_query("SELECT user_id FROM openid_user_ref WHERE openid = '".mysql_real_escape_string($openid)."'");
		$user_id = @mysql_result($res,0);
		mysql_free_result($res);
		if( !empty($user_id) )
		{
			echo 'Логиню пользователя с id '.$user_id.' без проверки пароля<br/>';
			return true;
		}
		return false;
	}
	
	

?>