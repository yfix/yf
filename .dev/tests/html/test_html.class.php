<?php

class test_html {

	/***/
	public $data = array(
		'first' 	=> 'first text',
		'second'	=> 'second text',
		'third'		=> 'Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. 
			Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. 
			Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. 
			Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably havent heard of them accusamus labore sustainable VHS.',
		'fourth'	=> '44444',
	);

	/***/
	function _hook_side_column() {
		$items = array();
		$url = process_url('./?object='.$_GET['object']);
		$methods = get_class_methods($this);
		sort($methods);
		foreach ((array)$methods as $name) {
			if ($name == 'show' || substr($name, 0, 1) == '_') {
				continue;
			}
			$items[] = '<li><a href="#head_'.$name.'"><i class="icon-chevron-right"></i> '.t($name).'</a></li>';
		}
		return '<div class="bs-docs-sidebar"><ul class="nav nav-list bs-docs-sidenav">'.implode(PHP_EOL, $items).'</ul></div>';
	}

	/***/
	function _get_method_source($cls, $method) {
		if (is_object($cls)) {
			$cls = get_class($cls);
		}
		$methods = $this->_cache[__FUNCTION__][$cls];
		if (is_null($methods)) {
			$methods = $this->_get_methods_source($cls);
			$this->_cache[__FUNCTION__][$cls] = $methods;
		}
		return $methods[$method];
	}

	/***/
	function _get_methods_source($cls) {
		if (is_object($cls)) {
			$cls = get_class($cls);
		}
		$data = array();
		$class = new ReflectionClass($cls);
		foreach ($class->getMethods() as $v) {
			$name = $v->name;
			if ($name == 'show' || substr($name, 0, 1) == '_') {
				continue;
			}
			preg_match('~@@\s+(?P<file>.+?)\s+(?P<line1>[0-9]+)\s+-\s+(?P<line2>[0-9]+)$~ims', ReflectionMethod::export($cls, $name, 1), $m);
			$file = trim($m['file']);
			$line_start = intval($m['line1'] - 1);
			$line_end = intval($m['line2'] + 1);
			$data[$name] = array(
				'body'		=> $this->_get_file_slice($file, $line_start, $line_end),
				'file'		=> $file,
				'line_start'=> $line_start,
				'line_end'	=> $line_end,
			);
		}
		return $data;
	}

	/***/
	function _get_file_slice($file, $line_start, $line_end) {
		$source = $this->_cache[__FUNCTION__][$file];
		if (is_null($source)) {
			$source = file($file);
			$this->_cache[__FUNCTION__][$file] = $source;
		}
		$offset = $line_end - $line_start;
		return implode(array_slice($source, $line_start, $offset));
	}

	/***/
	function show() {
#		require_js('//cdnjs.cloudflare.com/ajax/libs/prettify/r298/run_prettify.js?autoload=true&amp;skin=desert');
#		require_css('<style>pre.prettyprint { font-weight: bold; }</style>');

		require_js('//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.0/highlight.min.js');
		require_js('//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.0/languages/php.min.js');
		require_js('<script>hljs.initHighlightingOnLoad();</script>');
		require_css('//cdnjs.cloudflare.com/ajax/libs/highlight.js/8.0/styles/railscasts.min.css');
		require_css('<style>pre.prettyprint { background-color: transparent; border: 0;} pre.prettyprint code { font-family: monospace; }</style>');

		$url = process_url('./?object='.$_GET['object']);
		$methods = get_class_methods($this);
		sort($methods);
		foreach ((array)$methods as $name) {
			if ($name == 'show' || substr($name, 0, 1) == '_') {
				continue;
			}
			$self_source = $this->_get_method_source(__CLASS__, $name);
			$target_source = $this->_get_method_source(_class('html'), $name);
			$items[] = 
				'<div id="head_'.$name.'" style="margin-bottom: 30px;">
					<h1>'.$name.'
						<button class="btn btn-primary btn-small btn-sm" data-toggle="collapse" data-target="#func_self_source_'.$name.'">test '.$name.'() source</button>
						'.($target_source['body'] ? '<button class="btn btn-primary btn-small btn-sm" data-toggle="collapse" data-target="#func_target_source_'.$name.'">_class("html")-&gt;'.$name.'() source</button>' : '').'
						'.($target_source['body'] ? '<a target="_blank" class="btn btn-primary btn-small btn-sm" href="https://github.com/yfix/yf/tree/master/'.substr($target_source['file'], strlen(YF_PATH)).'#L'.$target_source['line_start'].'">Github <i class="icon icon-github"></i></a>' : '').'
					</h1>
					<div id="func_self_source_'.$name.'" class="collapse out"><pre class="prettyprint lang-php"><code>'.(_prepare_html($self_source['body'])).'</code></pre></div>
					'.($target_source['body'] ? '<div id="func_target_source_'.$name.'" class="collapse out"><pre class="prettyprint lang-php"><code>'.(_prepare_html($target_source['body'])).'</code></pre></div>' : '').'
					<div id="func_out_'.$name.'" class="row well well-lg" style="margin-left:0;">'.$this->$name().'</div>
				</div>';
		}
		return implode(PHP_EOL, $items);
	}

	/***/
	function complex_test() {
		return _class('html')->tabs(array(
			'thumbnails'	=> $this->thumbnails(),
			'media_objects'	=> $this->media_objects(),
			'carousel'		=> $this->carousel(),
			'menu'			=> '<div style="min-height:200px;">'.$this->menu().'</div>',
			'dd_table'		=> $this->dd_table(),
			'accordion'		=> _class('html')->accordion(array(
				'modal'			=> $this->modal(),
				'navbar' 		=> $this->navbar(),
				'breadcrumbs'	=> $this->breadcrumbs(),
				'pagination'	=> $this->pagination(),
			), array('class_head' => 'alert-error')),
#			'table'			=> _class('form2_stacked_sample', YF_PATH.'.dev/tests/form2/')->show(),
#			'form'			=> _class('table2_new_controls', YF_PATH.'.dev/tests/table2/')->show(),
		));
	}

	/***/
	function dd_table() {
		return _class('html')->dd_table($this->data, array());
	}

	/***/
	function accordion() {
		$data = $this->data;
		$data['first'] = array(
			'body'	=> $data['first'],
			'class_group'	=> 'panel-info',
			'class_head'	=> 'alert-info',
		);
		$data['second'] = array(
			'body'	=> $data['second'],
			'class_group'	=> 'panel-danger',
			'class_head'	=> 'alert-error',
		);
		return _class('html')->accordion($data, array('selected' => 'third', 'class' => 'span4 col-lg-4'));
	}

	/***/
	function tabs() {
		return _class('html')->tabs($this->data, array('selected' => 'third'));
	}

	/***/
	function modal() {
		return _class('html')->modal(array(
			'inline'		=> 1,
			'show_close'	=> 1,
			'header'		=> 'Modal header',
			'body'			=> '<p>Some body</p>',
			'footer'		=> form_item()->save(),
		));
	}

	/***/
	function carousel() {
		return _class('html')->carousel(array(
			array(
				'img'	=> '//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-01.jpg',
				'desc'	=> '<h4>First Thumbnail label</h4><p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>',
			),
			array(
				'img'	=> '//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-02.jpg',
				'desc'	=> '<h4>Second Thumbnail label</h4><p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>',
			),
			array(
				'img'	=> '//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-03.jpg',
				'desc'	=> '<h4>Third Thumbnail label</h4><p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>',
			),
			'//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-01.jpg',
			'//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-02.jpg',
			'//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-03.jpg',
		));
	}

	/***/
	function navbar() {
		return _class('html')->navbar(array(
			'brand'	=> array(
				'link'	=> './',
				'name'	=> 'Title',
			),
			array(
				'link'	=> './?object=home',
				'name'	=> 'Home',
			),
			array(
				'link'	=> './?object=link1',
				'name'	=> 'Link1',
			),
			array(
				'link'	=> './?object=link2',
				'name'	=> 'Link2',
			),
		));
	}

	/***/
	function breadcrumbs() {
		return _class('html')->breadcrumbs(array(
			array(
				'link'	=> './?object=home',
				'name'	=> 'Home',
			),
			array(
				'link'	=> './?object=library',
				'name'	=> 'Library',
			),
			array(
				'name'	=> 'Data',
			),
		));
	}

	/***/
	function alert() {
		return _class('html')->alert(array(
			'head'	=> 'Oh snap! You got an error!',
			'body'	=> '<p>Change this and that and try again. Duis mollis, est non commodo luctus, nisi erat porttitor ligula, eget lacinia odio sem nec elit. Cras mattis consectetur purus sit amet fermentum.</p>
				<p><a class="btn btn-danger btn-default" href="#">Take this action</a> <a class="btn btn-default" href="#">Or do this</a></p>',
		))
		. _class('html')->alert(array(
			'alert'	=> 'info',
			'head'	=> 'Oh snap! You got an error!',
			'body'	=> '<p>Change this and that and try again. Duis mollis, est non commodo luctus, nisi erat porttitor ligula, eget lacinia odio sem nec elit. Cras mattis consectetur purus sit amet fermentum.</p>
				<p><a class="btn btn-danger btn-default" href="#">Take this action</a> <a class="btn btn-default" href="#">Or do this</a></p>',
		));
	}

	/***/
	function thumbnails() {
		return _class('html')->thumbnails(array(
			array(
				'img'	=> 'http://placehold.it/300x200',
			),
			array(
				'img'	=> 'http://placehold.it/300x200',
				'head'	=> 'Thumbnail label 4',
			),
			array(
				'img'	=> 'http://placehold.it/300x200',
				'alt'	=> '300x200',
				'head'	=> 'Thumbnail label 1',
				'body'	=> '<p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
					<p><a href="#" class="btn btn-primary">Action</a> <a href="#" class="btn">Action</a></p>',
			),
			'http://placehold.it/300x200',
			array(
				'img'	=> 'http://placehold.it/300x200',
				'alt'	=> '300x200',
				'head'	=> 'Thumbnail label 2',
				'body'	=> '<p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>',
			),
			'http://placehold.it/300x200',
		), array('columns' => 3));
	}

	/***/
	function progress_bar() {
		return _class('html')->progress_bar(array(
			'35',
			array(
				'val'	=> '20',
				'type'	=> 'warning',
			),
			array(
				'val'	=> '10',
				'type'	=> 'info',
			),
		), array('type' => 'success'));
	}

	/***/
	function pagination() {
		return _class('html')->pagination(array(
			'1'	=> './?object=some&id=1',
			'2'	=> './?object=some&id=2',
			'prev'	=> './?object=some&id=1',
			'next'	=> './?object=some&id=2',
			'3'	=> './?object=some&id=3',
			'4'	=> './?object=some&id=4',
			'5'	=> './?object=some&id=5',
		));
	}

	/***/
	function panel() {
		return _class('html')->panel(array('title' => 'Panel title', 'body' => 'Panel content'));
	}

	/***/
	function jumbotron() {
		return _class('html')->jumbotron(array(
			'head'	=> 'My big header',
			'body'	=> '<p>This is a simple hero unit, a simple jumbotron-style component for calling extra attention to featured content or information.</p>
				<p><a class="btn btn-primary btn-lg" role="button">Learn more</a></p>',
		));
	}

	/***/
	function well() {
		return _class('html')->well('Large well content');
	}

	/***/
	function list_group() {
		return _class('html')->list_group(array(
			'First line',
			array(
				'body'	=> 'Cras justo odio',
				'badge'	=> '14',
			),
			array(
				'body'	=> 'Dapibus ac facilisis in',
				'badge'	=> '2',
				'class_item'	=> 'active'
			),
			array(
				'body'	=> 'Morbi leo risus',
				'badge'	=> '1',
				'class_item'	=> 'list-group-item-warning',
			),
		));
	}

	/***/
	function media_objects() {
		return _class('html')->media_objects(array(
			11 => array(
				'link'	=> './?object=comments&action=view&id=11',
				'img'	=> 'http://placehold.it/64x64',
				'alt'	=> '64x64',
				'head'	=> 'Comment 1',
				'body'	=> 'Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.',
				'date'	=> date('Y-m-d H:i:s'),
			),
			22 => array(
				'link'	=> './?object=comments&action=view&id=22',
				'img'	=> 'http://placehold.it/64x64',
				'alt'	=> '64x64',
				'head'	=> 'Comment 2',
				'body'	=> 'Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.',
				'date'	=> date('Y-m-d H:i:s'),
			),
			33 => array(
				'link'	=> './?object=comments&action=view&id=33',
				'img'	=> 'http://placehold.it/64x64',
				'alt'	=> '64x64',
				'head'	=> 'Comment 1',
				'body'	=> 'Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.',
				'parent_id'	=> 22,
			),
			44 => array(
				'link'	=> './?object=comments&action=view&id=44',
				'img'	=> 'http://placehold.it/64x64',
				'body'	=> 'Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.',
				'date'	=> date('Y-m-d H:i:s'),
				'parent_id'	=> 33,
			),
			55 => array(
				'img'	=> 'http://placehold.it/64x64',
				'body'	=> 'Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.',
				'parent_id'	=> 44,
			),
			66 => array(
				'link'	=> './?object=comments&action=view&id=66',
				'img'	=> 'http://placehold.it/64x64',
				'alt'	=> '64x64',
				'head'	=> 'Comment 1',
				'body'	=> 'Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.',
				'date'	=> date('Y-m-d H:i:s'),
			),
		));
	}

	/***/
	function menu() {
		return _class('html')->menu(array(
			11 => array(
				'name'	=> 'Tools',
			),
			22 => array(
				'link'		=> './?object=blocks',
				'name'		=> 'Blocks editor',
				'parent_id'	=> 11,
			),
			33 => array(
				'link'		=> './?object=file_manager',
				'name'		=> 'File manager',
				'parent_id'	=> 11,
			),
			44 => array(
				'name'		=> 'Administration',
			),
			55 => array(
				'link'		=> './?object=admin',
				'name'		=> 'Admin accounts',
				'parent_id'	=> 44,
			),
			66 => array(
				'link'		=> './?object=admin_groups',
				'name'		=> 'Admin groups',
				'parent_id'	=> 44,
			),
			77 => array(
				'link'		=> './?object=admin_modules',
				'name'		=> 'Admin modules',
				'parent_id'	=> 44,
			),
			88 => array(
				'name'		=> 'Users',
				'parent_id'	=> 44,
			),
			99 => array(
				'link'		=> './?object=manage_users',
				'name'		=> 'User accounts',
				'parent_id'	=> 88,
			),
			101 => array(
				'link'		=> './?object=user_groups',
				'name'		=> 'User groups',
				'parent_id'	=> 88,
			),
			102 => array(
				'link'		=> './?object=user_modules',
				'name'		=> 'User modules',
				'parent_id'	=> 88,
			),
			103 => array(
				'name'		=> 'Content',
			),
			104 => array(
				'link'		=> './?object=static_pages',
				'name'		=> 'Static pages',
				'parent_id'	=> 103,
			),
			105 => array(
				'link'		=> './?object=manage_news',
				'name'		=> 'News',
				'parent_id'	=> 103,
			),
			106 => array(
				'link'		=> './?object=manage_comments',
				'name'		=> 'Comments',
				'parent_id'	=> 103,
			),
		));
	}

	/***/
	function grid() {
		return _class('html')->grid(array(
			array(
				array('s1'),
				array('s1'),
				array('s1'),
				array('s1'),
				array('s1'),
				array('s1'),
				array('s1'),
				array('s1', 'class' => 'btn btn-warning'),
				array('s1'),
				array('s1', 'class' => 'btn btn-primary'),
				array('s1'),
				array('s1'),
			),
			array(
				array('s4'),
				array('s4'),
				array('s4'),
			),
			array(
				array('s4', 'col' => 4, 'class' => 'alert alert-error'),
				array('s8', 'col' => 8, 'class' => 'alert alert-info'),
			),
			array(
				array('s6'),
				array('s6'),
			),
			array(
				array('s12'),
			),
		));
	}
}