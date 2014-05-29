<?php

/**
* BB codes handler
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_bb_codes {

	/** @var bool BB codes Enabled or not */
	public $ENABLE_BB_CODES	= 1;
	/** @var bool Smilies in bb codes enabled or not */
	public $ENABLE_SMILIES		= 1;
	/** @var bool Smilies in bb codes are like images or only with special CSS class */
	public $SMILIES_AS_IMAGES	= 1;
	/** @var string Smilies folder */
	public $SMILIES_DIR		= 'images/smilies/';
	/** @var bool Try to highlight special text (SQL or HTML) */
	public $USE_HIGHLIGHT		= 1;
	/** @var array CSS classes names @conf_skip */
	public $CSS_CLASSES = array(
		'show1'		=> 'forum1',
		'show2'		=> 'forum2',
		'quote'		=> 'forum_quote',
		'code'		=> 'forum_code',
		'smile'		=> 'forum_smile',
		'topic_a_1'	=> 'row1',
		'topic_a_2'	=> 'row2',
		'topic_u_1'	=> 'row2shaded',
		'topic_u_2'	=> 'row4shaded',
		'post_a_1'	=> 'post2',
		'post_u_1'	=> 'post2shaded',
	);
	/** @var bool Filter 'bad words' or not */
	public $FILTER_BAD_WORDS	= 0;
	/** @var bool Check for long words 'hacking' or not */
	public $CHECK_WORDS_LENGTH = 0;
	/** @var bool Enable extra codes */
	public $ENABLE_EXTRA_CODES = 0;
	/** @var array Default codes on/off */
	public $DEFAULT_SHOW_CODES	= array(
		'font_family'	=> 0,
		'font_size'		=> 1,
		'font_color'	=> 1,
		'extra_fields'	=> 0,
		'help_box'		=> 0,
		'open_tags'		=> 0,
		'youtube'		=> 0,
	);
	/** @var bool Check for unclosed bb codes every time when parsing */
	public $CHECK_CODES_IF_CLOSED	= 0;
	/** @var bool */
	public $USE_NOFOLLOW_TAG		= false;
	/** @var bool Use or not custom bb codes (from db) */
	public $USE_CUSTOM_BB_CODES	= false;
	/** @var string Available bb codes pattern (for closing codes check) */
	public $_avail_codes = '(b|i|u|code|quote|color|size|url|img|sql|html|media|swf|email|imgurl|sub|sup|csv|li|hr|youtube|spoiler)';

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	*/
	function _preload_data () {
		if ($this->_preload_complete) {
			return true;
		}
		if ($this->ENABLE_BB_CODES && $this->ENABLE_SMILIES && !isset($GLOBALS['_smiles_array'])) {
			$GLOBALS['_smiles_array'] = main()->get_data('smilies');
		}
		$nofollow = $this->USE_NOFOLLOW_TAG ? ' rel="nofollow"' : '';
		$this->_preg_bb_codes = array(
			'/\[url=[\"\']{0,1}([^\]]*?)[\"\']{0,1}\](.*?)\[\/url\]/i'=> '<a href="\1" target="blank"'.$nofollow.'>\2</a>',
			'/\[url\](.*?)\[\/url\]/i'								=> '<a href="\1" target="blank"'.$nofollow.'>\1</a>',
			'/\[img\]([^\[]*?)\[\/img\]/i'							=> '<div class="bb_remote_image"><img src="\1"></div>',
			'/\[color=[\"\']*([#\w]+)[\"\']*\]/i'					=> '<span style="color:\1">',
			'/\[size=[\"\']*([#\w]+)[\"\']*\]/i'					=> '<span style="font-size:\1px;">',
			'/\[quote[:=\w]*[\"\']*([\w\s&;-]*)[\"\']*\]/i'			=> '<div>'.t('quote').' <b>\1</b> :</div><div class="'.$this->CSS_CLASSES['quote'].'">',
			'/\[code\]/i'											=> '<pre class="'.$this->CSS_CLASSES['code'].'">',
			'/\[\/(color|size)\]/i'									=> '</span>',
			'/\[\/quote\]/i'										=> '</div>',
			'/\[\/code\]/i'											=> '</pre>',
			'/\[([\/]{0,1})(b|i|u|sub|sup|li)\]/i'					=> '<\1\2>',
			'/\[imgurl=([^\]]*)\]([^\[]*?)\[\/imgurl\]/i'			=> '<a href="\1" target="blank"'.$nofollow.'><img src="\2" border="0"></a>',
			'/\[media\]([^\[]*?)\[\/media\]/i'						=> '<embed name="RAOCXplayer" src="\1">',
			'/\[swf\]([^\[]*?)\[\/swf\]/i'							=> '<object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" codebase="http://fpdownload.macromedia.com/pub/shockwave/cabs/flash/swflash.cab#version=7,0,0,0"><param name="movie" value="\1" /><param name="quality" value="high" /><embed src="\1" quality="high" allowScriptAccess="sameDomain" type="application/x-shockwave-flash" pluginspage="http://www.macromedia.com/go/getflashplayer" /></object>',
			'/\[email\]([^\[]*?)\[\/email\]/i'						=> '<a href="mailto:\1"'.$nofollow.'>\1</a>',
			'/\[hr\]/i'												=> '<hr />',
			'/\[youtube\]([^\[]*?)\[\/youtube\]/i'					=> '<object width="425" height="350"><param name="movie" value="\1"></param><param name="wmode" value="transparent"></param><embed src="\1" type="application/x-shockwave-flash" wmode="transparent" width="425" height="350"></embed></object>',
			'/\[spoiler[=]{0,1}[\"\']*([^\]]*?)[\"\']*\]([^\[]*?)\[\/spoiler\]/i'=> '<div class="spoiler_block"><div class="spoiler_head"><input type="button" class="toggle_button" value="+">\1&nbsp;</div><div class="spoiler_body">\2</div></div>',
		);
		if ($this->USE_CUSTOM_BB_CODES) {
			$custom_codes = main()->get_data('custom_bbcode');
			foreach ((array)$custom_codes as $_custom_tag => $_info) {
				$_custom_tag = preg_quote($_custom_tag);
				$_regex = '/\['.$_custom_tag.'[=]{0,1}([a-z0-9_-]+)'.($_info['useoption'] ? '{0,1}' : '{0}').'\](.*?)\[\/'.$_custom_tag.'\]/ims';
				$this->_preg_bb_codes[$_regex] 	= str_replace(array('{option}', '{content}'), array('\1', '\2'), $_info['replace']);
			}
		}
		// Prepare avail codes string (sort them by name desc)
		$tmp_codes = array();
		foreach (explode('|', substr($this->_avail_codes, 1, -1)) as $_item) {
			$tmp_codes[$_item] = $_item;
		}
		krsort($tmp_codes);
		$this->_avail_codes = '('.implode('|', $tmp_codes).')';

		$this->_preload_complete = true;
	}

	/**
	* Process text to show
	*/
	function _process_text ($body = '', $no_smilies = false, $smilies_as_image = false) {
		if (empty($body)) {
			return '';
		}
		$this->_preload_data();
		$body = str_replace(array('<','>'), array('&lt;','&gt;'), $body);
		if ($this->FILTER_BAD_WORDS) {
			if (!isset($GLOBALS['BAD_WORDS_ARRAY'])) {
				$Q = db()->query('SELECT word FROM '.db('badwords').'');
				while ($A = db()->fetch_assoc($Q)) $GLOBALS['BAD_WORDS_ARRAY'] = $A['word'];
			}
			$body = str_replace($GLOBALS['BAD_WORDS_ARRAY'], '', $body);
		}
		if ($this->CHECK_WORDS_LENGTH) {
			$body = _check_words_length($body);
		}
		if ($this->ENABLE_BB_CODES) {
			if (false !== strpos($body, '[')) {
				if ($this->CHECK_CODES_IF_CLOSED) {
					$body = $this->_force_close_bb_codes($body);
				}
				$body = preg_replace(array_keys($this->_preg_bb_codes), array_values($this->_preg_bb_codes), $body);
				if ($this->USE_HIGHLIGHT) {
					$body = preg_replace('/\[sql\](.*?)\[\/sql\]/imse',		'_class_safe("text_highlight", "classes/common/")->_regex_sql_tag(stripslashes("\1"))', $body);
					$body = preg_replace('/\[html\](.*?)\[\/html\]/imse',	'_class_safe("text_highlight", "classes/common/")->_regex_html_tag(stripslashes("\1"))', $body);
				}
				$body = preg_replace('/\[csv\](.*?)\[\/csv\]/imse',	'$this->_parse_csv_bb_code("\1")', $body);
			}
			if ($this->ENABLE_SMILIES && is_array($GLOBALS['_smiles_array']) && empty($no_smilies)) {
				if (!empty($GLOBALS['_SMILIES_CACHE'])) {
					$this->_smilies_replace = &$GLOBALS['_SMILIES_CACHE'];
				} else {
					$smilies_as_image = 0;
					if ($this->SMILIES_AS_IMAGES) {
						$smilies_as_image = true;
					}
					foreach ((array)$GLOBALS['_smiles_array'] as $smile_info) {
						$replace = array(
							'img_src'	=> WEB_PATH. /*tpl()->TPL_PATH. */$this->SMILIES_DIR. $smile_info['url'],
							'img_alt'	=> _prepare_html($smile_info['emoticon']),
							'css_class'	=> $this->CSS_CLASSES['smile'],
							'text'		=> _prepare_html($smile_info['code']),
							'as_image'	=> intval($smilies_as_image),
						);
						$this->_smilies_replace[$smile_info['code']] = tpl()->parse('system/smile_item', $replace);
					}
					$GLOBALS['_SMILIES_CACHE'] = $this->_smilies_replace;
				}
			}
			if (!empty($this->_smilies_replace)) {
				$body = str_replace(array_keys($this->_smilies_replace), array_values($this->_smilies_replace), $body);
			}
		}
		$body = nl2br($body);
		return $body;
	}

	/**
	*/
	function _cut_bb_codes ($body = '') {
		return preg_replace('/\[[^\]]+\]/ims', '', $body);
	}

	/**
	*/
	function _display_buttons ($input = array()) {
		$this->_preload_data();
		$STPL_NAME = isset($input['stpl_name']) ? $input['stpl_name'] : __CLASS__.'/buttons';
		$js_vars_code = '';
		if (!$GLOBALS['_bb_codes_calls']++) {
			$js_vars_code = tpl()->parse(__CLASS__.'/js_vars', array(
				'max_length'			=> isset($input['max_length'])	? intval($input['max_length']) : 0,
				'bb_codes_js_src'		=> !$GLOBALS['_bb_codes_calls'] ? WEB_PATH.'js/yf_bb_codes.js' : '',
				'display_i18n_js_vars'	=> !$GLOBALS['_bb_codes_calls'] ? 1 : 0,
				'emo_pop_link'			=> './?object=help&action=display_emo_pop',
				'bb_pop_link'			=> './?object=help&action=display_bb_pop',
			));
		}
		return tpl()->parse($STPL_NAME, array(
			'display_font_family'	=> isset($input['font_family']) ? (int)((bool)$input['font_family'])	: $this->DEFAULT_SHOW_CODES['font_family'],
			'display_font_size'		=> isset($input['font_size'])	? (int)((bool)$input['font_size'])		: $this->DEFAULT_SHOW_CODES['font_size'],
			'display_font_color'	=> isset($input['font_color'])	? (int)((bool)$input['font_color'])		: $this->DEFAULT_SHOW_CODES['font_color'],
			'display_extra_fields'	=> isset($input['extra_fields'])? (int)((bool)$input['extra_fields'])	: $this->DEFAULT_SHOW_CODES['extra_fields'],
			'display_youtube'		=> isset($input['youtube'])		? (int)((bool)$input['youtube'])		: $this->DEFAULT_SHOW_CODES['youtube'],
			'display_help_box'		=> isset($input['help_box'])	? (int)((bool)$input['help_box'])		: $this->DEFAULT_SHOW_CODES['help_box'],
			'display_open_tags'		=> isset($input['open_tags'])	? (int)((bool)$input['open_tags'])		: $this->DEFAULT_SHOW_CODES['open_tags'],
			'unique_id'				=> isset($input['unique_id'])	? _prepare_html($input['unique_id'])	: substr(md5(microtime(true).rand()), 0, 8),
			'js_vars_code'			=> $js_vars_code,
		));
	}

	/**
	* Force to close bb codes in given text (prevent design bugging)
	*/
	function _force_close_bb_codes ($text = '') {
		$this->_preload_data();
		$add_text = '';
		$opened_codes = $closed_codes = array();
		// Try to find unclosed codes
		if ($num_opening_bb_codes = preg_match_all('/\['.$this->_avail_codes.'[^\]]*?\]/i', $text, $m_opening)) {
			foreach ((array)$m_opening[1] as $cur_code) {
				$opened_codes[strtolower($cur_code)]++;
			}
		}
		if ($num_closing_bb_codes = preg_match_all('/\[\/'.$this->_avail_codes.'\]/i', $text, $m_closing)) {
			foreach ((array)$m_closing[1] as $cur_code) {
				$closed_codes[strtolower($cur_code)]++;
			}
		}
		// Process opening codes
		$tmp_opened_codes = $opened_codes;
		foreach ((array)$tmp_opened_codes as $cur_code => $num_items) {
			if (!empty($closed_codes[$cur_code])) {
				$opened_codes[$cur_code] -= $closed_codes[$cur_code];
			}
		}
		// Do close codes
		foreach ((array)$opened_codes as $cur_code => $num_items) {
			if ($num_items <= 0) {
				continue;
			}
			$add_text .= str_repeat('[/'.strtoupper($cur_code).']', $num_items);
		}
		$text .= $add_text;

		// Fix youtube URL
		$text = preg_replace('/(\[youtube\]http:\/\/www.youtube.com\/)watch\?v=(\w+)(\[\/youtube\])/ims', '\1v/\2\3', $text);

		return $text;
	}

	/**
	* Try to parse CSV code
	*
	* @example
	* 	[CSV]DocID,ParentID,RefCount,RefDocID,DocCode,DocName
	* 	2,0,1,0,,Text1
	* 	3,2,1,0,NULL,Text2
	* 	4,3,1,0,NULL,Text3[/CSV]
	*/
	function _parse_csv_bb_code ($text = '') {
// TODO: user table()->auto()
		$output		= '';
		$c			= 0;
		$num_cols	= 0;
		foreach (explode("\n", str_replace(array("\r", "<br \/>"), "", $text)) as $cur_line) {
			$data = explode(",", $cur_line);
			if ($c++ == 0) {
				$output .= "<table border=\"1\" cellpadding=\"2\" cellspacing=\"1\" style=\"font: 11px verdana;border-collapse:collapse;border:1px solid gray;margin:5px;\"";
				$num_cols = count($data);
			}
			$output .= "<tr>";
			for ($i = 0; $i < $num_cols; $i++) {
				$output .= "<td bgcolor=\"".($c == 1 ? "#b9ccdf" : "#ffffff")."\">"._prepare_html($data[$i])."</td>";
			}
			$output .= "</tr>";
		}
		$output .= "</table>";
		return $output;
	}

// TODO: use pure-JS highlighter instead of server-based version
	function _geshi_highlight ($text = '', $prog_lang = 'html4strict') {
		require_once (dirname(INCLUDE_PATH).'/yf/__SANDBOX/geshi/geshi.php');
		$text = geshi_highlight($text, $prog_lang, '', 1);
		return $text;
	}
}
