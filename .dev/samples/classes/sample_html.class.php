<?php

class sample_html {

	/***/
	function _init() {
		_class('core_api')->add_syntax_highlighter();
	}

	/***/
	function _hook_side_column($only_data = false) {
		$items = array();
		$url = url('/@object');
		$methods = get_class_methods(_class('html'));
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
				'name'	=> $name. (!in_array($name, $sample_methods) ? ' <sup class="text-error text-danger"><small>TODO</small></sup>' : ''),
				'link'	=> url('/@object/@action/'.$name), // '#head_'.$name,
			);
		}
		return $only_data ? $items : _class('html')->navlist($items);
	}

	/***/
	function show() {
		if (preg_match('~^[a-z0-9_]+$~ims', $_GET['id'])) {
			$only_method = strtolower($_GET['id']);
		}
		if (!$only_method) {
			return _class('html')->li($this->_hook_side_column($only_data = true));
		}
		$url = url('/@object');
		$methods = get_class_methods($this);
		sort($methods);
		foreach ((array)$methods as $name) {
			if ($name == 'show' || substr($name, 0, 1) == '_') {
				continue;
			}
			if ($only_method && $only_method !== $name) {
				continue;
			}
			$self_source	= _class('core_api')->get_method_source(__CLASS__, $name);
			$target_source	= _class('core_api')->get_method_source(_class('html'), $name);
			$target_docs	= _class('core_api')->get_method_docs('html', $name);

			$items[] = 
				'<div id="head_'.$name.'" style="margin-bottom: 30px;">
					<h1><a href="'.url('/@object/@action/'.$name).'">'.$name.'</a>
						<button class="btn btn-primary btn-small btn-sm" data-toggle="collapse" data-target="#func_self_source_'.$name.'">test '.$name.'() source</button> '
						.($target_source['source'] ? ' <button class="btn btn-primary btn-small btn-sm" data-toggle="collapse" data-target="#func_target_source_'.$name.'">_class("html")-&gt;'.$name.'() source</button> ' : '')
						._class('core_api')->get_github_link('html.'.$name)
						.($target_docs ? ' <button class="btn btn-primary btn-small btn-sm" data-toggle="collapse" data-target="#func_target_docs_'.$name.'">html::'.$name.' docs</button> ' : '')
					.'</h1>
					<div id="func_self_source_'.$name.'" class="collapse out"><pre class="prettyprint lang-php"><code>'._prepare_html($self_source['source']).'</code></pre></div> '
					.($target_source['source'] ? '<div id="func_target_source_'.$name.'" class="collapse out"><pre class="prettyprint lang-php"><code>'.(_prepare_html($target_source['source'])).'</code></pre></div> ' : '')
					.($target_docs ? '<div id="func_target_docs_'.$name.'" class="collapse out">'._class('html')->well(nl2br($target_docs)).'</div> ' : '')
					.'<div id="func_out_'.$name.'" class="row well well-lg" style="margin-left:0;">'.$this->$name().'</div>
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
			'2trees'		=> '<div class="span4">'.$this->tree().'</div>'.'<div class="span4">'.$this->tree().'</div>',
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
	function a() {
		return _class('html')->a('/docs/html', 'Block me', 'fa fa-lock');
	}

	/***/
	function dd_table() {
		$data = array(
			'first' 	=> 'first text',
			'second'	=> 'second text',
			'third'		=> 'Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. 
				Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. 
				Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. 
				Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably havent heard of them accusamus labore sustainable VHS.',
			'fourth'	=> '44444',
		);
		return _class('html')->dd_table($data, array());
	}

	/***/
	function simple_table() {
		$data = array(
			'first key' 	=> 'first text',
			'second key'	=> 'second text',
			'third key'		=> 'Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. 
				Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. 
				Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. 
				Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably havent heard of them accusamus labore sustainable VHS.',
			'fourth key'	=> '44444',
		);
		return _class('html')->simple_table($data, array(
			'key' => array(
				'func' => function($in) {
					return '<b>'.$in.'</b>';
				},
				'extra' => array(
					'width' => '20%',
				),
			),
			'tr' => function($row, $id) {
				return $id === 2 ? array('class' => 'success') : '';
			},
			'td' => function($row, $name, $row_id) {
				return $row_id === 1 && $name == 'key' ? array('class' => 'info') : '';
			},
		));
	}

	/***/
	function accordion() {
		$data = array(
			'first' 	=> array(
				'body'			=> 'first accordion item body<br />'.PHP_EOL.'first accordion item body',
				'class_group'	=> 'panel-info',
				'class_head'	=> 'alert-info',
			),
			'second'	=> array(
				'body'			=> 'second accordion item body<br />'.PHP_EOL.'second accordion item body',
				'class_group'	=> 'panel-danger',
				'class_head'	=> 'alert-error',
			),
			'third'		=> 'Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. 
				Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. 
				Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. 
				Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably havent heard of them accusamus labore sustainable VHS.',
			'fourth'	=> '44444 accordion item body<br />'.PHP_EOL.'second accordion item body',
		);
		return _class('html')->accordion($data, array('selected' => 'third', 'class' => 'span4 col-md-4'));
	}

	/***/
	function tabs() {
		$data = array(
			'first' 	=> 'first text',
			'second'	=> 'second text',
			'third'		=> 'Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. 
				Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. 
				Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. 
				Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably havent heard of them accusamus labore sustainable VHS.',
			'fourth'	=> '44444',
		);
		return _class('html')->tabs($data, array('selected' => 'third'));
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
		css('.carousel { max-width: 870px; }');
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
				'link'	=> url('/'),
				'name'	=> 'Title',
			),
			array(
				'link'	=> url('/home'),
				'name'	=> 'Home',
			),
			array(
				'link'	=> url('/link1'),
				'name'	=> 'Link1',
			),
			array(
				'link'	=> url('/link2'),
				'name'	=> 'Link2',
			),
		));
	}

	/***/
	function navlist() {
		return _class('html')->navlist(array(
			'brand'	=> array(
				'link'	=> url('/'),
				'name'	=> 'Title',
			),
			array(
				'link'	=> url('/home'),
				'name'	=> 'Home',
			),
			array(
				'link'	=> url('/link1'),
				'name'	=> 'Link1',
			),
			array(
				'link'	=> url('/link2'),
				'name'	=> 'Link2',
			),
		));
	}

	/***/
	function breadcrumbs() {
		return _class('html')->breadcrumbs(array(
			array(
				'link'	=> url('/home'),
				'name'	=> 'Home',
			),
			array(
				'link'	=> url('/library'),
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
#		$img = module('dynamic')->placeholder_img(array('width' => 300, 'height' => 200));
		$img = url('/dynamic/placeholder/300x200');

		return _class('html')->thumbnails(array(
			array(
				'img'	=> $img,
			),
			array(
				'img'	=> $img,
				'head'	=> 'Thumbnail label 4',
			),
			array(
				'img'	=> $img,
				'alt'	=> '300x200',
				'head'	=> 'Thumbnail label 1',
				'body'	=> '<p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
					<p><a href="#" class="btn btn-primary">Action</a> <a href="#" class="btn">Action</a></p>',
			),
			$img,
			array(
				'img'	=> $img,
				'alt'	=> '300x200',
				'head'	=> 'Thumbnail label 2',
				'body'	=> '<p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>',
			),
			$img,
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
			'1'	=> url('/some/action/1'),
			'2'	=> url('/some/action/2'),
			'prev'	=> url('/some/action/1'),
			'next'	=> url('/some/action/2'),
			'3'	=> url('/some/action/3'),
			'4'	=> url('/some/action/4'),
			'5'	=> url('/some/action/5'),
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
#		$img = module('dynamic')->placeholder_img(array('width' => 300, 'height' => 200));
		$img = url('/dynamic/placeholder/64x64');
		$body = 'Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.';
		$date = date('Y-m-d H:i:s');

		return _class('html')->media_objects(array(
			11 => array(
				'link'	=> url('/comments/view/11'),
				'img'	=> $img,
				'alt'	=> '64x64',
				'head'	=> 'Comment 1',
				'body'	=> $body,
				'date'	=> $date,
			),
			22 => array(
				'link'	=> url('/comments/view/22'),
				'img'	=> $img,
				'alt'	=> '64x64',
				'head'	=> 'Comment 2',
				'body'	=> $body,
				'date'	=> $date,
			),
			33 => array(
				'link'	=> url('/comments/view/33'),
				'img'	=> $img,
				'alt'	=> '64x64',
				'head'	=> 'Comment 3',
				'body'	=> $body,
				'parent_id'	=> 22,
			),
			44 => array(
				'link'	=> url('/comments/view/44'),
				'img'	=> $img,
				'body'	=> $body,
				'date'	=> $date,
				'parent_id'	=> 33,
			),
			55 => array(
				'img'	=> $img,
				'body'	=> $body,
				'parent_id'	=> 44,
			),
			66 => array(
				'link'	=> url('/comments/view/66'),
				'img'	=> $img,
				'alt'	=> '64x64',
				'head'	=> 'Comment 6',
				'body'	=> $body,
				'date'	=> $date,
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
				'link'		=> url('/blocks'),
				'name'		=> 'Blocks editor',
				'parent_id'	=> 11,
			),
			33 => array(
				'link'		=> url('/file_manager'),
				'name'		=> 'File manager',
				'parent_id'	=> 11,
			),
			44 => array(
				'name'		=> 'Administration',
			),
			55 => array(
				'link'		=> url('/admin'),
				'name'		=> 'Admin accounts',
				'parent_id'	=> 44,
			),
			66 => array(
				'link'		=> url('/admin_groups'),
				'name'		=> 'Admin groups',
				'parent_id'	=> 44,
			),
			77 => array(
				'link'		=> url('/admin_modules'),
				'name'		=> 'Admin modules',
				'parent_id'	=> 44,
			),
			88 => array(
				'name'		=> 'Users',
				'parent_id'	=> 44,
			),
			99 => array(
				'link'		=> url('/manage_users'),
				'name'		=> 'User accounts',
				'parent_id'	=> 88,
			),
			101 => array(
				'link'		=> url('/user_groups'),
				'name'		=> 'User groups',
				'parent_id'	=> 88,
			),
			102 => array(
				'link'		=> url('/user_modules'),
				'name'		=> 'User modules',
				'parent_id'	=> 88,
			),
			103 => array(
				'name'		=> 'Content',
			),
			104 => array(
				'link'		=> url('/static_pages'),
				'name'		=> 'Static pages',
				'parent_id'	=> 103,
			),
			105 => array(
				'link'		=> url('/manage_news'),
				'name'		=> 'News',
				'parent_id'	=> 103,
			),
			106 => array(
				'link'		=> url('/manage_comments'),
				'name'		=> 'Comments',
				'parent_id'	=> 103,
			),
		));
	}

	/***/
	function tree() {
		return _class('html')->tree(array(
			11 => array(
				'name'	=> 'Tools',
			),
			22 => array(
				'link'		=> url('/blocks'),
				'name'		=> 'Blocks editor',
				'parent_id'	=> 11,
			),
			33 => array(
				'link'		=> url('/file_manager'),
				'name'		=> 'File manager',
				'parent_id'	=> 11,
			),
			44 => array(
				'name'		=> 'Administration',
			),
			55 => array(
				'link'		=> url('/admin'),
				'name'		=> 'Admin accounts',
				'parent_id'	=> 44,
			),
			66 => array(
				'link'		=> url('/admin_groups'),
				'name'		=> 'Admin groups',
				'parent_id'	=> 44,
			),
			77 => array(
				'link'		=> url('/admin_modules'),
				'name'		=> 'Admin modules',
				'parent_id'	=> 44,
			),
			88 => array(
				'name'		=> 'Users',
				'parent_id'	=> 44,
			),
			99 => array(
				'link'		=> url('/manage_users'),
				'name'		=> 'User accounts',
				'parent_id'	=> 88,
			),
			101 => array(
				'link'		=> url('/user_groups'),
				'name'		=> 'User groups',
				'parent_id'	=> 88,
			),
			102 => array(
				'link'		=> url('/user_modules'),
				'name'		=> 'User modules',
				'parent_id'	=> 88,
			),
			103 => array(
				'name'		=> 'Content',
			),
			104 => array(
				'link'		=> url('/static_pages'),
				'name'		=> 'Static pages',
				'parent_id'	=> 103,
			),
			105 => array(
				'link'		=> url('/manage_news'),
				'name'		=> 'News',
				'parent_id'	=> 103,
			),
			106 => array(
				'link'		=> url('/manage_comments'),
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