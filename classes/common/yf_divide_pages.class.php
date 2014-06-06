<?php

/**
* Common used pager module
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_divide_pages {

	/** @var bool Force to rewrite given SQL to force speed with COUNT(*) operator. Example: module_conf("divide_pages", "SQL_COUNT_REWRITE", false); */
	public $SQL_COUNT_REWRITE	= true;
	/** @var int Global pages limit (to prevent pages flooding). Example: 100 (pages only). set to "0" to disable */
	public $PAGES_LIMIT			= 0;
	/** @var */
	public $OVERRIDE_DB_OBJECT	= false;
	/** @var */
	public $PAGES_PER_BLOCK		= 10;
	/** @var */
	public $DEFAULT_PER_PAGE	= 20;
	/** @var */
	public $DEFAULT_RENDER_TYPE	= 'slide'; // blocks|slide
	/** @var */
	public $DEFAULT_TPLS_PATH	= 'system/divide_pages/';

	/**
	* Divide pages
	*/
	function go ($sql = '', $url_path = '', $render_type = '', $records_on_page = 0, $num_records = 0, $tpls_path = '', $add_get_vars = 1, $extra = array()) {
		if (is_array($url_path)) {
			$extra = $url_path;
			$url_path = '';
		}
		if (empty($url_path)) {
			if ($extra['url_path']) {
				$url_path = $extra['url_path'];
			} elseif ($extra['path']) {
				$url_path = $extra['path'];
			}
		}
		if (!$url_path) {
			$url_path = './?object='.$_GET['object'].'&action='.$_GET['action']. (isset($_GET['id']) ? '&id='.$_GET['id'] : '');
		}
		if (!strlen($render_type)) {
			if ($extra['render_type']) {
				$render_type = $extra['render_type'];
			} elseif ($extra['type']) {
				$render_type = $extra['type'];
			}
		}
		if (!$render_type) {
			$render_type = $this->DEFAULT_RENDER_TYPE;
		}
		$requested_page = isset($extra['requested_page']) ? $extra['requested_page'] : $_GET['page'];
		$records_on_page = abs(intval($extra['records_on_page'] ?: $records_on_page));
		$per_page = $records_on_page ?: (MAIN_TYPE_ADMIN ? conf('admin_per_page') : conf('user_per_page'));
		if (!$per_page) {
			$per_page = $this->DEFAULT_PER_PAGE;
		}
		$num_records	= abs(intval($extra['num_records'] ?: $num_records));
		$tpls_path		= $extra['tpls_path'] ?: $tpls_path;
		if (!strlen($tpls_path)) {
			$tpls_path = $this->DEFAULT_TPLS_PATH;
		}
		$add_get_vars	= isset($extra['add_get_vars']) ? $extra['add_get_vars'] : ($add_get_vars ?: 1);

		$total_records = (int)$this->get_total_records($sql, $num_records, $extra);

		$total_pages = $per_page ? ceil($total_records / $per_page) : 0;
		// Global number of pages limit (only for user section)
		if (MAIN_TYPE_USER && $this->PAGES_LIMIT && $total_pages > $this->PAGES_LIMIT) {
			$old_total_pages = $total_pages;
			$total_pages = $this->PAGES_LIMIT;
		}
		// Filter not existing pages numbers
		if (!$requested_page || $requested_page < 1) {
			$cur_page = 1;
		} elseif ($requested_page > $total_pages) {
			$cur_page = $total_pages;
		} else {
			$cur_page = $requested_page;
		}
		$render_func = 'render_type_'.$render_type;
		$rendered = $this->$render_func(array(
			'total_records'		=> $total_records,
			'per_page'			=> $per_page,
			'requested_page'	=> $requested_page,
			'cur_page'			=> $cur_page,
			'total_pages'		=> $total_pages,
			'url_path'			=> $url_path. ($add_get_vars ? _add_get(array('page')) : ''),
			'tpls_path'			=> $tpls_path,
		));
		$result = array(
			'limit_sql'		=> ' LIMIT '.intval($rendered['first']).', '.intval($per_page),
			'pages_html'	=> trim($rendered['html']),
			'total_records'	=> intval($total_records),
			'first_record'	=> intval($rendered['first']), // Counter start value for the current page
			'total_pages'	=> intval($total_pages),
			'limited_pages' => intval($limited_pages),
		);
		return array_values($result); // Needed for compatibility with tons of legacy code, that using list(...) = divide_pages(...)
	}

	/**
	* Divide pages using given array
	*/
	function go_with_array ($items_array = array(), $url_path = '', $render_type = '', $records_on_page = 0, $num_records = 0, $tpls_path = '', $add_get_vars = 1) {
		$total = count($items_array);
		// Number of records to show on one page
		$records_on_page = intval($records_on_page);
		$per_page = $records_on_page ?: (MAIN_TYPE_ADMIN ? conf('admin_per_page') : conf('user_per_page'));
		if (empty($per_page)) {
			$per_page = $this->DEFAULT_PER_PAGE;
		}
		list(, $pages, ) = $this->go(null, $url_path, $render_type, $per_page, $total);
		if (count($items_array) > $per_page) {
			$items_array = array_slice($items_array, (empty($_GET['page']) ? 0 : intval($_GET['page']) - 1) * $per_page, $per_page, true);
		}
		return array(
			$items_array,
			trim($pages),
			intval($total)
		);
	}

	/**
	*/
	function get_total_records($sql = '', $num_records = 0, $extra = array()) {
		$sql_callback	= $extra['sql_callback'];
		if (is_array($sql)) {
			$total_records = count($sql);
		} elseif (empty($num_records) && !empty($sql)) {
			$sql = trim($sql);
			$_need_std_num_rows		= true;
			// Try to rewrite query for more speed
			if ($this->SQL_COUNT_REWRITE) {
				// Example of callback: function($sql) { return preg_replace('/^SELECT.*FROM/ims', 'SELECT COUNT(*) FROM', ltrim($sql)); }
				if (is_callable($sql_callback)) {
					$modified_sql = $sql_callback($sql);
				} else {
					$modified_sql = 'SELECT COUNT(*) AS `0` FROM ('.$sql.') AS __pager_tmp';
				}
				if ($modified_sql != $sql) {
					$_need_std_num_rows = false;
				}
				// Simple speed optimization by removing ORDER BY ... from SQL when counting total records
				$modified_sql = preg_replace('/\sORDER BY .*? (ASC|DESC)$/i', '', $modified_sql);
			}
			$db = !empty($this->OVERRIDE_DB_OBJECT) ? $this->OVERRIDE_DB_OBJECT : db();
			$total_records = intval($_need_std_num_rows ? $db->query_num_rows($sql) : $db->get_one($modified_sql));
		} else {
			$total_records = $num_records;
		}
		return intval($total_records);
	}

	/**
	* Render pager type "blocks"
	*/
	function render_type_blocks ($params = array()) {
		$total_records	= $params['total_records'];
		$per_page		= $params['per_page'];
		$requested_page	= $params['requested_page'];
		$url_path		= $params['url_path'];
		$tpls_path		= $params['tpls_path'];
		$total_pages	= $params['total_pages'];
		$cur_page		= $params['cur_page'];
		$pages_per_block= $this->PAGES_PER_BLOCK;

		$items = array();
		if ($total_records < $per_page) {
			$first = 0;
		} else {
			$total_blocks = $pages_per_block ? ceil($total_pages / $pages_per_block) : 0;
			$cur_block = $pages_per_block ? ceil($cur_page / $pages_per_block) : 0;
			$start_page = ($cur_block - 1) * $pages_per_block + 1;
			$end_page = $start_page + $pages_per_block;
			if ($end_page > $total_pages) {
				$end_page = $total_pages + 1;
			}
			// Show link to first page
			if ($cur_page > 1) {
				$items['page_first'] = tpl()->parse($tpls_path.'page_first', array(
					'link'	=> $url_path. '&page=1',
				));
			}
			// Show link to the previous block (if needed)
			if ($cur_block > 1) {
				$items['block_prev'] = tpl()->parse($tpls_path.'block_prev', array(
					'link'				=> $url_path. '&page='.(($cur_block - 1) * $pages_per_block),
					'pages_per_block'	=> $pages_per_block,
				));
			}
			// Show link to previous page
			if ($cur_page > 1) {
				$items['page_prev'] = tpl()->parse($tpls_path.'page_prev', array(
					'link'	=> $url_path. '&page='.intval($cur_page - 1),
				));
			}
			// Process current block of pages
			for ($k = $start_page; $k < $end_page; $k++) {
				$items['pages'] .= tpl()->parse($tpls_path. ($cur_page == $k ? 'page_current' : 'page_other'), array(
					'link'		=> $url_path. '&page='.$k,
					'page_num'	=> $k,
				));
			}
			// Show link to next page
			if ($cur_page < $total_pages) {
				$items['page_next'] = tpl()->parse($tpls_path.'page_next', array(
					'link'	=> $url_path. '&page='.($cur_page + 1),
				));
			}
			// Show link to the next block (if needed)
			if ($cur_block < $total_blocks) {
				$items['block_next'] = tpl()->parse($tpls_path.'block_next', array(
					'link'				=> $url_path. '&page='.$k,
					'pages_per_block'	=> $pages_per_block,
				));
			}
			// Show link to last page
			if ($total_pages > $pages_per_block && $cur_page < $total_pages) {
				$items['page_last'] = tpl()->parse($tpls_path.'page_last', array(
					'link'	=> $url_path. '&page='.$total_pages,
				));
			}
			// Set first value for the database query
			$first = ($cur_page - 1) * $per_page;
			// First record could be only positive
			if ($first < 0) {
				$first = 0;
			}
		}
		$html = tpl()->parse($tpls_path.'main', array(
			'total_pages'	=> intval($total_pages),
			'total_records'	=> intval($total_records),
			'record_first'	=> intval($first + 1),
			'record_last'	=> intval($cur_page * $per_page),
			'page_first'	=> $items['page_first'],
			'block_prev'	=> $items['block_prev'],
			'page_prev'		=> $items['page_prev'],
			'pages'			=> $items['pages'],
			'page_next'		=> $items['page_next'],
			'block_next'	=> $items['block_next'],
			'page_last'		=> $items['page_last'],
			'current_page'	=> $cur_page,
		));
		return array(
			'html'			=> $html,
			'total_pages'	=> $total_pages,
			'limited_pages'	=> $limited_pages,
			'first'			=> $first,
		);
	}

	/**
	* Render pager type "slide"
	*/
	function render_type_slide ($params = array()) {
		$total_records	= $params['total_records'];
		$per_page		= $params['per_page'];
		$requested_page	= $params['requested_page'];
		$url_path		= $params['url_path'];
		$tpls_path		= $params['tpls_path'];
		$total_pages	= $params['total_pages'];
		$cur_page		= $params['cur_page'];
		$pages_per_block= $this->PAGES_PER_BLOCK;

		$items = array();
		if ($total_records < $per_page) {
			$first = 0;
		} else {
			$half = ceil($pages_per_block / 2);
			$start_page = $cur_page - $half + 1;
			if ($start_page <= 0) {
				$start_page = 1;
			}
			$end_page = $start_page + $pages_per_block;
			if ($end_page > $total_pages) {
				$end_page = $total_pages + 1;
			}
			// Show link to first page
			if ($cur_page > 1) {
				$items['page_first'] = tpl()->parse($tpls_path.'page_first', array(
					'link'	=> $url_path. '&page=1',
				));
			}
			// Show link to previous page
			if ($cur_page > 1) {
				$items['page_prev'] = tpl()->parse($tpls_path.'page_prev', array(
					'link'	=> $url_path. '&page='.intval($cur_page - 1),
				));
			}
			// Process current block of pages
			for ($k = $start_page; $k < $end_page; $k++) {
				$items['pages'] .= tpl()->parse($tpls_path. ($cur_page == $k ? 'page_current' : 'page_other'), array(
					'link'		=> $url_path. '&page='.$k,
					'page_num'	=> $k,
				));
			}
			// Show link to next page
			if ($cur_page < $total_pages) {
				$items['page_next'] = tpl()->parse($tpls_path.'page_next', array(
					'link'	=> $url_path. '&page='.($cur_page + 1),
				));
			}
			// Show link to last page
			if ($total_pages > $pages_per_block && $cur_page < $total_pages) {
				$items['page_last'] = tpl()->parse($tpls_path.'page_last', array(
					'link'	=> $url_path. '&page='.$total_pages,
				));
			}
			// Set first value for the database query
			$first = ($cur_page - 1) * $per_page;
			// First record could be only positive
			if ($first < 0) {
				$first = 0;
			}
		}
		$html = tpl()->parse($tpls_path.'main', array(
			'total_pages'	=> intval($total_pages),
			'total_records'	=> intval($total_records),
			'record_first'	=> intval($first + 1),
			'record_last'	=> intval($cur_page * $per_page),
			'page_first'	=> $items['page_first'],
			'block_prev'	=> '',
			'page_prev'		=> $items['page_prev'],
			'pages'			=> $items['pages'],
			'page_next'		=> $items['page_next'],
			'block_next'	=> '',
			'page_last'		=> $items['page_last'],
			'current_page'	=> $cur_page,
		));
		return array(
			'html'			=> $html,
			'total_pages'	=> $total_pages,
			'limited_pages'	=> $limited_pages,
			'first'			=> $first,
		);
	}
}
