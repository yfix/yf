<?php

/**
* Absttraction layer over HTML5/CSS frameworks.
* Planned support for these plugins: 
*	Bootstrap 2		http://twbs.github.io/bootstrap/2.3.2/
*	Bootstrap 3		http://twbs.github.io/bootstrap/3
*	Zurb Foundation	http://foundation.zurb.com/
*	Pure CSS		http://purecss.io/
*/
class yf_html {

	/**
	* Catch missing method call
	*/
	function __call($name, $arguments) {
		trigger_error(__CLASS__.': No method '.$name, E_USER_WARNING);
		return false;
	}

	/**
	* We cleanup object properties when cloning
	*/
	function __clone() {
		foreach ((array)get_object_vars($this) as $k => $v) {
			$this->$k = null;
		}
	}

	/**
	* Need to avoid calling render() without params
	*/
	function __toString() {
		return $this->render();
	}

	/**
	* Wrapper for template engine
	* Example:
	*	return html()->dd_table(db()->get_2d('SELECT * FROM '.db('countries')));
	*/
	function chained_wrapper($params = array()) {
		$this->_chained_mode = true;
		$this->_params = $params;
		return $this;
	}

	/**
	*/
	function dd_table($replace = array(), $field_types = array(), $extra = array()) {
		if (DEBUG_MODE) {
			$ts = microtime(true);
		}
		$form = form($replace, array(
			'legend' => $replace['title'],
			'no_form' => 1,
			'dd_mode' => 1,
			'dd_class' => 'span6 col-lg-6',
		));
		foreach ((array)$replace as $name => $val) {
			$func = 'container';
			$_extra = array(
				'desc' => $name,
				'value' => $val,
			);
			$ft = $field_types[$name];
			if (isset($ft)) {
				if (is_array($ft)) {
					if (isset($ft['func'])) {
						$func = $ft['func'];
					}
					$_extra = (array)$ft + $_extra;
				} else {
					$func = $ft;
				}
			}
			$_extra += (array)$extra;
			// Callback to decide if we need to show this field or not
			if (isset($_extra['display_func']) && is_callable($_extra['display_func'])) {
				$_display_allowed = $_extra['display_func']($val, $_extra);
				if (!$_display_allowed) {
					continue;
				}
			}
			if ($func) {
				$form->$func($val, $_extra);
			}
		}
		$legend = $extra['legend'] ? '<legend>'._prepare_html(t($extra['legend'])).'</legend>' : '';
		$div_class = $extra['div_class'] ? $extra['div_class'] : 'span6 col-lg-6';
		if (DEBUG_MODE) {
			debug('dd_table[]', array(
				'fields'		=> $replace,
				'field_types'	=> $field_types,
				'extra'			=> $extra,
				'time'			=> round(microtime(true) - $ts, 5),
				'trace'			=> main()->trace_string(),
			));
		}
		return '<div class="row-fluid">'.$legend.'<div class="'.$div_class.'">'.$form.'</div></div>';
	}

	/**
	*/
	function modal ($extra = array()) {
		$def_style = $extra['inline'] ? 'position: relative; top: auto; left: auto; right: auto; margin: 0 auto 20px; z-index: 1; max-width: 100%;' : '';
		$extra['style'] = $extra['style'] ?: $def_style;
		return '
			<div class="modal" style="'.$extra['style'].'">
				<div class="modal-header">'
					.($extra['show_close'] ? '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' : '')
					.($extra['header'] ? '<h3>'.$extra['header'].'</h3>' : '')
				.'</div>
				<div class="modal-body">'.$extra['body'].'</div>'
				.($extra['footer'] ? '<div class="modal-footer">'.$extra['footer'].'</div>' : '')
			.'</div>';
	}

	/**
	*/
	function tabs ($tabs = array(), $extra = array()) {
		$headers = array();
		$items = array();
		foreach ((array)$tabs as $k => $v) {
			if (!is_array($v)) {
				$content = $v;
				$v = array();
			} else {
				$content = $v['content'];
			}
			$name = $v['name'] ?: $k;
			$desc = $v['desc'] ?: ucfirst(str_replace('_', ' ', $name));
			$id = $v['id'] ?: 'tab_'.$k;
			if (isset($extra['selected'])) {
				$is_active = ($extra['selected'] == $k);
			} else {
				$is_active = (++$i == 1);
			}
			$css_class = ($is_active || $extra['show_all']) ? 'active' : 'fade';
			if ($extra['class']) {
				$css_class .= ' '.$extra['class'];
			}
			$class_head = $v['class_head'] ?: $extra['class_head'];
			$class_body = $v['class_body'] ?: $extra['class_body'];
			if (!$extra['no_headers']) {
				$headers[] = '<li class="'.($is_active ? 'active' : ''). ($class_head ? ' '.$class_head : '').'"><a href="#'.$id.'" data-toggle="tab">'.t($desc).'</a></li>';
			}
			$items[] = '<div class="tab-pane '.$css_class. ($class_body ? ' '.$class_body : '').'" id="'.$id.'">'.$content.'</div>';
		}
		$extra['id'] = $extra['id'] ?: 'tabs_'.substr(md5(microtime()), 0, 8);
		$body .= $headers ? '<ul id="'.$extra['id'].'" class="nav nav-tabs">'.implode(PHP_EOL, (array)$headers). '</ul>'. PHP_EOL : '';
		$body .= '<div id="'.$extra['id'].'_content" class="tab-content">'. implode(PHP_EOL, (array)$items).'</div>';
		return $body;
	}

	/**
	*/
	function accordion ($data = array(), $extra = array()) {
		$items = array();
		$extra['id'] = $extra['id'] ?: 'accordion_'.substr(md5(microtime()), 0, 8);
		foreach ((array)$data as $k => $v) {
			if (!is_array($v)) {
				$content = $v;
				$v = array();
			} else {
				$content = $v['content'];
			}
			$name = $v['name'] ?: $k;
			$desc = $v['desc'] ?: ucfirst(str_replace('_', ' ', $name));
			$id = $v['id'] ?: 'accordion_item_'.$k;
			if (isset($extra['selected'])) {
				$is_selected = ($extra['selected'] == $k);
			} else {
				$is_selected = (++$i == 1);
			}
			$class_group = $v['class_group'] ?: $extra['class_group'];
			$class_head = $v['class_head'] ?: $extra['class_head'];
			$class_body = $v['class_body'] ?: $extra['class_body'];

			$items[] = 
				'<div class="accordion-group'.($class_group ? ' '.$class_group : '').'">
					<div class="accordion-heading'.($class_head ? ' '.$class_head : '').'">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#'.$extra['id'].'" href="#'.$id.'">'.$desc.'</a>
					</div>
					<div id="'.$id.'" class="accordion-body collapse'.($is_selected ? ' in' : ''). ($class_body ? ' '.$class_body : '').'">
						<div class="accordion-inner">'.$content.'</div>
					</div>
				</div>';
		}
		return '<div class="accordion'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">'.implode(PHP_EOL, (array)$items).'</div>';
	}

	/**
	*/
	function carousel ($data = array(), $extra = array()) {
		$items = array();
		$headers = array();
		$extra['id'] = $extra['id'] ?: 'caroousel_'.substr(md5(microtime()), 0, 8);
		foreach ((array)$data as $k => $v) {
			if (!is_array($v)) {
				$img_src = $v;
				$v = array();
			} else {
				$img_src = $v['img'];
			}
			$desc = $v['desc'];
			$alt = $v['alt'] ?: strip_tags($desc);
			$id = $v['id'] ?: 'carousel_item_'.$k;
			if (isset($extra['selected'])) {
				$is_active = ($extra['selected'] == $k);
			} else {
				$is_active = (++$i == 1);
			}
			$class_head = $v['class_head'] ?: $extra['class_head'];
			$class_body = $v['class_body'] ?: $extra['class_body'];

			$headers[] = '<li data-target="#'.$extra['id'].'" data-slide-to="'.($i - 1).'" class="'.($is_active ? 'active' : ''). ($class_head ? ' '.$class_head : '').'"></li>';
			$items[] = 
				'<div class="item'.($is_active ? ' active' : ''). ($class_body ? ' '.$class_body : '').'">
					<img src="'.$img_src.'" alt="'.$alt.'">
					'.($desc ? '<div class="carousel-caption">'.$desc.'</div>' : '').'
				</div>';
		}
		$controls = '
			<a class="left carousel-control" href="#'.$extra['id'].'" data-slide="prev">‹</a>
			<a class="right carousel-control" href="#'.$extra['id'].'" data-slide="next">›</a>
		';
		return '<div id="'.$extra['id'].'" class="carousel slide'.($extra['class'] ? ' '.$extra['class'] : '').'">
				<ol class="carousel-indicators">'.implode(PHP_EOL, $headers).'</ol>
				<div class="carousel-inner">'.implode(PHP_EOL, $items).'</div>
				'.(!$extra['no_controls'] ? $controls : '').'
			</div>';
	}

	/**
	*/
	function alert ($data = array(), $extra = array()) {
		$close_btn = (!$extra['no_close'] && !$data['no_close']) ? '<button type="button" class="close" data-dismiss="alert">×</button>' : '';
		$head = is_array($data) ? $data['head'] : '';
		$body = is_array($data) ? $data['body'] : $data;
		$alert_type = $extra['alert'] ?: (is_array($data) ? $data['alert'] : '');
		if (!$alert_type) {
			$alert_type = 'error';
		}
		return '
			<div class="alert alert-block alert-'.$alert_type.' fade in'.($extra['class'] ? ' '.$extra['class'] : '').'">
				'.$close_btn.'
				'.($head ? '<h4 class="alert-heading">'.$head.'</h4>' : '').'
				'.$body.'
			</div>';
	}

	/**
	*/
	function navbar ($data = array()) {
// TODO
		return '
			<div class="navbar">
				<div class="navbar-inner">
					<a class="brand" href="#">Title</a>
					<ul class="nav">
						<li class="active"><a href="#">Home</a></li>
						<li><a href="#">Link</a></li>
						<li><a href="#">Link</a></li>
					</ul>
				</div>
			</div>';
	}

	/**
	*/
	function breadcrumbs ($data = array()) {
// TODO
		return '
			<ul class="breadcrumb">
				<li><a href="#">Home</a> <span class="divider">/</span></li>
				<li><a href="#">Library</a> <span class="divider">/</span></li>
				<li class="active">Data</li>
			</ul>';
	}

	/**
	*/
	function pagination ($data = array()) {
// TODO
		return '
			<div class="pagination">
				<ul>
					<li><a href="#">Prev</a></li>
					<li><a href="#">1</a></li>
					<li><a href="#">2</a></li>
					<li><a href="#">3</a></li>
					<li><a href="#">4</a></li>
					<li><a href="#">5</a></li>
					<li><a href="#">Next</a></li>
				</ul>
			</div>';
	}

	/**
	*/
	function thumbnails ($data = array()) {
// TODO
		return '
				<ul class="thumbnails">
					<li class="span4">
						<div class="thumbnail">
							<img alt="300x200" src="http://placehold.it/300x200" style="width: 300px; height: 200px;">
							<div class="caption">
								<h3>Thumbnail label</h3>
								<p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
								<p><a href="#" class="btn btn-primary">Action</a> <a href="#" class="btn">Action</a></p>
							</div>
						</div>
					</li>
					<li class="span4">
						<div class="thumbnail">
							<img alt="300x200" src="http://placehold.it/300x200" style="width: 300px; height: 200px;">
							<div class="caption">
								<h3>Thumbnail label</h3>
								<p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
								<p><a href="#" class="btn btn-primary">Action</a> <a href="#" class="btn">Action</a></p>
							</div>
						</div>
					</li>
					<li class="span4">
						<div class="thumbnail">
							<img alt="300x200" src="http://placehold.it/300x200" style="width: 300px; height: 200px;">
							<div class="caption">
								<h3>Thumbnail label</h3>
								<p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
								<p><a href="#" class="btn btn-primary">Action</a> <a href="#" class="btn">Action</a></p>
							</div>
						</div>
					</li>
				</ul>
';
	}

	/**
	*/
	function media_objects ($data = array()) {
// TODO
		return '
			<div class="bs-docs-example">
				<div class="media">
					<a class="pull-left" href="#">
						<img class="media-object" alt="64x64" src="http://placehold.it/64x64" style="width: 64px; height: 64px;">
					</a>
					<div class="media-body">
						<h4 class="media-heading">Media heading</h4>
						Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.
					</div>
				</div>
				<div class="media">
					<a class="pull-left" href="#">
						<img class="media-object" alt="64x64" src="http://placehold.it/64x64" style="width: 64px; height: 64px;">
					</a>
					<div class="media-body">
						<h4 class="media-heading">Media heading</h4>
						Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.

						<div class="media">
							<a class="pull-left" href="#">
								<img class="media-object" alt="64x64" src="http://placehold.it/64x64" style="width: 64px; height: 64px;">
							</a>
							<div class="media-body">
								<h4 class="media-heading">Media heading</h4>
								Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.
							</div>
						</div>
					</div>
				</div>
			</div>';
	}

	/**
	*/
	function progress_bar ($data = array()) {
// TODO
		return '
			<div class="progress">
				<div class="bar bar-success" style="width: 35%;"></div>
				<div class="bar bar-warning" style="width: 20%;"></div>
				<div class="bar bar-danger" style="width: 10%;"></div>
			</div>';
	}

	/**
	*/
	function grid ($data = array()) {
// TODO
		return '
			<div class="bs-docs-grid">
				<div class="row-fluid show-grid">
					<div class="span1">1</div>
					<div class="span1">1</div>
					<div class="span1">1</div>
					<div class="span1">1</div>
					<div class="span1">1</div>
					<div class="span1">1</div>
					<div class="span1">1</div>
					<div class="span1">1</div>
					<div class="span1">1</div>
					<div class="span1">1</div>
					<div class="span1">1</div>
					<div class="span1">1</div>
				</div>
				<div class="row-fluid show-grid">
					<div class="span4">4</div>
					<div class="span4">4</div>
					<div class="span4">4</div>
				</div>
				<div class="row-fluid show-grid">
					<div class="span4">4</div>
					<div class="span8">8</div>
				</div>
				<div class="row-fluid show-grid">
					<div class="span6">6</div>
					<div class="span6">6</div>
				</div>
				<div class="row-fluid show-grid">
					<div class="span12">12</div>
				</div>
			</div>';
	}

	/**
	*/
	function panel ($data = array()) {
// bs3+
// TODO
		return '
			<div class="panel panel-primary">
				<div class="panel-heading">
					<h3 class="panel-title">Panel title</h3>
				</div>
				<div class="panel-body">
					Panel content
				</div>
			</div>
		';
	}

	/**
	*/
	function list_group ($data = array()) {
// bs3+
// TODO
		return '
			<ul class="list-group">
				<li class="list-group-item">
					<span class="badge">14</span>
					Cras justo odio
				</li>
				<li class="list-group-item active">
					<span class="badge">2</span>
					Dapibus ac facilisis in
				</li>
				<li class="list-group-item list-group-item-warning">
					<span class="badge">1</span>
					Morbi leo risus
				</li>
			</ul>
		';
	}

	/**
	*/
	function jumbotron ($data = array()) {
// bs3+
// TODO
		return '
			<div class="jumbotron">
				<h1>Hello, world!</h1>
				<p>This is a simple hero unit, a simple jumbotron-style component for calling extra attention to featured content or information.</p>
				<p><a class="btn btn-primary btn-lg" role="button">Learn more</a></p>
			</div>
		';
	}

	/**
	*/
	function well ($data = array()) {
// bs3+
// TODO
		return '
			<div class="well well-lg">
				in a large well!
			</div>
		';
	}
}
