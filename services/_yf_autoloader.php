<?php

class yf_autoloader
{

	public $libs_root = '';
	public $is_console = false;
	public $config_names = [
		'file',
		'composer_names',
		'git_urls',
		'pear',
		'autoload_config',
		'require_once',
		'manual',
		'require_services',
		'example',
	];
	public $composer_names = [];
	public $git_urls = [];
	public $autoload_config = [];
	public $pear = [];
	public $require_services = [];
	public $manual = [];
	public $require_once = [];
	public $example = [];
	public $php_libs = [];
	public $_paths_cache = [];

	/***/
	public function __construct($config = [])
	{
		!defined('YF_PATH') && define('YF_PATH', dirname(dirname(__DIR__)) . '/');
		$this->libs_root = YF_PATH . 'vendor/';
		$this->is_console = $_SERVER['argc'] && !isset($_SERVER['REQUEST_METHOD']);

		foreach ($this->config_names as $name) {
			if (isset($config[$name])) {
				$this->$name = $config[$name];
			}
		}

		$this->process_composer();
		$this->process_git();
		$this->process_pear();
		$this->process_yf_autoload();
		$this->process_require_services();
		$this->process_require_once();
		$this->process_manual();
		$this->process_example();
	}

	/***/
	public function process_example()
	{
		$libs_root = $this->libs_root;
		$example = $this->example;
		if (!is_callable($example)) {
			return false;
		}
		if ($this->is_console) {
			$trace = debug_backtrace();
			if (realpath($_SERVER['argv'][0]) === realpath($trace[1]['file'])) {
				try {
					$example($this);
				} catch (Exception $e) {
					var_dump($e);
				}
			}
		}
	}

	/***/
	public function process_composer()
	{
		$libs_root = $this->libs_root;
		$composer_names = $this->composer_names;
		if (!$composer_names) {
			return false;
		}
		$dir = $libs_root . 'vendor/';
		foreach ((array)$composer_names as $composer_package) {
			$check_file = $dir . dirname($composer_package) . '/' . basename($composer_package) . '/';
			if (!file_exists($check_file)) {
				$this->composer_require($composer_package);
				$this->check_error($composer_package, $dir, $check_file, 'something wrong with composer');
			}
		}
		require_once $dir . 'autoload.php';
		// Exclude raw git clone steps
		$this->git_urls = [];
		$this->autoload_config = [];
	}

	/***/
	public function process_git()
	{
		$libs_root = $this->libs_root;
		$git_urls = $this->git_urls;
		foreach ((array)$git_urls as $git_url => $lib_dir) {
			$dir = $libs_root . $lib_dir;
			$check_file = $dir . '.gitignore';
			if (!file_exists($check_file)) {
				$git_opts = '';
				if (false !== strpos($git_url, '~')) {
					list($git_url, $git_tag) = explode('~', $git_url);
					$git_opts = '--branch "' . $git_tag . '" --single-branch ';
				}
				$cmd = 'git clone --depth 1 ' . $git_opts . ' ' . $git_url . ' ' . $dir . ' && rm -rf ' . $dir . '/.git';
				passthru($cmd);
				$this->check_error(basename($lib_dir), $dir, $check_file);
			// } elseif (getenv('YF_FORCE_UPDATE_SERVICES')) {
			// 	if (false !== strpos($git_url, '~')) {
			// 		// Tag was forced, so do nothing
			// 	} else {
			// 		$cmd = 'cd ' . $dir . ' && git pull';
			// 	}
			// 	passthru($cmd);
			}
		}
	}

	/***/
	public function process_pear()
	{
		if (!$this->pear) {
			return false;
		}
		spl_autoload_register(function ($class) {
			$libs_root = $this->libs_root;
			$pear_config = $this->pear;
			foreach ((array)$pear_config as $lib_dir => $prefix) {
				if (strlen($prefix) && strpos($class, $prefix) !== 0) {
					continue;
				}
				$path = $libs_root . $lib_dir . str_replace('_', '/', $class) . '.php';
				if (file_exists($path)) {
					require $path;
				}
				return true;
			}
		});
	}

	/***/
	public function process_yf_autoload()
	{
		$libs_root = $this->libs_root;
		$autoload_config = $this->autoload_config;
		if (!$autoload_config) {
			return false;
		}
		spl_autoload_register(function ($class) {
			$libs_root = $this->libs_root;
			$autoload_config = $this->autoload_config;
			foreach ((array)$autoload_config as $lib_dir => $prefix) {
				$no_cut_prefix = false;
				if (substr($prefix, 0, strlen('no_cut_prefix:')) === 'no_cut_prefix:') {
					$no_cut_prefix = true;
				}
				if (false !== strpos($prefix, ':')) {
					list($tmp, $prefix) = explode(':', $prefix);
				}
				if (strpos($class, $prefix) !== 0) {
					continue;
				}
				if ($no_cut_prefix) {
					$path = $libs_root . $lib_dir . str_replace("\\", '/', $class) . '.php';
				} else {
					$path = $libs_root . $lib_dir . str_replace("\\", '/', substr($class, strlen($prefix) + 1)) . '.php';
				}
				if (!file_exists($path)) {
					continue;
				}
				require $path;
				return true;
			}
		});
	}

	/***/
	public function process_require_once()
	{
		$libs_root = $this->libs_root;
		$require_once = $this->require_once;
		if (!$require_once) {
			return false;
		}
		foreach ((array)$require_once as $path) {
			require_once $libs_root . $path;
		}
	}

	/**
	 */
	function _get_lib_path($name, $params = [])
	{
		if (isset($this->php_libs[$name])) {
			return $this->php_libs[$name];
		}
		if (empty($this->_paths_cache)) {
			$suffix = '.php';
			$patterns = [
				'framework' => [
					YF_PATH . 'services/*' . $suffix,
					YF_PATH . 'services/*/*' . $suffix,
					YF_PATH . 'share/services/*' . $suffix,
					YF_PATH . 'share/services/*/*' . $suffix,
					YF_PATH . 'plugins/*/services/*' . $suffix,
					YF_PATH . 'plugins/*/services/*/*' . $suffix,
					YF_PATH . 'plugins/*/share/services/*' . $suffix,
					YF_PATH . 'plugins/*/share/services/*/*' . $suffix,
				],
			];
			if (defined('APP_PATH')) {
				$patterns['app'] = [
					APP_PATH . 'services/*' . $suffix,
					APP_PATH . 'services/*/*' . $suffix,
					APP_PATH . 'share/services/*' . $suffix,
					APP_PATH . 'share/services/*/*' . $suffix,
					APP_PATH . 'plugins/*/services/*' . $suffix,
					APP_PATH . 'plugins/*/services/*/*' . $suffix,
					APP_PATH . 'plugins/*/share/services/*' . $suffix,
					APP_PATH . 'plugins/*/share/services/*/*' . $suffix,
				];
			}

			$slen = strlen($suffix);
			$paths = [];
			foreach ($patterns as $pathsList) {
				foreach ($pathsList as $glob) {
					foreach (glob($glob) as $_path) {
						$_name = substr(basename($_path), 0, -$slen);
						if ($_name == '_yf_autoloader') {
							continue;
						}
						$paths[$_name] = $_path;
					}
				}
			}
			// This double iterating code ensures we can inherit/replace services with same name inside project
			foreach ((array)$paths as $_name => $_path) {
				$this->_paths_cache[$_name] = $_path;
			}
		}
		$this->php_libs[$name] = $this->_paths_cache[$name];
		return $this->_paths_cache[$name];
	}

	/***/
	public function process_require_services()
	{
		$libs_root = $this->libs_root;
		$require_services = $this->require_services;
		if (!$require_services) {
			return false;
		}
		ob_start();
		foreach ((array)$require_services as $name) {
			require_once $this->_get_lib_path($name);
		}
		ob_end_clean();
	}

	/***/
	public function process_manual()
	{
		$libs_root = $this->libs_root;
		$manual = $this->manual;
		if (is_callable($manual)) {
			$manual($this);
		}
	}

	// globally: curl -s http://getcomposer.org/installer | php -- --install-dir=/usr/local/bin
	// locally: curl -s http://getcomposer.org/installer | php
	// ls -s /usr/local/bin/composer.phar /usr/local/bin/composer
	public function composer_require($package)
	{
		$libs_root = $this->libs_root;

		set_error_handler(function ($code, $msg) {
			// do nothing for these types of errors
		}, E_NOTICE | E_USER_NOTICE | E_DEPRECATED | E_USER_DEPRECATED);

		ob_start();
		include_once __DIR__ . '/composer.php';
		ob_end_clean();

		$cwd = getcwd();
		chdir($libs_root);

		$input = new Symfony\Component\Console\Input\ArrayInput(['command' => 'require', 'packages' => is_array($package) ? $package : [$package]]);
		$input->setInteractive(false);
		$application = new Composer\Console\Application();
		$application->setAutoExit(false);
		$application->run($input);

		restore_error_handler();
		chdir($cwd);
	}

	/***/
	public function check_error($name, $dir, $check_file, $error_reason = 'git url or command is wrong')
	{
		$libs_root = $this->libs_root;
		$error_reasons = [];
		if (!file_exists($check_file)) {
			if (!is_writable($dir)) {
				$error_reasons[] = $dir . ' is not writable';
				if (!is_readable($dir)) {
					$error_reasons[] = $dir . ' is not readable';
				} else {
					$stat = stat($dir);
					$posix = posix_getpwuid($stat['uid']);
					$error_reasons[] = ', details: file owner: ' . $posix['name'] . ', php owner: ' . $_SERVER['USER'] . ', file perms: ' . fileperms($dir);
				}
			}
		}
		if ($error_reasons) {
			throw new Exception('lib "' . $name . '" install failed. Reasons: ' . implode(', ', $error_reasons));
		}
	}
}
