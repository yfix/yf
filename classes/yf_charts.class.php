<?php

/**
 */
class yf_charts {

	public $TICK_COUNT = 10;

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* http://omnipotent.net/jquery.sparkline
	*/
	function jquery_sparklines($data, $extra = array()) {
		$extra['id'] = $extra['id'] ?: __FUNCTION__.'_'.++$this->_ids[__FUNCTION__];
		if (!is_array($data) || !$data) {
			return false;
		}
		$extra['trim'] = isset($extra['trim']) ? $extra['trim'] : true;
		// Trim empty data left and right
		if ($extra['trim']) {
			foreach ((array)$data as $k => $v) {
				if (!$v) {
					unset($data[$k]);
					continue;
				}
				break;
			}
			foreach (array_reverse($data, $preserve_keys = true) as $k => $v) {
				if ($v) {
					break;
				}
				unset($data[$k]);
			}
			if (!$data) {
				return false;
			}
		}
		$extra['type'] = $extra['type'] ?: 'bar';

		asset('jquery-sparklines');
		jquery('
			$("#'.$extra['id'].'").sparkline('.json_encode(array_values($data)).', {
				type: "'.$extra['type'].'",
				enableTagOptions: true,
				tooltipFormat: "{{offset:key}}: {{value}}",
				tooltipValueLookups: { key: '.json_encode(array_keys($data)).' }
			});
		');
		return '<span id="'.$extra['id'].'" class="sparkline"></span>';
	}

	/**
	* Data array format for chart. Examples:
	* array('{DATE1}' => '{VALUE1}' '{DATE2}' => '{VALUE2}')	
	* array('{LINE_NAME1}' => Array('DATE1' => 'VAL1', 'DATE2' => 'VAL2'), '{LINE_NAME2}' => Array('DATE1' => 'VAL1', 'DATE2' => 'VAL2')}
	*/
	function chart($data, $params) {
		if ($params['flash'] == true) {
			return $this->chart_flash($data, $params);
		}
		$width = isset($params['width'])?$params['width']:'90%';
		$height = isset($params['height'])?$params['height']:'90%';
		foreach ((array)$data as $data_item) {
			$count = count($data_item);
			break;
		}
		if ($count == 1) {
			$data = array(
				'1'	=> $data,
			);
		}
		foreach ((array)$data as $name => $data_items){
			foreach ((array)$data_items as $date => $val){
				$items[] = array(
					'date'	=> $date,
					'name'	=> $name,
					'val'	=> $val,
				);
			}
		}
		if (!$params['not_include_js']) {
			$incude_js = $this->_include_js();
		}
		return $incude_js.tpl()->parse('charts/chart', array(
			'items'		=> $items,
			'id'		=> 'chart'.rand(),
			'width'		=> is_numeric($width)?$width.'px':$width,
			'height'	=> is_numeric($height)?$height.'px':$height,
		));
	}

	/**
	* Data array format for bar chart. Examples:
	* array('{NAME}' => array('val' => '{VALUE}', 'link' => '{LINK}')}
	*/
	function chart_bar($data, $params) {
		if ($params['flash'] == true) {
			return $this->chart_bar_flash($data, $params);
		}
		$direction = isset($params['direction']) ? $params['direction'] : 'horizontal';
		$width = isset($params['width']) ? $params['width'] : '90%';
		foreach ((array)$data as $key => $value) {
			if (is_array($value)) {
				break;
			}
			$data[$key] = array(
				'val' => $value,
				'link' => ''
			);
		}
		if (!$params['not_include_js']) {
			$incude_js = $this->_include_js();
		}
		return $incude_js. tpl()->parse('charts/chart_bar', array(
			'data'		=> $data,
			'ticks'		=> $this->_get_ticks($data),
			'direction'	=> $direction,
			'id'		=> 'bar'.rand(),
			'width'		=> is_numeric($width)?$width.'px':$width,
		));
	}
	
	/**
	* Data array format for pie chart. Examples:
	* array('{NAME}' => array('val' => '{VALUE}', 'link' => '{LINK}')}
	*/
	function chart_pie($data, $params) {
		$width = isset($params['width'])?$params['width']:'90%';
		$height = isset($params['height'])?$params['height']:'90%';
		if (!$params['not_include_js']) {
			$incude_js = $this->_include_js();
		}
		return $incude_js. tpl()->parse('charts/chart_pie', array(
			'data'		=> $data,
			'id'		=> 'pie'.rand(),
			'width'		=> is_numeric($width)?$width.'px':$width,
			'height'	=> is_numeric($height)?$height.'px':$height,
		));
	}

	/**
	*/
	function _get_ticks($data) {
		$max = 0;
		foreach ((array)$data as $val){
			if($val['val'] > $max){
				$max = $val['val'];
			}
		}
		$max_real = $max;
		$max = ceil($max);
		if ($max < 10) {
			$max = 10;
		}
		$len = strlen($max);
		$max = round($max, -1 * ($len-1));
		$step = $max / ($this->TICK_COUNT - 1);
		if ($max <= $max_real || ($max - $max_real) < $step) {
			$max = $max + $step;
		}
		for ($i = 0; $i <= $max; $i = $i + $step) {
			$tick = round($i, -1*($len-2));
			$result[$tick] = $tick;
		}
		return $result;
	}

	/**
	*/
	function _include_js() {
		if (!$GLOBALS['chart']['include_js']) {
			$GLOBALS['chart']['include_js'] = true;
			$content = tpl()->parse('charts/chart_include_js', $replace);	
		}
		return $content;
	}

	/**
	*/
	function chart_bar_flash($data, $params) {
		if (empty($data)) {
			return;
		}
		include_once YF_PATH.'libs/yf_open_flash_chart/open-flash-chart.php';

		$width = isset($params['width'])?$params['width']:'90%';
		$height = isset($params['height'])?$params['height']:'90%';
		
		$bar = new bar_outline( 70, '#A2C2FC', '#0750D9' );
//		$bar->key( 'Page views', 10 );

		$bar->data = $data;
		
		$g = new graph();
		
		$g->js_path = isset($params['js_path'])?$params['js_path']:'/js/';
		$g->swf_path = isset($params['swf_path_path'])?$params['swf_path_path']:'/js/';
		
		$g->title( ' ', '{font-size: 20px;}' );
		$g->bg_colour = '#e9e9e9';
		$g->x_axis_colour( '#000000', '#c1c1c1' );
		$g->y_axis_colour( '#000000', '#c1c1c1' );

		$g->data_sets[] = $bar;
		$g->set_x_labels(array_keys($data));
		$g->set_x_label_style(10, '#000000', 0, 2);
		
		$g->set_y_max(max($bar->data));
		$g->set_y_label_style(10, '#000000', 0, 2);
		
		$g->set_y_legend( 'Price', 10, '#000000' );
		$g->set_x_legend( 'Date', 10, '#000000' );
		
		$g->set_tool_tip(  '#val# EUR on #x_label#' );

		// формат значений
		$g->set_num_decimals(0);
		$g->set_y_format('#val#&euro;');
		
		$g->set_width($width);
		$g->set_height($height);

		$g->set_output_type('js');
		return $g->render();
	}
	
	/**
	*/
	function chart_flash($data, $params) {
		if (empty($data)) {
			return;
		}
		include_once YF_PATH.'libs/yf_open_flash_chart/open-flash-chart.php';

		$width = isset($params['width']) ? $params['width'] : '90%';
		$height = isset($params['height']) ? $params['height'] : '90%';

		$g = new graph();
		$g->js_path = isset($params['js_path']) ? $params['js_path'] : '/js/';
		$g->swf_path = isset($params['swf_path']) ? $params['swf_path'] : '/js/';
		
		$g->title( ' ', '{font-size: 20px;}' );
		$g->bg_colour = '#e9e9e9';
		$g->x_axis_colour( '#000000', '#c1c1c1' );
		$g->y_axis_colour( '#000000', '#c1c1c1' );

		$g->set_data( $data );
		// Find maximal strlen of x axis label
		foreach((array)$data as $k => $v) {
			$xlabel_len[] = _strlen($k);
		}
		if (max($xlabel_len) > 7) {
			$orientation = 2;
		} else {
			$orientation = 0;
		}
		$g->set_x_labels(array_keys($data));
		$g->set_x_label_style(10, '#000000', $orientation, 2);
		
		$g->set_y_max(max($data));

		$g->set_y_label_style(10, '#000000', 0, 2);
		
		$g->set_y_legend( 'Price', 10, '#000000' );
		$g->set_x_legend( 'Date', 10, '#000000' );
		
		$g->set_tool_tip(  '#val# EUR on #x_label#' );
		$g->line_dot( 2, 3, '#0750D9', '', 10);

		// формат значений
		$g->set_num_decimals(0);
		$g->set_y_format('#val#&euro;');
		
		$g->set_width($width);
		$g->set_height($height);

		$g->set_output_type('js');
		return $g->render();
	}
}
