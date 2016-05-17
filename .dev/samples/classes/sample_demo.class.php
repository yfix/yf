<?php

class sample_demo {

	/***/
	function _init() {
		_class('core_api')->add_syntax_highlighter();
	}

	/***/
	function _hook_side_column() {
		$items = array();
		$url = url('/@object');
		$methods = $this->_get_demos();
		$sample_methods = get_class_methods($this);
		sort($methods);
		foreach ((array)$sample_methods as $name) {
			if (in_array($name, $methods)) {
				continue;
			}
			$methods[] = $name;
		}
		foreach ((array)$methods as $name) {
			if ($name == 'show' || substr($name, 0, 1) == '_') {
				continue;
			}
			$items[] = array(
				'name'	=> $name,
				'link'	=> url('/@object/@action/'.urlencode($name)),
			);
		}
		return _class('html')->navlist($items);
	}

	/***/
	function show() {
		$docs = _class('docs');
		$dir = $docs->demo_dir;
		$dir_len = strlen($dir);
		$ext = '.php';
		$ext_len = strlen($ext);

		$name = preg_replace('~[^a-z0-9/_-]+~ims', '', $_GET['id']);
		if (strlen($name)) {
			$f = $dir. $name. '.php';
			if (!file_exists($f)) {
				return _404('Not found');
			}
			$body = include $f;
			if (is_callable($body)) {
				$self_source = _class('core_api')->get_function_source($body);
				$body = $body();
			} else {
				$self_source = array(
					'name'		=> $name,
					'file'		=> $f,
					'line_start'=> 1,
					'source'	=> $body,
				);
			}
			$name_html = preg_replace('~[^0-9a-z_-]~ims', '', $name);
			$header = 
				'<div id="head_'.$name_html.'" class="panel">
	                <div class="panel-heading">
						<h1 class="panel-title">
							<a href="'.url('/@object/@action/'.urlencode($name)).'">'.$name.'</a>
							<div class="pull-right">
								<button class="btn btn-primary btn-xs pull-right" data-toggle="collapse" data-target="#func_self_source_'.$name_html.'"><i class="fa fa-file-text-o"></i> source</button> '
								._class('core_api')->_github_link_btn($self_source)
							.'</div>
						</h1>
					</div>
					<div id="func_self_source_'.$name_html.'" class="panel-body collapse out"><pre class="prettyprint lang-php"><code>'._prepare_html($self_source['source']).'</code></pre></div> '
					.($target_source['source'] ? '<div id="func_target_source_'.$name_html.'" class="panel-body collapse out"><pre class="prettyprint lang-php"><code>'.(_prepare_html($target_source['source'])).'</code></pre></div> ' : '')
				.'</div>'
			;
			return implode(PHP_EOL, [$header, '<section class="page-contents">'.tpl()->parse_string($body, $replace, 'demo_'.$name).'</section>']);
		}
		$url = rtrim(url('/@object/@action/')).'/';
		$data = array();
		foreach ((array)$this->_get_demos($dir) as $name) {
			$data[$name] = array(
				'name'	=> $name,
				'link'	=> $url. urlencode($name),
			);
		}
		ksort($data);
		return html()->li($data);
	}

	/***/
	function _get_demos($dir = '') {
		$dir = $dir ?: _class('docs')->demo_dir;
		$dir_len = strlen($dir);
		$ext = '.php';
		$ext_len = strlen($ext);
		$names = array();
		foreach ((array)_class('dir')->rglob($dir) as $path) {
			if (substr($path, -$ext_len) !== $ext) {
				continue;
			}
			$name = substr($path, $dir_len, -$ext_len);
			$names[$name] = $name;
		}
		return $names;
	}
}