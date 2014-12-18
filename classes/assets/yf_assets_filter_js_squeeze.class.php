<?php

class yf_assets_filter_js_squeeze {

	/**
	*/
	public function apply($in, $params = array()) {
// TODO
		!isset($params['single_line']) && $params['single_line'] = true;
		!isset($params['keep_important_comments']) && $params['keep_important_comments'] = true;
		!isset($params['special_var_rx']) && $params['special_var_rx'] = \JSqueeze::SPECIAL_VAR_RX;

		$parser = new \JSqueeze();
		return $parser->squeeze($in, $params['single_line'], $params['keep_important_comments'], $params['special_var_rx']);
	}
}
