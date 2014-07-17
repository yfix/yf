<?php

/**
* HTML high-level controls collection
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_html {

	/** @var bool */
	public $AUTO_ASSIGN_IDS = true;
	/** @var bool */
	public $BOXES_USE_STPL	= true;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* We cleanup object properties when cloning
	*/
	function __clone() {
		foreach ((array)get_object_vars($this) as $k => $v) {
			if ($k[0] == '_') {
				$this->$k = null;
			}
		}
	}

	/**
	* Need to avoid calling render() without params
	*/
	function __toString() {
		return $this->render();
	}

	/**
	*/
	function _init() {
		$this->_is_bs3 = (conf('css_framework') == 'bs3');
	}

	/**
	* Get and sort items ordered array (recursively)
	*/
	function _recursive_sort_items($items = array(), $skip_item_id = 0, $parent_id = 0) {
		$children = array();
		foreach ((array)$items as $id => $info) {
			$parent_id = (int)$info['parent_id'];
			if ($skip_item_id == $id) {
				continue;
			}
			$children[$parent_id][$id] = $id;
		}
		$ids = $this->_count_levels(0, $children);
		$new_items = array();
		foreach ((array)$ids as $id => $level) {
			$new_items[$id] = $items[$id] + array('level' => $level);
		}
		return $new_items;
	}

	/**
	*/
	function _count_levels($start_id = 0, &$children, $level = 0) {
		$ids = array();
		foreach ((array)$children[$start_id] as $id => $_tmp) {
			$ids[$id] = $level;
			if (isset($children[$id])) {
				foreach ((array)$this->_count_levels($id, $children, $level + 1) as $_id => $_level) {
					$ids[$_id] = $_level;
				}
			}
		}
		return $ids;
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
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
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
		return '<div class="row-fluid" id="'.$extra['id'].'">'.$legend.'<div class="'.$div_class.'">'.$form.'</div></div>';
	}

	/**
	*/
	function modal ($extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		$def_style = $extra['inline'] ? 'position: relative; top: auto; left: auto; right: auto; bottom: auto; margin: 0 auto 20px; z-index: 1; max-width: 100%; display: block; overflow-y: auto;' : '';
		$extra['style'] = $extra['style'] ?: $def_style;
		return '
			<div class="modal" style="'.$extra['style'].'" id="'.$extra['id'].'">
				<div class="modal-dialog">
					<div class="modal-content">
						<div class="modal-header">'
							.($extra['show_close'] ? '<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>' : '')
							.($extra['header'] ? '<h3>'.$extra['header'].'</h3>' : '')
						.'</div>
						<div class="modal-body">'.$extra['body'].'</div>'
						.($extra['footer'] ? '<div class="modal-footer">'.$extra['footer'].'</div>' : '').'
					</div>
				</div>
			</div>';
	}

	/**
	*/
	function tabs ($tabs = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		$headers = array();
		$items = array();
		$links_prefix = $extra['links_prefix'] ?: 'tab_';
		foreach ((array)$tabs as $k => $v) {
			$desc_raw = null;
			$disabled = null;
			if (!is_array($v)) {
				$content = $v;
				$v = array();
			} else {
				$content = $v['content'];
				$desc_raw = $v['desc_raw'];
				$disabled = $v['disabled'];
			}
			$content = trim($content);
			if ($extra['hide_empty'] && !strlen($content)) {
				continue;
			}
			$name = $v['name'] ?: $k;
			$desc = $v['desc'] ?: (!$extra['no_auto_desc'] ? ucfirst(str_replace('_', ' ', $name)) : $name);
			$id = preg_replace('~[^a-z0-9_-]+~i', '', $v['id'] ?: $links_prefix. $k);
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
			if (isset($extra['totals'][$name])) {
				$v['badge'] = intval( isset($extra['totals'][$name]['total']) ? $extra['totals'][$name]['total'] : $extra['totals'][$name] );
			}
			$badge = isset($v['badge']) ? ' <sup class="badge badge-'.($v['class_badge'] ?: 'info').'">'.$v['badge'].'</sup>' : '';
			if (!$extra['no_headers']) {
				$headers[] =
					'<li class="'.($is_active ? 'active' : ''). ($class_head ? ' '.$class_head : '').'">
						<a '.(!$disabled ? 'href="#'.$id.'" ' : '').'data-toggle="tab">'.($desc_raw ?: t($desc)). $badge. '</a>
					</li>';
			}
			$items[] = '<div class="tab-pane '.$css_class. ($class_body ? ' '.$class_body : '').'" id="'.$id.'">'.$content.'</div>';
		}
		$body .= $headers ? '<ul id="'.$extra['id'].'" class="nav nav-tabs">'.implode(PHP_EOL, (array)$headers). '</ul>'. PHP_EOL : '';
		$body .= '<div id="'.$extra['id'].'_content" class="tab-content">'. implode(PHP_EOL, (array)$items).'</div>';
		return $body;
	}

	/**
	*/
	function accordion ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		$items = array();
		foreach ((array)$data as $k => $v) {
			if (!is_array($v)) {
				$content = $v;
				$v = array();
			} else {
				$content = $v['body'];
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
			$badge = $v['badge'] ? ' <sup class="badge badge-'.($v['class_badge'] ?: 'info').'">'.$v['badge'].'</sup>' : '';

			$items[] =
				'<div class="accordion-group panel panel-default'.($class_group ? ' '.$class_group : '').'">
					<div class="accordion-heading panel-heading'.($class_head ? ' '.$class_head : '').'">
						'.($this->_is_bs3 ? '<h4 class="panel-title">' : '').'
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#'.$extra['id'].'" href="#'.$id.'">'. $desc. $badge. '</a>
						'.($this->_is_bs3 ? '</h4>' : '').'
					</div>
					<div id="'.$id.'" class="accordion-body panel-collapse collapse'.($is_selected ? ' in' : ''). ($class_body ? ' '.$class_body : '').'">
						<div class="accordion-inner panel-body">'.$content.'</div>
					</div>
				</div>';
		}
		return '<div class="panel-group accordion'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">'.implode(PHP_EOL, (array)$items).'</div>';
	}

	/**
	*/
	function carousel ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		$items = array();
		$headers = array();
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
			<a class="left carousel-control" href="#'.$extra['id'].'" data-slide="prev"><span class="icon icon-chevron-left"></span></a>
			<a class="right carousel-control" href="#'.$extra['id'].'" data-slide="next"><span class="icon icon-chevron-right"></span></a>
		';
		return '<div id="'.$extra['id'].'" class="carousel slide'.($extra['class'] ? ' '.$extra['class'] : '').'" data-ride="carousel">
				<ol class="carousel-indicators">'.implode(PHP_EOL, $headers).'</ol>
				<div class="carousel-inner">'.implode(PHP_EOL, $items).'</div>
				'.(!$extra['no_controls'] ? $controls : '').'
			</div>';
	}

	/**
	*/
	function alert ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		$close_btn = (!$extra['no_close'] && !$data['no_close']) ? '<button type="button" class="close" data-dismiss="alert">×</button>' : '';
		$head = is_array($data) ? $data['head'] : '';
		$body = is_array($data) ? $data['body'] : $data;
		$alert_type = $extra['alert'] ?: (is_array($data) ? $data['alert'] : '');
		if (!$alert_type) {
			$alert_type = 'error';
		}
		return '
			<div class="alert alert-block alert-'.$alert_type.' fade in'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">
				'.$close_btn.'
				'.($head ? '<h4 class="alert-heading">'.$head.'</h4>' : '').'
				'.$body.'
			</div>';
	}

	/**
	*/
	function navbar ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		$items = array();
		$brand = '';
		if (isset($data['brand'])) {
			$b = $data['brand'];
			unset($data['brand']);
			$brand = '<a class="brand navbar-brand'.($b['class'] ? ' '.$b['class'] : '').'" href="'.$b['link'].'" title="'.$b['name'].'">'.$b['name'].'</a>';
		}
		$data = _prepare_html($data);
		foreach ((array)$data as $k => $v) {
			if (isset($extra['selected'])) {
				$is_selected = ($extra['selected'] == $k);
			} else {
				$is_selected = (++$i == 1);
			}
			$class_item = $v['class_item'] ?: $extra['class_item'];
			$badge = $v['badge'] ? ' <sup class="badge badge-'.($v['class_badge'] ?: 'info').'">'.$v['badge'].'</sup>' : '';
			$items[] = '<li class="'.($is_selected ? ' active' : ''). ($class_item ? ' '.$class_item : '').'"><a href="'.$v['link'].'" title="'.$v['name'].'">'. $v['name']. $badge. '</a></li>';
		}
		return
			'<div class="navbar navbar-default'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">
				<div class="navbar-inner navbar-header">'
					.$brand
					.'<ul class="nav navbar-nav">'.implode(PHP_EOL, (array)$items).'</a>
				</div>
			</div>';
	}

	/**
	*/
	function breadcrumbs ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		$items = array();
		$divider = $extra['divider'] ?: '/';
		$len = count($data);
		$data = _prepare_html($data);
		foreach ((array)$data as $k => $v) {
			$is_last = (++$i == $len);
			$class_item = $v['class_item'] ?: $extra['class_item'];
			$badge = $v['badge'] ? ' <sup class="badge badge-'.($v['class_badge'] ?: 'info').'">'.$v['badge'].'</sup>' : '';
			$items[] = '<li class="'.($is_last ? ' active' : ''). ($class_item ? ' '.$class_item : '').'">
				'.(($is_last || !$v['link']) ? $v['name']
					: '<a href="'.$v['link'].'" title="'.$v['name'].'">'.$v['name']. $badge. '</a>'.(!$this->_is_bs3 ? ' <span class="divider">'.$divider.'</span>' : '')
				).'
			</li>';
		}
		$tag = $this->_is_bs3 ? 'ol' : 'ul';
		return '<'.$tag.' class="breadcrumb'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'"">'.implode(PHP_EOL, (array)$items).'</'.$tag.'>';
	}

	/**
	*/
	function thumbnails ($data = array(), $extra = array()) {
		$items = array();
		$columns = (int)$extra['columns'] ?: 3;
		$row_class = 'span'.round(12 / $columns).' col-lg-'.round(12 / $columns);
		foreach ((array)$data as $k => $v) {
			if (!is_array($v)) {
				$img_src = $v;
				$v = array();
			} else {
				$img_src = $v['img'];
			}
			$class_item = $v['class_item'] ?: $extra['class_item'];
			$tag = $this->_is_bs3 ? 'div' : 'li';
			$items[] =
				'<'.$tag.' class="'.$row_class. ($class_item ? ' '.$class_item : ''). ($v['style'] ? ' style="'.$v['style'].'"' : '').'">
					<div class="thumbnail">
						<img alt="'._prepare_html($v['alt'] ?: $v['head']).'" src="'._prepare_html($img_src).'" />
						'.(($v['head'] || $v['body']) ? '<div class="caption">'.($v['head'] ? '<h3>'._prepare_html($v['head']).'</h3>' : '').' '.$v['body'].'</div>' : '').'
					</div>
				</'.$tag.'>';
		}
		$body = array();
		$tag = $this->_is_bs3 ? 'div' : 'ul';
		foreach (array_chunk($items, $columns) as $_items) {
			$id = __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
			$body[] = '<'.$tag.' class="thumbnails'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$id.'">'.implode(PHP_EOL, (array)$_items).'</'.$tag.'>';
		}
		return implode(PHP_EOL, $body);
	}

	/**
	*/
	function progress_bar ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		$items = array();
		foreach ((array)$data as $v) {
			if (!is_array($v)) {
				$val = $v;
				$v = array();
			} else {
				$val = $v['val'];
			}
			$type = $v['type'] ?: $extra['type'];
			$class_item = $v['class_item'] ?: $extra['class_item'];
			$items[] = '<div class="progress-bar bar bar-'.$type.' progress-bar-'.$type. ($class_item ? ' '.$class_item : '')
				.'" style="width: '.$val.'%;'.($v['style'] ? ' '.$v['style'] : '').'" role="progressbar"></div>';
		}
		return '<div class="progress'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">'.implode(PHP_EOL, (array)$items).'</div>';
	}

	/**
	*/
	function pagination ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		if (isset($data['prev'])) {
			$prev = $data['prev'];
			unset($data['prev']);
		}
		if (isset($data['next'])) {
			$next = $data['next'];
			unset($data['next']);
		}
		$items = array();
// TODO: auto-detect current page and need of first. last
		if ($prev) {
			$items[] = '<li><a href="'.$prev.'">'.t('Prev').'</a></li>';
		}
		foreach ((array)$data as $page => $link) {
			$items[] = '<li><a href="'.$link.'">'.$page.'</a></li>';
		}
		if ($next) {
			$items[] = '<li><a href="'.$next.'">'.t('Next').'</a></li>';
		}
		if ($this->_is_bs3) {
			return '<div><ul class="pagination'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">'.implode(PHP_EOL, $items).'</ul></div>';
		} else {
			return '<div class="pagination'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'"><ul>'.implode(PHP_EOL, $items).'</ul></div>';
		}
	}

	/**
	*/
	function panel ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		return
			'<div class="panel panel-primary'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">
				<div class="panel-heading">
					<h3 class="panel-title">'.$data['title'].'</h3>
				</div>
				<div class="panel-body">'.$data['body'].'</div>
			</div>';
	}

	/**
	*/
	function jumbotron ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		return '<div class="jumbotron'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'"><h1>'.$data['head'].'</h1>'.$data['body'].'</div>';
	}

	/**
	*/
	function well ($body = '', $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		if (!$extra['class']) {
			$extra['class'] = 'well-lg';
		}
		return '<div class="well well-lg'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">'.$body.'</div>';
	}

	/**
	*/
	function list_group ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		$items = array();
		foreach ((array)$data as $v) {
			if (!is_array($v)) {
				$body = $v;
				$v = array();
			} else {
				$body = $v['body'];
			}
			$type = $v['type'] ?: $extra['type'];
			$class_item = $v['class_item'] ?: $extra['class_item'];
			$items[] = '<li class="list-group-item'. ($class_item ? ' '.$class_item : '').'"><span class="badge">'.$v['badge'].'</span> '.$body.'</li>';
		}
		return '<ul class="list-group'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">'.implode(PHP_EOL, (array)$items).'</ul>';
	}

	/**
	*/
	function media_objects ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		if ($data) {
			$data = $this->_recursive_sort_items($data);
		}
		$keys = array_keys($data);
		$keys_counter = array_flip($keys);
		$items = array();
		foreach ((array)$data as $id => $item) {
			$next_id = $keys[$keys_counter[$id] + 1];
			$next_item = $next_id ? $data[$next_id] : array();
			$close_num_levels = 1;
			if ($next_item) {
				$close_num_levels = $item['level'] - $next_item['level'] + 1;
				if ($close_num_levels < 0) {
					$close_num_levels = 0;
				}
			}
			$items[] = '
				<div class="media">
					<a class="pull-left"'.($item['link'] ? ' href="'.$item['link'].'"' : '').'><img class="media-object" alt="'.$item['alt'].'" src="'.$item['img'].'"></a>
					<div class="media-body">
						<h4 class="media-heading">'
						.($item['link'] ? '<a href="'.$item['link'].'">' : ''). $item['head']. ($item['link'] ? '</a>' : '')
						.($item['date'] ? '<small class="pull-right">'._format_date($item['date'], $extra['date_format'] ?: 'full').'</small>' : '')
						. '</h4>'
						.$item['body'].'
			';
			if ($close_num_levels) {
				$items[] = str_repeat(PHP_EOL.'</div></div>'.PHP_EOL, $close_num_levels);
			}
		}
		return '<div class="media-objects'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">'.implode(PHP_EOL, (array)$items).'</div>';
	}

	/**
	*/
	function menu ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		if ($data) {
			$data = $this->_recursive_sort_items($data);
		}
		$keys = array_keys($data);
		$keys_counter = array_flip($keys);
		$items = array();
		$ul_opened = false;
		foreach ((array)$data as $id => $item) {
			$next_item = $data[ $keys[$keys_counter[$id] + 1] ];
			$has_children = false;
			$close_li = 1;
			$close_ul = 0;
			if ($next_item) {
				if ($next_item['level'] > $item['level']) {
					$has_children = true;
				}
				$close_li = $item['level'] - $next_item['level'] + 1;
				if ($close_li < 0) {
					$close_li = 0;
				}
			}
			$badge = $item['badge'] ? ' <sup class="badge badge-'.($item['class_badge'] ?: 'info').'">'.$item['badge'].'</sup>' : '';
			$items[] = '
				<li class="dropdown">
					<a href="'.$item['link'].'" class="dropdown-toggle">'. $item['name']. $badge. ($has_children ? ' <b class="caret"></b>' : ''). '</a>'
				;
			if ($has_children) {
				$ul_opened = true;
				$items[] = PHP_EOL. '<ul class="dropdown-menu sub-menu">'. PHP_EOL;
			} elseif ($close_li) {
				if ($ul_opened && !$has_children && $item['level'] != $next_item['level']) {
					$ul_opened = false;
					$close_ul = 1;
				}
				$tmp = str_repeat(PHP_EOL. ($close_ul ? '</li></ul>' : '</li>'). PHP_EOL, $close_li);
				if ($close_li > 1 && $close_ul) {
					$tmp = substr($tmp, 0, -strlen('</ul>'.PHP_EOL)). PHP_EOL;
				}
				$items[] = $tmp;
			}
		}
		return '<div class="navbar navbar-default'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">
					<div class="navbar-inner navbar-header">
						<ul class="nav navbar-nav">'.implode(PHP_EOL, (array)$items).'</ul>
					</div>
				</div>';
	}

	/**
	*/
	function grid ($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		$rows = array();
		$ul_opened = false;
		foreach ((array)$data as $id => $row) {
			$items = array();
			$row_col = count($row) ? floor(12 / count($row)) : 1;
			if ($row_col < 1) {
				$row_col = 1;
			}
			foreach ((array)$row as $rid => $item) {
				$body = $item[0] ?: $item['body'];
				$col = $item[1] ?: $item['col'];
				$class = $item['class'];
				if (!$col) {
					$col = $row_col;
				}
				$items[] = '<div class="span'.$col.' col-lg-'.$col.($class ? ' '.$class : '').'">'.$body.'</div>';
			}
			$rows[] = '<div class="row-fluid show-grid">'.implode(PHP_EOL, $items).'</div>';
		}
		return '<div class="grid'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">'.implode(PHP_EOL, (array)$rows).'</div>';
	}

	/**
	*/
	function navlist($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		$items = array();
		foreach ((array)$data as $v) {
			if (!is_array($v)) {
				$name = $v;
				$v = array();
			} else {
				$name = $v['name'];
			}
			$link = $v['link'];
			$class_item = $v['class_item'] ?: $extra['class_item'];
			$badge = $v['badge'] ? ' <sup class="badge badge-'.($v['class_badge'] ?: 'info').'">'.$v['badge'].'</sup>' : '';
			$items[] = '<li class="'. ($class_item ? ' '.$class_item : '').'"><a href="'.$link.'"><i class="icon-chevron-right"></i> '.t($name). $badge. '</a></li>';
		}
		return '<div class="bs-docs-sidebar"><ul class="nav nav-list bs-docs-sidenav'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">'.implode(PHP_EOL, (array)$items).'</ul></div>';
	}

	/**
	*/
	function li($data = array(), $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		$items = array();
		foreach ((array)$data as $v) {
			if (!is_array($v)) {
				$body = $v;
				$v = array();
			} else {
				$body = $v['body'];
			}
			$class_item = $v['class_item'] ?: $extra['class_item'];
			$badge = $v['badge'] ? ' <sup class="badge badge-'.($v['class_badge'] ?: 'info').'">'.$v['badge'].'</sup>' : '';
			$items[] = '<li class="'. ($class_item ? ' '.$class_item : '').'">'. $badge. ($v['link'] ? '<a href="'.$v['link'].'">'.$body.'</a>' : $body).'</li>';
		}
		return '<ul class="'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">'.implode(PHP_EOL, (array)$items).'</ul>';
	}

	/**
	*/
	function tree($data = array(), $extra = array()) {
		return _class('html_tree', 'classes/html/')->tree($data, $extra);
	}

	/**
	*/
	function select_box ($name, $values = array(), $selected = '', $show_text = false, $type = 2, $add_str = '', $translate = 0, $level = 0) {
		// Passing params as array
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
		}
		$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		$translate = isset($extra['translate']) ? $extra['translate'] : $translate;
		if ($extra['no_translate']) {
			$translate = 0;
		}
		$selected = isset($extra['selected']) ? $extra['selected'] : $selected;
		$show_text = isset($extra['show_text']) ? $extra['show_text'] : (!is_null($show_text) ? $show_text : false);
		$type = isset($extra['type']) ? $extra['type'] : (!is_null($type) ? $type : 2);
		$level = isset($extra['level']) ? $extra['level'] : $level;
		// (example: $add_str = 'size=6')
		$add_str = isset($extra['add_str']) ? $extra['add_str'] : $add_str;
		$extra['class'] .= ' form-control';
		if ($extra['class']) {
			$add_str .= ' class="'.$extra['class'].'" ';
		}
		if ($extra['style']) {
			$add_str .= ' style="'.$extra['style'].'" ';
		}
		if (!$values) {
			return false;
		}
		if ($level == 0) {
			$extra['force_id'] && $id = $extra['force_id'];
			$id = $id ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
			$body = PHP_EOL.'<select name="'.$name.'"'.($this->AUTO_ASSIGN_IDS ? ' id="'.$id.'"' : '').$add_str.">".PHP_EOL;
		}
		$selected = strval($selected);
		if ($show_text && $level == 0) {
			$body .= '<option value="">'.($show_text == 1 ? '-'.t('select').' '.t($name).'-' : $show_text).'</option>'.PHP_EOL;
		}
		$self_func = __FUNCTION__;
		foreach ((array)$values as $key => $cur_value) {
			if (is_array($cur_value)) {
				$body .= '<optgroup label="'.$key.'" title="'.($translate ? t($key) : $key).'">'.PHP_EOL;
				$body .= $this->$self_func($name, $cur_value, $selected, $show_text, $type, $add_str, $translate, $level + 1);
				$body .= '</optgroup>'.PHP_EOL;
			} else {
				$_what_compare = strval($type == 1 ? $cur_value : $key);
				$body .= '<option value="'.$key.'" '.($_what_compare == $selected ? 'selected="selected"' : '').'>'.($translate ? t($cur_value) : $cur_value).'</option>'.PHP_EOL;
			}
		}
		$body .= $level == 0 ? '</select>'.PHP_EOL : '';
		return $body;
	}

	/**
	*/
	function multi_select($name, $values = array(), $selected = '', $show_text = false, $type = 2, $add_str = '', $translate = 0, $level = 0, $disabled = false) {
		// Passing params as array
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
		}
		$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		$translate = isset($extra['translate']) ? $extra['translate'] : $translate;
		if ($extra['no_translate']) {
			$translate = 0;
		}
		$selected = isset($extra['selected']) ? $extra['selected'] : $selected;
		$show_text = isset($extra['show_text']) ? $extra['show_text'] : (!is_null($show_text) ? $show_text : false);
		$type = isset($extra['type']) ? $extra['type'] : (!is_null($type) ? $type : 2);
		$level = isset($extra['level']) ? $extra['level'] : $level;
		$disabled = isset($extra['disabled']) ? $extra['disabled'] : $disabled;
		// (example: $add_str = 'size=6') disabled
		$add_str = isset($extra['add_str']) ? $extra['add_str'] : $add_str;
		$extra['class'] .= ' form-control';
		if ($extra['class']) {
			$add_str .= ' class="'.$extra['class'].'" ';
		}
		if ($extra['style']) {
			$add_str .= ' style="'.$extra['style'].'" ';
		}
		if (!$values) {
			return false;
		}
		if (!is_array($selected)) {
			$selected = strval($selected);
		}
		if ($disabled  == 1) {
			$disabled = 'disabled';
		} else {
			$disabled = '';
		}
		if ($level == 0) {
			$extra['force_id'] && $id = $extra['force_id'];
			$id = $id ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
			$body = PHP_EOL.'<select '.$disabled.' multiple name="'.$name.'[]"'.($this->AUTO_ASSIGN_IDS ? ' id="'.$id.'"' : '').$add_str.'>'.PHP_EOL;
		}
		if ($show_text && $level == 0) {
			$body .= '<option value="">-'.t('select').' '.t($name).'-</option>'.PHP_EOL;
		}
		$self_func = __FUNCTION__;
		foreach ((array)$values as $key => $value) {
			if (is_array($value)) {
				$body .= '<optgroup label="'.$key.'" title="'.($translate ? t($key) : $key).'">'.PHP_EOL;
				$body .= $this->$self_func($name, $value, $selected, $show_text, $type, $add_str, $translate, $level + 1);
				$body .= '</optgroup>'.PHP_EOL;
			} else {
				// Selected value could be an array
				if (is_array($selected)) {
					if ($type == 1) {
						$sel_text = in_array($value, $selected) ? 'selected="selected"' : '';
					} else {
						$sel_text = isset($selected[$key]) ? 'selected="selected"' : '';
					}
				} elseif (strlen($selected)) {
					$_what_compare = strval($type == 1 ? $value : $key);
					$sel_text = $_what_compare == $selected ? 'selected="selected"' : '';
				} else {
					$sel_text = '';
				}
				$body .= '<option value="'.$key.'" '.$sel_text.'>'.($translate ? t($value) : $value).'</option>'.PHP_EOL;
			}
		}
		$body .= $level == 0 ? '</select>'.PHP_EOL : '';
		return $body;
	}

	/**
	* Alias
	*/
	function multi_select_box($name, $values = array(), $selected = '', $show_text = false, $type = 2, $add_str = '', $translate = 0, $level = 0, $disabled = false) {
		return $this->multi_select($name, $values, $selected, $show_text, $type, $add_str, $translate, $level, $disabled);
	}

	/**
	*/
	function radio_box ($name, $values = array(), $selected = '', $flow_vertical = false, $type = 2, $add_str = '', $translate = 0) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
		}
		$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		$translate = isset($extra['translate']) ? $extra['translate'] : $translate;
		if ($extra['no_translate']) {
			$translate = 0;
		}
		$selected = isset($extra['selected']) ? $extra['selected'] : $selected;
		$type = isset($extra['type']) ? $extra['type'] : (!is_null($type) ? $type : 2);
		$flow_vertical = isset($extra['flow_vertical']) ? $extra['flow_vertical'] : $flow_vertical;
		$add_str = isset($extra['add_str']) ? $extra['add_str'] : $add_str;
		if ($extra['class']) {
			$add_str .= ' class="'.$extra['class'].'" ';
		}
		if ($extra['style']) {
			$add_str .= ' style="'.$extra['style'].'" ';
		}
		if (!$values) {
			return false;
		}
		$selected = strval($selected);
		foreach ((array)$values as $value => $val_name) {
			$id = __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
			if ($this->BOXES_USE_STPL) {
				$_what_compare = strval($type == 1 ? $val_name : $value);
				$replace = array(
					'name'			=> $name,
					'value'			=> $value,
					'selected'		=> $_what_compare == $selected ? 'checked="true"' : '',
					'add_str'		=> $add_str,
					'label'			=> $translate ? t($val_name) : $val_name,
					'divider'		=> $flow_vertical ? '<br />' : '&nbsp;',
					'horizontal'	=> $extra['horizontal'] ? 1 : 0,
					'id'			=> $id,
				);
				$body .= tpl()->parse('system/common/radio_box_item', $replace);
			} else {
				$body .=
					'<label class="radio'.($extra['horizontal'] ? ' radio-horizontal' : '').'">'
						.'<input type="radio" name="'.$name.'" id="'.$id.'" value="'.$value.'" '.$add_str.' '.((strval($value) == $selected) ? 'checked' : '').'>'
						.t($val_name)
					.'</label>'.PHP_EOL;
			}
		}
		return $body;
	}

	/**
	* Simple check box
	*/
	function check_box ($name = '', $value = '', $selected = '', $add_str = '', $extra = array()) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$name = isset($extra['name']) ? $extra['name'] : 'checkbox';
		$value = $extra['value'] ?: (strlen($value) ? $value : '1');
		$selected = isset($extra['selected']) ? $extra['selected'] : $selected;
		if (isset($extra['checked'])) {
			$selected = $extra['checked'];
		}
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		$desc = $extra['desc'] ? $extra['desc'] : ucfirst(str_replace('_', '', $name));
		$translate = $extra['translate'] ? $extra['translate'] : $translate;
		$add_str = $extra['add_str'] ? $extra['add_str'] : $add_str;
		if ($extra['class']) {
			$add_str .= ' class="'.$extra['class'].'" ';
		}
		if ($extra['style']) {
			$add_str .= ' style="'.$extra['style'].'" ';
		}

		return '<label class="checkbox">'
				.'<input type="checkbox" name="'.$name.'" id="'.$extra['id'].'" value="'.$value.'"'
					.($selected ? ' checked="checked"' : '')
					.($add_str ? ' '.$add_str : '')
				.'> &nbsp;' // Please do not remove this whitespace :)
				.($translate ? t($extra['desc']) : $extra['desc'])
			.'</label>';
	}

	/**
	* Processing many checkboxes at one time
	*/
	function multi_check_box ($name, $values = array(), $selected = array(), $flow_vertical = false, $type = 2, $add_str = '', $translate = 0, $name_as_array = false) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
		}
		$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		$translate = isset($extra['translate']) ? $extra['translate'] : 0;
		if ($extra['no_translate']) {
			$translate = 0;
		}
		$selected = $extra['selected'];
		$type = isset($extra['type']) ? $extra['type'] : (!is_null($type) ? $type : 2);
		$flow_vertical = isset($extra['flow_vertical']) ? $extra['flow_vertical'] : false;
		$name_as_array = isset($extra['name_as_array']) ? $extra['name_as_array'] : false;
		$add_str = isset($extra['add_str']) ? $extra['add_str'] : '';
		if ($extra['class']) {
			$add_str .= ' class="'.$extra['class'].'" ';
		}
		if ($extra['style']) {
			$add_str .= ' style="'.$extra['style'].'" ';
		}
		if (!$values) {
			return false;
		}
		if (!is_array($selected)) {
			$selected = strval($selected);
		}
		foreach ((array)$values as $key => $value) {
			$sel_text = '';
			// Selected value could be an array
			if (is_array($selected)) {
				if ($type == 1) {
					$sel_text = in_array($value, $selected) ? 'checked' : '';
				} else {
					$sel_text = isset($selected[$key]) ? 'checked' : '';
				}
			} elseif (strlen($selected)) {
				$_what_compare = strval($type == 1 ? $value : $key);
				$sel_text = $_what_compare == $selected ? 'checked="true"' : '';
			} else {
				$sel_text = '';
			}
			if ($name_as_array) {
				$val_name = $name.'['.$key.']';
			} else {
				$val_name = $name.'_'.$key;
			}
			$id = __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
			if ($this->BOXES_USE_STPL) {
				$replace = array(
					'name'		=> $val_name,
					'value'		=> $key,
					'selected'	=> $sel_text,
					'add_str'	=> $add_str,
					'label'		=> $translate ? t($value) : $value,
					'divider'	=> $flow_vertical ? '<br />' : '&nbsp;',
					'id'		=> $id,
				);
				$body .= tpl()->parse('system/common/check_box_item', $replace);
			} else {
				$body .= '<input type="checkbox" name="'.$val_name.'" class="check" value="'.$key.'" '.$sel_text.' '.$add_str.' id="'.$id.'">'
					.($translate ? t($value) : $value)
					.($flow_vertical ? '<br />' : '&nbsp;'). PHP_EOL;
			}
		}
		return $body;
	}

	/**
	* Simple input form control
	*/
	function input ($name = '', $value = '', $extra = array()) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		$extra['name'] = $extra['name'] ?: ($name ?: 'text');
		$extra['value'] = $extra['value'] ?: $value;
#		$extra['id'] = $extra['id'] ?: $extra['name'];
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		$extra['desc'] = $extra['desc'] ?: ucfirst(str_replace('_', '', $extra['name']));
		$extra['type'] = $extra['type'] ?: 'text';
		$extra['placeholder'] = $extra['desc'];

		$attrs_names = array('name','type','id','class','style','placeholder','value','data','size','maxlength','pattern','disabled','required','autocomplete');
		return '<input'._attrs($extra, $attrs_names).'>';
	}

	/**
	*/
	function div_box ($name, $values = array(), $selected = '', $extra = array()) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
		}
		$desc = $extra['desc'] ? $extra['desc'] : ucfirst(str_replace('_', '', $name));
		$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		$translate = isset($extra['translate']) ? $extra['translate'] : 0;
		if ($extra['no_translate']) {
			$translate = 0;
		}
		$selected = $extra['selected'] ?: $selected;
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		if (!$values) {
			return false;
		}
		$selected = strval($selected);

		$items = array();
		$selected_val = '';
		foreach ((array)$values as $key => $cur_value) {
			$_what_compare = strval($type == 1 ? $cur_value : $key);
			$is_selected = $_what_compare == $selected;
			$val = ($translate ? t($cur_value) : $cur_value);
			$items[] = '<li class="dropdown'.($is_selected ? ' active' : '').'"><a data-value="'.$key.'" '.($is_selected ? 'data-selected="selected"' : '').'>'.$val.'</a></li>'.PHP_EOL;
			if ($is_selected) {
				$selected_val = $val;
			}
		}
		$body .= '<li class="dropdown" style="list-style-type:none;" id="'.$extra['id'].'">';
		$body .= '<a class="dropdown-toggle" data-toggle="dropdown">'.($selected_val ?: $desc).'&nbsp;<span class="caret"></span></a>';
		$body .= '<ul class="dropdown-menu">';
		$body .= implode(PHP_EOL, $items);
		$body .= '</ul>';
		$body .= '</li>';
		return $body;
	}

	/**
	*/
	function button_box ($name, $values = array(), $selected = '', $extra = array()) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
		}
		$desc = isset($extra['desc']) ? $extra['desc'] : ucfirst(str_replace('_', '', $name));
		$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		$translate = isset($extra['translate']) ? $extra['translate'] : 0;
		if ($extra['no_translate']) {
			$translate = 0;
		}
		$selected = isset($extra['selected']) ? $extra['selected'] : $selected;
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		if (!$values) {
			return false;
		}
		$selected = strval($selected);

		$items = array();
		$selected_val = '';
		foreach ((array)$values as $key => $cur_value) {
			$_what_compare = strval($type == 1 ? $cur_value : $key);
			$is_selected = $_what_compare == $selected;
			$val = $translate ? t($cur_value) : $cur_value;
			$items[] = '<li class="dropdown'.($is_selected ? ' active' : '').'"><a data-value="'.$key.'" '.($is_selected ? 'data-selected="selected"' : '').'>'.$val.'</a></li>'.PHP_EOL;
			if ($is_selected) {
				$selected_val = $val;
			}
		}
		$class = $extra['class'] ?: 'btn dropdown-toggle';
		$extra['class_add'] && $class .= ' '.$extra['class_add'];
		$text = $selected_val ?: $desc;

		$body = array();
		$body[] = '<div class="btn-group" id="'.$extra['id'].'">';
		if ($extra['button_split']) {
			$body[] = '<button class="btn">'.$text.'</button>'.PHP_EOL;
			$body[] = '<button class="'.$class.'" data-toggle="dropdown"><span class="caret"></span></button>';
		} else {
			$body[] = '<button class="'.$class.'" data-toggle="dropdown">'.$text.'&nbsp;<span class="caret"></span></button>';
		}
		$body[] = '<ul class="dropdown-menu">';
		$body[] = implode(PHP_EOL, $items);
		$body[] = '</ul>';
		$body[] = '</div>';
		return implode(PHP_EOL, $body);
	}

	/**
	*/
	function button_split_box ($name, $values = array(), $selected = '', $extra = array()) {
		$extra['button_split'] = true;
		return $this->button_box ($name, $values, $selected, $extra);
	}

	/**
	*/
	function list_box ($name, $values = array(), $selected = '', $extra = array()) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
			$name = $extra['name'];
		}
		$desc = isset($extra['desc']) ? $extra['desc'] : ucfirst(str_replace('_', '', $name));
		$values = isset($extra['values']) ? $extra['values'] : (array)$values; // Required
		$translate = isset($extra['translate']) ? $extra['translate'] : 0;
		if ($extra['no_translate']) {
			$translate = 0;
		}
		$selected = isset($extra['selected']) ? $extra['selected'] : $selected;
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		if (!$values) {
			return false;
		}
// TODO: allow deep customization of its layout
// TODO: require here js, css of the bfh-selectbox plugin
		$selected = strval($selected);
		$body .= '<div class="bfh-selectbox" id="'.$extra['id'].'">'
					.'<input type="hidden" name="'.$name.'" value="'.$selected.'">'
					.'<a class="bfh-selectbox-toggle" role="button" data-toggle="bfh-selectbox" href="#">'
						.'<span class="bfh-selectbox-option bfh-selectbox-medium" data-option="'.$selected.'">'.$values[$selected].'</span>'
						.'<b class="caret"></b>'
					.'</a>'
					.'<div class="bfh-selectbox-options">'
						.'<input type="text" class="bfh-selectbox-filter">'
						.'<div role="listbox">'
							.'<ul role="option">';
		foreach ((array)$values as $key => $cur_value) {
			$body .= '<li'.($is_selected ? ' class="active"' : '').'><a tabindex="-1" href="#" data-option="'.$key.'">'.($translate ? t($cur_value) : $cur_value).'</a></li>'.PHP_EOL;
		}
		$body .= 			'</ul>'
						.'</div>'
					.'</div>'
				.'</div>';
		return $body;
	}

	/**
	*/
	function select2_box ($name, $values = array(), $selected = '', $extra = array()) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
		} else {
			$extra['name'] = $name;
		}
		$extra['force_id'] = $extra['force_id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];

		css('//cdnjs.cloudflare.com/ajax/libs/select2/3.4.6/select2.min.css');
		js('//cdnjs.cloudflare.com/ajax/libs/select2/3.4.6/select2.min.js');
		$js_options = (array)$extra['js_options'] + array(
			'width'			=> 'element',
			'placeholder'	=> $extra['desc'],
			// put default js options here
		);
		js('$(function() { $("#'.addslashes($extra['force_id']).'").select2('.json_encode($js_options).'); });');
		$func = $extra['multiple'] ? 'multi_select' : 'select_box';
		$extra[ 'class' ] .= 'no-chosen';
		return $this->$func($extra, $values, $selected);
	}

	/**
	*/
	function chosen_box ($name, $values = array(), $selected = '', $extra = array()) {
		if (is_array($name)) {
			$extra = (array)$extra + $name;
		} else {
			$extra['name'] = $name;
		}
		$extra['force_id'] = $extra['force_id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];

		css('//cdnjs.cloudflare.com/ajax/libs/chosen/0.9.15/chosen.css');
		js('//cdnjs.cloudflare.com/ajax/libs/chosen/0.9.15/chosen.jquery.min.js');
		$js_options = (array)$extra['js_options'] + array(
			// put default js options here
		);
		js('$(function() { $("#'.addslashes($extra['force_id']).'").chosen('.json_encode($js_options).'); });');
		$func = $extra['multiple'] ? 'multi_select' : 'select_box';
		return $this->$func($extra, $values, $selected);
	}

	/**
	*/
	function date_picker ($name, $cur_date = '') {
		js('jquery-ui');
		css('jquery-ui');

		js('$(function() { $( ".datepicker" ).datepicker({ dateFormat: "yy-mm-dd" }); });');
		css('//cdnjs.cloudflare.com/ajax/libs/jqueryui/1.10.4/css/jquery.ui.datepicker.min.css');

		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
// TODO: use input() unified control
		return '<input type="text" name="'.$name.'" class="datepicker" value="'.$cur_date.'" style="width:80px" readonly="true" id="'.$extra['id'].'" />';
	}

	/**
	*/
	function date_box ($selected = '', $years = '', $name_postfix = '', $add_str = '', $show_what = 'ymd', $show_text = 1, $translate = 1) {
		return _class('html_datetime', 'classes/html/')->date_box($selected, $years, $name_postfix, $add_str, $show_what, $show_text, $translate);
	}

	/**
	*/
	function time_box ($selected = '', $name_postfix = '', $add_str = '', $show_text = 1, $translate = 1) {
		return _class('html_datetime', 'classes/html/')->time_box ($selected, $name_postfix, $add_str, $show_text, $translate);
	}

	/**
	*/
	function date_box2 ($name, $selected = '', $years = '', $add_str = '', $show_what = 'ymd', $show_text = 1, $translate = 1) {
		return _class('html_datetime', 'classes/html/')->date_box2($name, $selected, $years, $add_str, $show_what, $show_text, $translate);
	}

	/**
	*/
	function time_box2 ($name, $selected = '', $add_str = '', $show_text = 1, $translate = 1) {
		return _class('html_datetime', 'classes/html/')->time_box2 ($name, $selected, $add_str, $show_text, $translate);
	}

	/**
	*/
	function datetime_box2 ($name, $selected = '', $years = '', $add_str = '', $show_what = 'ymd', $show_text = 1, $translate = 1) {
		return _class('html_datetime', 'classes/html/')->datetime_box2($name, $selected, $years, $add_str, $show_what, $show_text, $translate);
	}
}
