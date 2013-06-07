<?php
/**
 * Класс для загрузки и выполнения функций, которые хранятся в заданной папке в виде PHP файлов.
 *
 * Пример использования:
 *   #этот метод можно не вызывать, если в .htaccess путь уже прописан
 *   Func::add_include_path(dirname(__FILE__) . '/func/');
 *   ...
 *   $s = Func::call('html_optimize', $s);  # PHP < 5.3.0
 *   $s = Func::html_optimize($s);		  # PHP >= 5.3.0
 *   ...
 *   #еще один способ для функций, которые возвращают результат по ссылке
 *   Func::load('utf8_str_limit', 'strip_tags_smart');
 *   $s = utf8_str_limit(strip_tags_smart($s), 100, null, $is_cutted);
 *
 * @license  http://creativecommons.org/licenses/by-nc-sa/3.0/
 * @author   Nasibullin Rinat <nasibullin at starlink ru>
 * @charset  ANSI
 * @version  2.0.0
 */
class Func
{
	#запрещаем создание экземпляра класса, вызов методов этого класса только статически!
	private function __construct() {}

	/**
	 * Добавляет пути в include_path (для загрузки функций, хранящихся в PHP файлах)
	 *
	 * Этот метод можно не вызывать, если в .htaccess добавить следующие настройки:
	 *   #пути для включаемых библиотек (разделитель в Unix - ":", в Windows - ";")
	 *   php_value include_path "./:/patch_to/func/"
	 */
	public static function add_include_path(/*string*/ $path)
	{
		$pathes = explode(PATH_SEPARATOR, get_include_path());
		foreach (func_get_args() as $path)
		{
			if (! is_dir($path))
			{
				trigger_error('Include path "' . $path . '" does not exist!', E_USER_WARNING);
				continue;
			}
			$path = realpath($path);
			if (array_search($path, $pathes) === false) array_push($pathes, $path);
		}#foreach
		return set_include_path(implode(PATH_SEPARATOR, $pathes));
	}

	/**
	 * Загружает (в случае необходимости) и выполняет функцию.
	 * Вызов как Func::<function>(<param1>, <param2>, ...)
	 * PHP >= 5.3.0
	 */
	public static function __callStatic($func, $args)
	{
		if (! function_exists($func)) self::load($func);
		return call_user_func_array($func, $args);
	}

	/**
	 * Загружает (в случае необходимости) и выполняет функцию.
	 * Вызов как Func::call(<function>, <param1>, <param2>, ...)
	 * или Func::call(<function>|<filename>, <param1>, <param2>, ...), если название функции и имя файла, в которой она хранится, различаются
	 * PHP < 5.3.0
	 */
	public static function call()
	{
		$args = func_get_args();
		$func = array_shift($args);
		@list($func, $file) = explode('|', $func);
		if (! $file) $file = $func;
		if (! function_exists($func)) self::load($file);
		return call_user_func_array($func, $args);
	}

	/**
	 * Загружает одну или несколько функций.
	 * Для работы с ссылочными переменными функций сначала необходимо выполнить загрузку функций,
	 * а потом делать их вызов как обычно.
	 */
	public static function load()
	{
		foreach (func_get_args() as $func)
		{
			if (! function_exists($func))
			{
				require_once $func . '.php';
				#мы д.б. уверены, что функция существует, т.к. она и call_user_func_array()
				#могут возвратить FALSE, в итоге мы получим икаженный результат с E_USER_WARNING
				if (! function_exists($func)) trigger_error('Function "' . $func . '" does not exist!', E_USER_ERROR);
			}
		}#foreach
		return true;
	}
}
?>