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
	function navbar ($data = array()) {
// TODO
	}

	/**
	*/
	function breadcrumbs ($data = array()) {
// TODO
	}

	/**
	*/
	function carousel ($data = array()) {
// TODO
	}

	/**
	*/
	function accordion ($tabs = array(), $extra = array()) {
		$items = array();
		$extra['id'] = $extra['id'] ?: 'accordion_'.substr(md5(microtime()), 0, 8);
		foreach ((array)$tabs as $k => $v) {
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
			$items[] = 
				'<div class="accordion-group">
					<div class="accordion-heading">
						<a class="accordion-toggle" data-toggle="collapse" data-parent="#'.$extra['id'].'" href="#'.$id.'">'.$desc.'</a>
					</div>
					<div id="'.$id.'" class="accordion-body collapse'.($is_selected ? ' in' : '').'">
						<div class="accordion-inner">'.$content.'</div>
					</div>
				</div>';
		}
		return '<div class="accordion'.($extra['class'] ? ' '.$extra['class'] : '').'" id="'.$extra['id'].'">'.implode(PHP_EOL, (array)$items).'</div>';
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
			if (!$extra['no_headers']) {
				$headers[] = '<li class="'.($is_active ? 'active' : '').'"><a href="#'.$id.'" data-toggle="tab">'.t($desc).'</a></li>';
			}
			$items[] = '<div class="tab-pane '.$css_class.'" id="'.$id.'">'.$content.'</div>';
		}
		$extra['id'] = $extra['id'] ?: 'tabs_'.substr(md5(microtime()), 0, 8);
		$body .= $headers ? '<ul id="'.$extra['id'].'" class="nav nav-tabs">'.implode(PHP_EOL, (array)$headers). '</ul>'. PHP_EOL : '';
		$body .= '<div id="'.$extra['id'].'_content" class="tab-content">'. implode(PHP_EOL, (array)$items).'</div>';
		return $body;
	}
}
