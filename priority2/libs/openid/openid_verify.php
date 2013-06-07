<?php
set_include_path (YF_PATH. "openid". PATH_SEPARATOR. get_include_path());
require_once('openid_common.php');

if( empty($openid_error) )
{

	$openid_url = $_REQUEST['openid_url'];
	$openid = $openid_url;


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

echo $openid_error;
?>
