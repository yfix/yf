<? die('go away!');
// Array of predefined SMTP accounts
$this->_smtp_accounts = array(
	"mail_ru" => array(
		'smtp_host'		=> 'smtp.mail.ru',
		'smtp_port'		=> '25',
		'smtp_user_name'=> 'test',
		'smtp_password'	=> 'test',
		'smtp_from'		=> 'test@mail.ru',
	),
	"yahoo" => array(
		'smtp_host'		=> 'smtp.yahoo.com',
		'smtp_port'		=> '12345',
		'smtp_user_name'=> 'test2',
		'smtp_password'	=> 'test2',
		'smtp_from'		=> 'test2@yahoo.com',
	),
	"other_smtp" => array(
		'smtp_host'		=> 'smtp.other.com',
		'smtp_port'		=> '25',
		'smtp_user_name'=> 'your_user_name',
		'smtp_password'	=> 'your_user_pswd',
		'smtp_from'		=> 'your_user_name@other.com',
	),
);
// Array of patterns to use predefined accounts
$this->_smtp_patterns = array(
	'^.+@mail\.ru$'		=> "mail_ru",
	'^.+@hotmail\.com$'	=> "other_smtp",
	'^.+@aol\.com$'		=> array_rand($this->_smtp_accounts),
);
?>