<?php

/**
*/
class yf_common_tags_cloud {

	/** @var string Default value. Cloud creates in alphabetic text order available values - 'text' or 'num' (For cloud creaion) */
	public $CLOUD_ORDER = 'text';
	/** @var int Maximum fontsize for cloud (in 'em') */
	public $CLOUD_MAX_FSIZE = 2;
	/** @var int Minimum fontsize for cloud (in 'em') */
	public $CLOUD_MIN_FSIZE = 0.9;

	/**
	* Creates tags cloud
	* $cloud_data - array like (key => array(text, num))  OR   $cloud_data - array like (text => num)
	*/
	function create($cloud_data = array(), $params = array()) {
		if (empty($cloud_data)) {
			return '';
		}
		if (empty($params['object'])) {
			$params['object'] = 'tags';
		}
		if (empty($params['action'])) {
			$params['action'] = 'search';
		}
		if ($this->CLOUD_ORDER == 'text') {
			ksort($cloud_data);
		} elseif ($this->CLOUD_ORDER == 'num') {
			arsort($cloud_data);
		}
		// Search for the max and min values of 'num' in array	
		$max_val = max($cloud_data);
		$min_val = min($cloud_data);
		foreach ((array)$cloud_data as $_text => $_num) {
			// Creating cloud
			if ($max_val !== $min_val) {
				$_cloud_fsize = $this->CLOUD_MIN_FSIZE + (
					($this->CLOUD_MAX_FSIZE - $this->CLOUD_MIN_FSIZE)
					* ($_num - $min_val)
					/ ($max_val - $min_val)
				);
				$_cloud_fsize = round($_cloud_fsize, 2);
			} else {
				$_cloud_fsize = 1;
			}
			$replace2 = array(
				'num'			=> $_num,
				'tag_text'		=> $_text,
				'tag_search_url'=> './?object='.$params['object'].'&action='.$params['action'].'&id='.$params['id_prefix'].($params['amp_encode'] ? str_replace(urlencode('&'), urlencode(urlencode('&')), urlencode($_text)) : urlencode($_text)),
				'cloud_fsize'	=> $_cloud_fsize,
			);
			$items .= tpl()->parse('tags/cloud_item', $replace2);
		}
		return $items;
	}	
}
