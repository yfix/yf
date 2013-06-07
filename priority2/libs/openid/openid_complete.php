<?php
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
		 
		if( !login_by_openid($openid) )
		{
			//Получаем данные о пользователе
			$sreg_resp = Auth_OpenID_SRegResponse::fromSuccessResponse($response);

			$sreg = $sreg_resp->contents();

			print_r($sreg);
	
			$email = @$sreg['email'];
			$nickname = @$sreg['nickname'];
			$fullname = @$sreg['fullname'];
			$gender = @$sreg['gender'];
			
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
		} else
		{
			echo 'Залогинились по OpenID<br/>';
			exit;
		}
	}
}

echo $openid_error;

?>