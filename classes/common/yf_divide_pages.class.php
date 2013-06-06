<?php

/**
* Common used pager module
* 
* @package		YF
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class yf_divide_pages {

	/** @var bool 
	*	Force to rewrite given SQL to force speed with COUNT(*) operator 
	*	Example: module_conf("divide_pages", "SQL_COUNT_REWRITE", false);
	*/
	var $SQL_COUNT_REWRITE		= true;
	/** @var int 
	*	Global pages limit (to prevent pages flooding), 
	* 	Example: 100 (pages only), 
	*	set to "0" to disable 
	*/
	var $PAGES_LIMIT			= 0;
	/** @var */
	var $OVERRIDE_DB_OBJECT		= false;
	/** @var */
	var $PAGES_PER_BLOCK		= 10;

	/**
	* Divide pages
	*/
	function go ($sql = "", $path = "", $type = "", $records_on_page = 0, $num_records = 0, $TPLS_PATH = "", $add_get_vars = 1) {
		// Default path
		if (empty($path)) {
			$path = "./?object=".$_GET["object"]."&action=".$_GET["action"]
			.(isset($_GET["id"]) ? "&id=".$_GET["id"] : "");
		}
		// Set default type
		if (!strlen($type)) {
			$type = "blocks";
		}
		// Set default templates path (MUST be with trailing slash)
		if (!strlen($TPLS_PATH)) {
			$TPLS_PATH = "system/divide_pages/";
		}
		// Total number of records
		$total_records = 0;
		if (empty($num_records) && !empty($sql)) {
			$sql = trim($sql);
			$_need_std_num_rows		= true;
			// Try to rewrite query for more speed
			if ($this->SQL_COUNT_REWRITE) {
				$modified_sql = $sql;
				if (preg_match("/\sGROUP\s+BY\s/ims", $sql)) {
					$modified_sql = "SELECT COUNT(*) AS `0` FROM (".$sql.") AS `__tmp`";
				} elseif (false === strpos($sql, " JOIN ")) {
					$modified_sql = preg_replace("/^SELECT\s+.*?\s+FROM/i", "SELECT COUNT(*) AS `0` FROM", $sql);
				}
				if ($modified_sql != $sql) {
					$_need_std_num_rows = false;
				}
				$modified_sql = preg_replace("/\sORDER BY `.*?` (ASC|DESC)\$/i", "", $modified_sql);
			}
			if ($_need_std_num_rows) {
				if (!empty($this->OVERRIDE_DB_OBJECT)) {
					$obj = $this->OVERRIDE_DB_OBJECT;
					
	 				$total_records = $obj->query_num_rows($sql);
				} else {
	 				$total_records = db()->query_num_rows($sql);
				}
			} else {
				if (!empty($this->OVERRIDE_DB_OBJECT)) {
					$obj = $this->OVERRIDE_DB_OBJECT;
					list($total_records) = $obj->query_fetch($modified_sql);
				} else {
					list($total_records) = db()->query_fetch($modified_sql);
				}
			}
		} else {
			$total_records = $num_records;
		}
		$total_records = intval($total_records);
		// Number of records to show on one page
		$records_on_page = intval($records_on_page);
		if ($records_on_page) {
			$per_page = $records_on_page;
		} else {
			$per_page = conf(MAIN_TYPE_ADMIN ? 'admin_per_page' : 'user_per_page');
			if (!isset($per_page)) {
				$per_page = 0;
			}
		}
		if (empty($per_page)) {
			$per_page = 20;
		}
		// NUmber of pages to show in one block
		$pages_per_block = $this->PAGES_PER_BLOCK;
		// Divide numbers of pages into blocks inside forum
		if ($type == "blocks") {
			$items = array();
			$total_pages= 0;
			$curr_page	= 0;
			// If need to create pages navigation - proceed
			if ($total_records < $per_page) {
				$first = 0;
			} else {
				// Total number of pages
				$total_pages	= $per_page ? ceil($total_records / $per_page) : 0;
				// Global number of pages limit (only for user section)
				if (MAIN_TYPE_USER && $this->PAGES_LIMIT && $total_pages > $this->PAGES_LIMIT) {
					$old_total_pages = $total_pages;
					$total_pages = $this->PAGES_LIMIT;
				}
				// Filter pages numbers that are do not exists
				if (!$_GET['page'] || ($_GET['page'] < 1)) {
					$curr_page = 1;
				} elseif ($_GET['page'] > $total_pages) {
					$curr_page = $total_pages;
				} else {
					$curr_page = $_GET['page'];
				}
				// Total number of blocks
				$total_blocks	= $pages_per_block ? ceil($total_pages / $pages_per_block) : 0;
				// Number of current block
				$curr_block = $pages_per_block ? ceil($curr_page / $pages_per_block) : 0;
				// Start page in block number
				$start_page = ($curr_block - 1) * $pages_per_block + 1;
				// End page in block number
				$end_page = $start_page + $pages_per_block;
				// Check if number is greater than total - then assign to max page number
				if ($end_page > $total_pages) {
					$end_page = $total_pages + 1;
				}
				$_path = $path. ($add_get_vars ? _add_get(array("page")) : "");
				// Show link to first page
				if ($curr_page > 1) {
					$items["page_first"] = tpl()->parse($TPLS_PATH."page_first", array(
						"link"	=> $_path. "&page=1",
					));
				}
				// Show link to the previous block (if needed)
				if ($curr_block > 1) {
					$items["block_prev"] = tpl()->parse($TPLS_PATH."block_prev", array(
						"link"				=> $_path. "&page=".(($curr_block - 1) * $pages_per_block),
						"pages_per_block"	=> $pages_per_block,
					));
				}
				// Show link to previous page
				if ($curr_page > 1) {
					$items["page_prev"] = tpl()->parse($TPLS_PATH."page_prev", array(
						"link"	=> $_path. "&page=".intval($curr_page - 1),
					));
				}
				// Process current block of pages
				for ($k = $start_page; $k < $end_page; $k++) {
					$items["pages"] .= tpl()->parse($TPLS_PATH.($curr_page == $k ? "page_current" : "page_other"), array(
						"link"		=> $_path. "&page=".$k,
						"page_num"	=> $k,
					));
				}
				// Show link to next page
				if ($curr_page < $total_pages) {
					$items["page_next"] = tpl()->parse($TPLS_PATH."page_next", array(
						"link"	=> $_path. "&page=".($curr_page + 1),
					));
				}
				// Show link to the next block (if needed)
				if ($curr_block < $total_blocks) {
					$items["block_next"] = tpl()->parse($TPLS_PATH."block_next", array(
						"link"				=> $_path. "&page=".$k,
						"pages_per_block"	=> $pages_per_block,
					));
				}
				// Show link to last page
				if ($total_pages > $pages_per_block && $curr_page < $total_pages) {
					$items["page_last"] = tpl()->parse($TPLS_PATH."page_last", array(
						"link"	=> $_path. "&page=".$total_pages,
					));
				}
				// Set first value for the database query
				$first = ($curr_page - 1) * $per_page;
			}
			// Process pages main template
			$replace = array (
				"total_pages"	=> intval($total_pages),
				"total_records"	=> intval($total_records),
				"record_first"	=> intval($first + 1),
				"record_last"	=> intval($curr_page * $per_page),
				"page_first"	=> isset($items["page_first"])	? $items["page_first"] : "",
				"block_prev"	=> isset($items["block_prev"])	? $items["block_prev"] : "",
				"page_prev"		=> isset($items["page_prev"])	? $items["page_prev"] : "",
				"pages"			=> isset($items["pages"])		? $items["pages"] : "",
				"page_next"		=> isset($items["page_next"])	? $items["page_next"] : "",
				"block_next"	=> isset($items["block_next"])	? $items["block_next"] : "",
				"page_last"		=> isset($items["page_last"])	? $items["page_last"] : "",
				"current_page"	=> $curr_page,
			);
			$pages = tpl()->parse($TPLS_PATH."main", $replace);
		}
		// First record could be only positive
		if ($first < 0) {
			$first = 0;
		}
		$limited_pages = 0;
		if ($this->PAGES_LIMIT && !empty($old_total_pages)) {
			$limited_pages = $total_pages;
			$total_pages = $old_total_pages;
		}
		// Generate SQL string to limit output
		return array(
			" LIMIT ".intval($first).", ".intval($per_page),
			trim($pages),
			intval($total_records),
			intval($first), // Counter start value for the current page
			intval($total_pages),
			intval($limited_pages),
		);
	}

	/**
	* Divide pages using given array
	*/
	function go_with_array ($items_array = array(), $path = "", $type = "", $records_on_page = 0, $num_records = 0, $TPLS_PATH = "", $add_get_vars = 1) {
		// Get total number of records
		$total = count($items_array);
		// Number of records to show on one page
		$records_on_page = intval($records_on_page);
		$per_page = $records_on_page ? $records_on_page : (MAIN_TYPE_ADMIN ? conf('admin_per_page') : conf('user_per_page'));
		if (empty($per_page)) {
			$per_page = 20;
		}
		// Do connect common pager
		list(,$pages,) = $this->go(null, null, null, $per_page, $total);
		if (count($items_array) > $per_page) {
			$items_array = array_slice($items_array, (empty($_GET["page"]) ? 0 : intval($_GET["page"]) - 1) * $per_page, $per_page, true);
		}
		return array(
			$items_array,
			trim($pages),
			intval($total)
		);
	}
}
