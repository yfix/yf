<?php

class yf_manage_assets {

	/**
	*/
	function show() {
		return $this->search_used();
#		return a('/@object/purge_cache', 'Purge cache');
	}

	/**
	*/
	function search_used() {
		$in_php = _class('dir')->grep('~[\s](asset|js|css)\([^\(\)\{\}\$]+\)~ims', APP_PATH, '*.php');
		$in_tpl = _class('dir')->grep('~\{(asset|js|css)\(\)\}[^\{\}\(\)\$]+?\{\/\1\}~ims', APP_PATH, '*.stpl');
		return 'PHP: <pre>'.print_r(_prepare_html($in_php), 1).'</pre>'
			.  'TPL: <pre>'.print_r(_prepare_html($in_tpl), 1).'</pre>';
	}
}
