<?php

class yf_openid{

	function _get($openid_url){

		set_include_path (YF_PATH. "libs/openid". PATH_SEPARATOR. get_include_path());
		
		require_once('openid_common.php');

		if( empty($openid_error) ){

			if(empty($openid_url) )
				$openid_error = 'Не указан OpenID-идентификатор.';
			else
			{
				$auth_request = $consumer->begin($openid_url);

				if (!$auth_request) {
					$openid_error = "Ошибка аутентификации: неверно указаны данные.";
				} else
				{
					$sreg_request = Auth_OpenID_SRegRequest::build(
													 // Required
													 array('nickname'),
													 // Optional
													 array('fullname', 'email','gender'));           
													 
					if ($sreg_request) {
						$auth_request->addExtension($sreg_request);
					}
					$auth_request->shouldSendRedirect();             
					$redirect_url = $auth_request->redirectURL($trust_root,$return_to);           
					
					if( Auth_OpenID::isFailure($redirect_url) )
						$openid_error = "Не могу перенаправить на сервер: ". $redirect_url->message;
					else
					{
						header("Location: ".$redirect_url);
						exit;
					}
				}
			 }
		}

		return $openid_error;
		
	}



	function complete(){
		set_include_path (YF_PATH. "/openid". PATH_SEPARATOR. get_include_path());
		require_once('openid_common.php');

		if( empty($openid_error) )
		{
		    $response = $consumer->complete($return_to);
		    // Проверяем ответ
		    if ($response->status == Auth_OpenID_CANCEL) {
		        // Это означает, что аутентификация была отменена
		        $openid_error = 'Регистрация отменена.';
		    } else if ($response->status == Auth_OpenID_FAILURE) {
		        // Неудача. Отображаем сообщение
		        $openid_error = "Регистрация не удалась: " . $response->message;
		    } else if ($response->status == Auth_OpenID_SUCCESS) {
		        // Все в порядке. Получаем identify url и данные пользователя
		        
		        $openid = $response->getDisplayIdentifier();
		         
				//Получаем данные о пользователе
				$sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);

				$sreg = $sreg_resp->contents();

				//print_r($sreg);
		
				$email = @$sreg['email'];
				$nickname = @$sreg['nickname'];
				$fullname = @$sreg['fullname'];
				$gender = @$sreg['gender'];

				$message = "openid -".$openid."<BR>";
				$message.= "nickname - ".$nickname."<BR>";
				$message.= "fullname - ".$fullname."<BR>";
				$message.= "gender - ".$gender."<BR>";
				$message.= "email - ".$email."<BR>";



				
				$replace = array(
					"action"	=> WEB_PATH."./?object=test&action=openid",
					"message"	=> $message,
				);
		
				return tpl()->parse("test/openid", $replace);






/*
				if( empty($nickname) || check_user_name($nickname) )
				{
					//Если мы не можем создавать пользователя с указанным ником или если сервер не возвратил ник, генерируем ник сами
					$nickname = str_replace('.','-',preg_replace('#[^a-zA-Z.\-_]#','',str_replace('http://','',$openid)));
					if( check_user_name($nickname) )
						$nickname = null; //если даже сгенерированный ник не подходит (или уже занят) - то тут надо что-то делать. У меня допустимы пользователи не имеющие ника вообще.
				}

				if( !create_user($nickname,$fullname,$email,$gender) )
				{
					$openid_error = 'Ошибка создания пользователя';
				} else
				{
					if( !link_openid_user($user_id,$openid) )
						$openid_error = 'Ошибка связывания OpenID с пользователем';
					else
					{
						login_by_openid($openid);
						echo 'Создали пользователя по OpenID и залогинились<br/>';
						exit;
					}
				}

*/
		    }
		}

		echo $openid_error;
	}
}
