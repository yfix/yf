<?php

class yf_docs {

	/**
	*/
	function _init() {
		require_js('//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.0/highlight.min.js');
		require_js('//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.0/languages/php.min.js');
		require_js('<script>hljs.initHighlightingOnLoad();</script>');
		require_css('//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.0/styles/railscasts.min.css');
		require_css('section.page-contents pre, pre.prettyprint {
			background-color: transparent;
			border: 0;
			font-family: inherit;
			font-size: inherit;
			font-weight: bold;
		}');
		$this->docs_dir = YF_PATH.'.dev/docs/en/';

		tpl()->add_pattern_callback('/\{github\(\s*["\']{0,1}([a-z0-9_:\.]+?)["\']{0,1}\s*\)\}/i', function($m, $r, $name, $_this) {
			$body = trim($m[1]);
// TODO: find correct line
// TODO: find correct path
			if (false !== strpos($body, '.')) {
				list($class, $method) = explode('.', $body);
				$line = 100;
				$path = 'classes/yf_'.$class.'.php#L'.$line;
			} else {
				$line = 100;
				// Function, maybe inside common_funcs...
				$path = 'share/functions/yf_common_funcs.php#L'.$line;
			}
			return '<a href="https://github.com/yfix/yf'.$path.'" class="btn btn-mini btn-xs btn-primary pull-right"><i class="icon icon-github icon-large"></i></a>';
		});
	}

	/***/
	function view() {
		$name = preg_replace('~[^a-z0-9_-]+~ims', '', $_GET['id']);
		if ($name) {
			$f = $this->docs_dir. $name. '.stpl';
			if (file_exists($f)) {
				return '<section class="page-contents">'.tpl()->parse_string(file_get_contents($f), $replace, 'doc_'.$name).'</section>';
			}
		}
		return _e('Not found');
	}

	/***/
	function show() {
		if ($_GET['id']) {
			return $this->view();
		}
		foreach (glob($this->docs_dir.'*.stpl') as $path) {
			$f = basename($path);
			$name = substr($f, 0, -strlen('.stpl'));
			$body[] = '<li><a href="./?object='.$_GET['object'].'&action=view&id='.$name.'">'.$name.'</a></li>';
		}
		return implode(PHP_EOL, $body);
	}

	/***/
	function _hook_side_column() {
		$items = array();
		$url = process_url('./?object='.$_GET['object']);
		foreach ((array)glob(PROJECT_PATH.'modules/*.class.php') as $cls) {
			$cls = basename($cls);
			if ($cls == __CLASS__) {
				continue;
			}
			$name = substr($cls, 0, -strlen('.class.php'));
			$items[] = '<li><a href="./?object='.$name.'"><i class="icon-chevron-right"></i> '.t($name).'</a></li>';
		}
		return '<div class="bs-docs-sidebar"><ul class="nav nav-list bs-docs-sidenav">'.implode(PHP_EOL, $items).'</ul></div>';
	}
}