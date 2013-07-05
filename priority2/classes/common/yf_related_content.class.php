<?php

/**
* Code for searching related content
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_related_content {

	/** @var List of words to skip when analyzing @conf_skip */
	public $STOP_WORDS			= array('', 'a', 'an', 'the', 'and', 'of', 'i', 'to', 'is', 'in', 'with', 'for', 'as', 'that', 'on', 'at', 'this', 'my', 'was', 'our', 'it', 'you', 'we', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '10', 'about', 'after', 'all', 'almost', 'along', 'also', 'amp', 'another', 'any', 'are', 'area', 'around', 'available', 'back', 'be', 'because', 'been', 'being', 'best', 'better', 'big', 'bit', 'both', 'but', 'by', 'c', 'came', 'can', 'capable', 'control', 'could', 'course', 'd', 'dan', 'day', 'decided', 'did', 'didn', 'different', 'div', 'do', 'doesn', 'don', 'down', 'drive', 'e', 'each', 'easily', 'easy', 'edition', 'end', 'enough', 'even', 'every', 'example', 'few', 'find', 'first', 'found', 'from', 'get', 'go', 'going', 'good', 'got', 'gt', 'had', 'hard', 'has', 'have', 'he', 'her', 'here', 'how', 'if', 'into', 'isn', 'just', 'know', 'last', 'left', 'li', 'like', 'little', 'll', 'long', 'look', 'lot', 'lt', 'm', 'made', 'make', 'many', 'mb', 'me', 'menu', 'might', 'mm', 'more', 'most', 'much', 'name', 'nbsp', 'need', 'new', 'no', 'not', 'now', 'number', 'off', 'old', 'one', 'only', 'or', 'original', 'other', 'out', 'over', 'part', 'place', 'point', 'pretty', 'probably', 'problem', 'put', 'quite', 'quot', 'r', 're', 'really', 'results', 'right', 's', 'same', 'saw', 'see', 'set', 'several', 'she', 'sherree', 'should', 'since', 'size', 'small', 'so', 'some', 'something', 'special', 'still', 'stuff', 'such', 'sure', 'system', 't', 'take', 'than', 'their', 'them', 'then', 'there', 'these', 'they', 'thing', 'things', 'think', 'those', 'though', 'through', 'time', 'today', 'together', 'too', 'took', 'two', 'up', 'us', 'use', 'used', 'using', 've', 'very', 'want', 'way', 'well', 'went', 'were', 'what', 'when', 'where', 'which', 'while', 'white', 'who', 'will', 'would', 'your');
	/** @var @conf_skip */
	public $_PATTERN_KWORDS	= "/\s*[\s+\.|\?|,|(|)|\-+|'|\\\"|=|;|&#0215;|\$|\/|:|{|}]\s*/i";
	/** @var @conf_skip */
	public $NUM_KEYWORDS		= 20;
	/** @var @conf_skip */
	public $DEF_PARAMS			= array(
		"WHAT_TO_RETURN"=> "sql",
		"FIELD_ID"		=> "id",
		"FIELD_DATE"	=> "add_date",	// Set to -1 to disable
		"FIELD_USER"	=> "user_id",	// Set to -1 to disable
		"FIELD_TITLE"	=> "title",		// Set to -1 to disable
		"FIELD_TEXT"	=> "text",		// Set to -1 to disable
		"FIELD_SCORE"	=> "score",
		"FIELD_ADD_1"	=> "",
		"FIELD_ADD_2"	=> "",
		"PAST_ONLY"		=> 0,
		"RECORDS_LIMIT"	=> 5,
		"THRESHOLD"		=> 1,
		"WEIGHT_TEXT"	=> 3,
		"WEIGHT_TITLE"	=> 1,
		"WEIGHT_TAG"	=> 0,
		"WEIGHT_CAT"	=> 0,
		"WEIGHT_ADD_1"	=> 1,
		"WEIGHT_ADD_2"	=> 1,
		"STPL_NAME"		=> "system/common/related_content",
	);

	/**
	* Get related content
	*
	* @exmaple
	*	$data = common()->related_content(array(
	*		"action"		=> "fetch", // Action: sql, fetch, stpl
	*		"source_array"	=> $post_info, // array to analyze title and text from
	*		"table_name"	=> db('blog_posts'), // database table name to query
	*		"fields_return"	=> "id, user_id, add_date, title, text, privacy", // array or string of fields to return in resultset
	*		"field_id"		=> "id",
	*		"field_date"	=> "add_date",
	*		"field_title"	=> "title",
	*		"field_text"	=> "text",
	*		"where"			=> "user_id=".intval($post_info["user_id"]), // custom WHERE condition will be added to query
	*	));
	*
	*/
	function _process ($params = array()) {
		// THESE ARE REQUIRED!
		$SOURCE_ARRAY	= $params["source_array"];
		$TABLE_NAME		= $params["table_name"];
		// Missing required params
		if (!$SOURCE_ARRAY) {
			trigger_error("RELATED: empty params['source_array']", E_USER_WARNING);
			return false;
		}
		$WHAT_TO_RETURN	= $params["action"] && in_array($params["action"], array("sql", "fetch", "stpl")) ? $params["action"] : $this->DEF_PARAMS["WHAT_TO_RETURN"];

		$FIELD_ID		= $params["field_id"]		? _es($params["field_id"])	: $this->DEF_PARAMS["FIELD_ID"];
		$FIELD_DATE		= $params["field_date"]		? _es($params["field_date"])	: $this->DEF_PARAMS["FIELD_DATE"];
		$FIELD_USER		= $params["field_user"]		? _es($params["field_user"])	: $this->DEF_PARAMS["FIELD_USER"];
		$FIELD_TITLE	= $params["field_title"]	? _es($params["field_title"]): $this->DEF_PARAMS["FIELD_TITLE"];
		$FIELD_TEXT		= $params["field_text"]		? _es($params["field_text"])	: $this->DEF_PARAMS["FIELD_TEXT"];
		$FIELD_SCORE	= $params["field_score"]	? _es($params["field_score"]): $this->DEF_PARAMS["FIELD_SCORE"];
		// Additional fields for fulltext searching
		$FIELD_ADD_1	= $params["field_add_1"]	? _es($params["field_add_1"]): $this->DEF_PARAMS["FIELD_ADD_1"];
		$FIELD_ADD_2	= $params["field_add_2"]	? _es($params["field_add_2"]): $this->DEF_PARAMS["FIELD_ADD_2"];
		// Title or text is required
		if ((!$FIELD_TITLE || $FIELD_TITLE == -1) && (!$FIELD_TEXT || $FIELD_TEXT == -1)) {
			trigger_error("RELATED: no title and text fields specified", E_USER_WARNING);
			return false;
		}
		$FIELDS_RETURN	= $params["fields_return"]	? $this->_prepare_fields_param($params["fields_return"]) : "";
		if (!$FIELDS_RETURN) {
			$FIELDS_RETURN[] = $FIELD_ID;
			if ($FIELD_DATE && $FIELD_DATE != -1) {
				$FIELDS_RETURN[] = $FIELD_DATE;
			}
			if ($FIELD_USER && $FIELD_USER != -1) {
				$FIELDS_RETURN[] = $FIELD_USER;
			}
			if ($FIELD_TITLE && $FIELD_TITLE != -1) {
				$FIELDS_RETURN[] = $FIELD_TITLE;
			}
			if ($FIELD_TEXT && $FIELD_TEXT != -1) {
				$FIELDS_RETURN[] = $FIELD_TEXT;
			}
			if ($FIELD_ADD_1 && $FIELD_ADD_1 != -1) {
				$FIELDS_RETURN[] = $FIELD_ADD_1;
			}
			if ($FIELD_ADD_2 && $FIELD_ADD_2 != -1) {
				$FIELDS_RETURN[] = $FIELD_ADD_2;
			}
			$FIELDS_RETURN[] = $FIELD_SCORE;
		}

		$WHERE_COND		= $params["where"]			? $params["where"]							: ""; // Not checked. Be careful with this!
		$PAST_ONLY		= $params["past_only"]		? intval((bool)$params["past_only"])		: $this->DEF_PARAMS["PAST_ONLY"];
		if (!$FIELD_DATE || $FIELD_DATE == -1) {
			$PAST_ONLY = false;
		}
		$RECORDS_LIMIT	= $params["limit"]			? intval($params["limit"])					: $this->DEF_PARAMS["RECORDS_LIMIT"];
		$ORDER_BY		= $params["order_by"]		? _es($params["order_by"])	: $FIELD_SCORE. " DESC";
		$STPL_NAME		= $params["stpl_name"]		? $params["stpl_name"]						: $this->DEF_PARAMS["STPL_NAME"];

		$THRESHOLD		= $params["thold"]			? intval($params["thold"])					: $this->DEF_PARAMS["THRESHOLD"];
		$WEIGHT_TEXT	= $params["weight_body"]	? intval($params["weight_body"])			: $this->DEF_PARAMS["WEIGHT_TEXT"];
		$WEIGHT_TITLE	= $params["weight_title"]	? intval($params["weight_title"])			: $this->DEF_PARAMS["WEIGHT_TITLE"];
		// Additional fields
		$WEIGHT_ADD_1	= $params["weight_add_1"]	? intval($params["weight_add_1"])			: $this->DEF_PARAMS["WEIGHT_ADD_1"];
		$WEIGHT_ADD_2	= $params["weight_add_2"]	? intval($params["weight_add_2"])			: $this->DEF_PARAMS["WEIGHT_ADD_2"];
// TODO: complete these
		$WEIGHT_TAG		= $params["weight_tag"]		? intval($params["weight_tag"])				: $this->DEF_PARAMS["WEIGHT_TAG"];
		$WEIGHT_CAT		= $params["weight_cat"]		? intval($params["weight_cat"])				: $this->DEF_PARAMS["WEIGHT_CAT"];
		//-------------------
		// PARSE PARAMS END

		$WEIGHT_TOTAL	= $WEIGHT_TEXT + $WEIGHT_TITLE + $WEIGHT_TAG + $WEIGHT_CAT;
		$WEIGHTED_THOLD	= $THRESHOLD / ($WEIGHT_TOTAL + 0.1);

		$keywords_text = "";
		if ($FIELD_TEXT && $FIELD_TEXT != -1) {
			$keywords_text	= $this->_get_keywords_from_text($SOURCE_ARRAY[$FIELD_TEXT]);
		}
		$keywords_title = "";
		if ($FIELD_TITLE && $FIELD_TITLE != -1) {
			$keywords_title	= $this->_get_keywords_from_text($SOURCE_ARRAY[$FIELD_TITLE]);
		}
		$keywords_add_1 = "";
		if ($FIELD_ADD_1 && $FIELD_ADD_1 != -1) {
			$keywords_add_1	= $this->_get_keywords_from_text($SOURCE_ARRAY[$FIELD_ADD_1]);
		}
		$keywords_add_2 = "";
		if ($FIELD_ADD_2 && $FIELD_ADD_2 != -1) {
			$keywords_add_2	= $this->_get_keywords_from_text($SOURCE_ARRAY[$FIELD_ADD_2]);
		}
		// Keywords required
		if (!strlen($keywords_text) && !strlen($keywords_title) && !strlen($keywords_add_1) && !strlen($keywords_add_2)) {
			return false;
		}
		// Prepare fields to return as string for SQL
		$_tmp = array();
		foreach ((array)$FIELDS_RETURN as $k => $v) {
			$_tmp[$k] = db()->enclose_field_name($v);
		}
		$fields_to_return_sql = implode(", ", $_tmp);
		unset($_tmp);

		$now = time();

		// TODO
		$cats = "";
		$tags = "";

		$sql = 
			"SELECT *, ( 
				score_text	* ".$WEIGHT_TEXT." 
				".(strlen($keywords_title)	? " + score_title	* ".$WEIGHT_TITLE : "")."
				".(strlen($keywords_add_1)	? " + score_add_1	* ".$WEIGHT_ADD_1 : "")."
				".(strlen($keywords_add_2)	? " + score_add_2	* ".$WEIGHT_ADD_2 : "")."
				".($tags					? " + score_tag	* ".$WEIGHT_TAG : "")."
				".($cats					? " + score_cat	* ".$WEIGHT_CAT : "")."
			) AS ".$FIELD_SCORE." 

			FROM ( 
				SELECT ".($fields_to_return_sql ? $fields_to_return_sql : "1")."
					, ".(strlen($keywords_text)	? "(MATCH (".$FIELD_TEXT.") AGAINST ('"._es($keywords_text)."' IN BOOLEAN MODE))" : "0")." AS score_text 
					".(strlen($keywords_title)	? ", (MATCH (".$FIELD_TITLE.") AGAINST ('"._es($keywords_title)."' IN BOOLEAN MODE)) AS score_title " : "")."
					".(strlen($keywords_add_1)	? ", (MATCH (".$FIELD_ADD_1.") AGAINST ('"._es($keywords_add_1)."' IN BOOLEAN MODE)) AS score_add_1 " : "")."
					".(strlen($keywords_add_2)	? ", (MATCH (".$FIELD_ADD_2.") AGAINST ('"._es($keywords_add_2)."' IN BOOLEAN MODE)) AS score_add_2 " : "")."
					".($tags ? ", IFNULL(0/*score_tag*/,0) AS score_tag " : "")."
					".($cats ? ", IFNULL(0/*score_cat*/,0) as score_cat " : "")."
				FROM ".$TABLE_NAME." 
				WHERE ".($WHERE_COND ? $WHERE_COND : "1")." 
					AND ".$FIELD_ID." != ".intval($SOURCE_ARRAY[$FIELD_ID])
					.($PAST_ONLY ? " AND ".$FIELD_DATE." <= '".$now."' " : ' ')
			.") AS rawscores 

			WHERE ( 
				score_text	* ".$WEIGHT_TEXT."
				".(strlen($keywords_title)	? " + score_title	* ".$WEIGHT_TITLE : "")."
				".(strlen($keywords_add_1)	? " + score_add_1 * ".$WEIGHT_ADD_1 : "")."
				".(strlen($keywords_add_2)	? " + score_add_2 * ".$WEIGHT_ADD_2 : "")."
				".($tags					? " + score_tag	* ".$WEIGHT_TAG : "")."
				".($cats					? " + score_cat	* ".$WEIGHT_CAT : "")."
			) >= ".$THRESHOLD."

			ORDER BY ".$ORDER_BY." 

			LIMIT ".$RECORDS_LIMIT;

		// Special for the installer_db (allows to easily restore if not exists FULLTEXT INDEX on used fields)
		$fulltext_needed_for = array();
		if (strlen($keywords_text)) {
			$fulltext_needed_for[] = $TABLE_NAME.".".$FIELD_TEXT;
		}
		if (strlen($keywords_title)) {
			$fulltext_needed_for[] = $TABLE_NAME.".".$FIELD_TITLE;
		}
		if (strlen($keywords_add_1)) {
			$fulltext_needed_for[] = $TABLE_NAME.".".$FIELD_ADD_1;
		}
		if (strlen($keywords_add_2)) {
			$fulltext_needed_for[] = $TABLE_NAME.".".$FIELD_ADD_2;
		}
		conf('fulltext_needed_for', $fulltext_needed_for);

		// Try to pretty format SQL with missing lines
		$sql = str_replace(array("\r", "\n\t\t\t\t\t\n", "\n\t\t\t\t\n", "\n\t\t\t\n"), "\n", $sql);
		$sql = str_replace(array("\n\n\n", "\n\n"), "\n", $sql);
		// RETURN RESULT HERE
		if ($WHAT_TO_RETURN == "sql") {
			return $sql;
		}
		if ($WHAT_TO_RETURN == "fetch") {
			return db()->query_fetch_all($sql, $FIELD_ID);
		}
		if ($WHAT_TO_RETURN == "stpl") {
			$data = db()->query_fetch_all($sql, $FIELD_ID);
			if (!$data) {
				return "";
			}
			// Get users infos
			if ($FIELD_USER && $FIELD_USER != -1) {
				$users_ids = array();
				foreach ((array)$data as $k => $v) {
					if ($v[$FIELD_USER]) {
						$users_ids[$v[$FIELD_USER]] = $v[$FIELD_USER];
					}
				}
				if (!empty($users_ids)) {
					$users_infos = user($users_ids);
				}
			}
			foreach ((array)$data as $k => $v) {
				$data2[$k] = array(
					"id"			=> intval($v[$FIELD_ID]),
					"date"			=> _format_date($v[$FIELD_DATE]),
					"title"			=> _prepare_html($v[$FIELD_TITLE]),
					"text"			=> _prepare_html(_substr($v[$FIELD_TEXT], 0, 200)),
					"add_1"			=> _prepare_html(_substr($v[$FIELD_ADD_1], 0, 200)),
					"add_2"			=> _prepare_html(_substr($v[$FIELD_ADD_2], 0, 200)),
					"user_id"		=> intval($v[$FIELD_USER]),
					"user_name"		=> _prepare_html(_display_name($users_infos[$v[$FIELD_USER]])),
					"profile_link"	=> _profile_link($v[$FIELD_USER]),
					"score"			=> _prepare_html($v[$FIELD_SCORE]),
					"href"			=> process_url("./?object=".$_GET["object"]."&action=".$_GET["action"]."&id=".intval($v[$FIELD_ID])),
				);
			}
			$replace = array(
				"data"		=> $data2,
				"source"	=> _prepare_html($SOURCE_ARRAY),
			);
			return tpl()->parse($STPL_NAME, $replace);
		}
	}

	/**
	* Analyze text, find top keywords and return string ready for use inside "MATCH ... AGAINST ..."
	*/
	function _get_keywords_from_text ($text = "") {

		$num_to_ret = $this->NUM_KEYWORDS;

		$wordlist = preg_split($this->_PATTERN_KWORDS, _strtolower(strip_tags($text)));

		// Build an array of the unique words and number of times they occur.
		$a = array_count_values($wordlist);
	
		// Remove the stop words from the list.
		foreach ((array)$this->STOP_WORDS as $_word) {
			unset($a[$_word]);
		}
	
		// Remove short words from the list.
		foreach ((array)$a as $k => $v) {
			if (_strlen($k) < 4) {
				unset($a[$k]);
			}
		}
		arsort($a, SORT_NUMERIC);
	
		$num_words = count($a);
		$num_to_ret = $num_words > $this->num_to_ret ? $num_to_ret : $num_words;
	
		$outwords = array_slice($a, 0, $num_to_ret);
		$result = implode(' ', array_keys($outwords));
		return $result;
	}

	/**
	* Cleanup and convert fields param value into array
	*/
	function _prepare_fields_param ($fields = "") {
		if (is_array($fields)) {
			return array_unique($fields);
		}
		if (!is_string($fields)) {
			return array();
		}
		$fields_array = array();
		$fields = strtolower(preg_replace("/[^a-z0-9_,]/i", "", trim($fields)));
		foreach ((array)explode(",", $fields) as $_item) {
			$fields_array[$_item] = $_item;
		}
		return $fields_array;
	}
}
