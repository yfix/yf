<?php

/**
* Security class, forked from codeigniter
*/
class yf_security {

	/**
	* List of sanitize filename strings
	*/
	public $filename_bad_chars = array(
		'../', '<!--', '-->', '<', '>',
		"'", '"', '&', '$', '#',
		'{', '}', '[', ']', '=',
		';', '?', '%20', '%22',
		'%3c',		// <
		'%253c',	// <
		'%3e',		// >
		'%0e',		// >
		'%28',		// (
		'%29',		// )
		'%2528',	// (
		'%26',		// &
		'%24',		// $
		'%3f',		// ?
		'%3b',		// ;
		'%3d'		// =
	);

	/**
	* Random Hash for protecting URLs.
	*/
	protected $_xss_hash =	'';

	/**
	* Random hash for Cross Site Request Forgery protection cookie
	*/
	protected $_csrf_hash =	'';

	/**
	* Expiration time for Cross Site Request Forgery protection cookie.
	* Defaults to two hours (in seconds).
	*/
	protected $_csrf_expire =	7200;

	/**
	* Token name for Cross Site Request Forgery protection cookie.
	*/
	protected $_csrf_token_name =	'yf_csrf_token';

	/**
	* Cookie name for Cross Site Request Forgery protection cookie.
	*/
	protected $_csrf_cookie_name =	'yf_csrf_token';

	/**
	* List of never allowed strings
	*/
	protected $_never_allowed_str =	array(
		'document.cookie'	=> '[removed]',
		'document.write'	=> '[removed]',
		'.parentNode'		=> '[removed]',
		'.innerHTML'		=> '[removed]',
		'window.location'	=> '[removed]',
		'-moz-binding'		=> '[removed]',
		'<!--'				=> '&lt;!--',
		'-->'				=> '--&gt;',
		'<![CDATA['			=> '&lt;![CDATA[',
		'<comment>'			=> '&lt;comment&gt;'
	);

	/**
	* List of never allowed regex replacements
	*/
	protected $_never_allowed_regex = array(
		'javascript\s*:',
		'expression\s*(\(|&\#40;)', // CSS and IE
		'vbscript\s*:', // IE, surprise!
		'Redirect\s+302',
		"([\"'])?data\s*:[^\\1]*?base64[^\\1]*?,[^\\1]*?\\1?"
	);

	/**
	* Catch missing method call
	*/
	function __call($name, $args) {
		return main()->extend_call($this, $name, $args);
	}

	/**
	* YF constructor
	*/
	public function _init() {
// TODO: implement CSRF protection into YF core
		$this->_init_csrf();
	}

	/**
	* Sanitizes data so that Cross Site Scripting Hacks can be prevented.  This method does a fair amount of work but
	* it is extremely thorough, designed to prevent even the most obscure XSS attempts.  Nothing is ever 100% foolproof,
	* of course, but I haven't been able to get anything passed the filter.
	*
	* Note: Should only be used to deal with data upon submission. It's not something that should be used for general runtime processing.
	*
	* @link	http://channel.bitflux.ch/wiki/XSS_Prevention
	* 		Based in part on some code and ideas from Bitflux.
	*
	* @link	http://ha.ckers.org/xss.html
	* 		To help develop this script I used this great list of
	*		vulnerabilities along with a few other hacks I've
	*		harvested from examining vulnerabilities in other programs.
	*/
	public function xss_clean($str, $is_image = FALSE) {
		// Is the string an array?
		if (is_array($str)) {
			while (list($key) = each($str)) {
				$str[$key] = $this->xss_clean($str[$key]);
			}
			return $str;
		}
		// Remove Invisible Characters and validate entities in URLs
		$str = $this->_validate_entities($this->remove_invisible_characters($str));

		// URL Decode, Just in case stuff like this is submitted: <a href="http://%77%77%77%2E%67%6F%6F%67%6C%65%2E%63%6F%6D">Google</a>
		// Note: Use rawurldecode() so it does not remove plus signs
		$str = rawurldecode($str);

		// Convert character entities to ASCII
		// This permits our tests below to work reliably. We only convert entities that are within tags since  these are the ones that will pose security problems.
		$str = preg_replace_callback("/[a-z]+=([\'\"]).*?\\1/si", array($this, '_convert_attribute'), $str);
		$str = preg_replace_callback('/<\w+.*/si', array($this, '_decode_entity'), $str);

		// Remove Invisible Characters Again!
		$str = $this->remove_invisible_characters($str);

		// Convert all tabs to spaces
		// This prevents strings like this: ja	vascript
		// NOTE: we deal with spaces between characters later.
		// NOTE: preg_replace was found to be amazingly slow here on large blocks of data, so we use str_replace.
		$str = str_replace("\t", ' ', $str);

		// Capture converted string for later comparison
		$converted_string = $str;

		// Remove strings that are never allowed
		$str = $this->_do_never_allowed($str);

		// Makes PHP tags safe
		// Note: XML tags are inadvertently replaced too: <?xml  But it doesn't seem to pose a problem.
		if ($is_image === TRUE) {
			// Images have a tendency to have the PHP short opening and
			// closing tags every so often so we skip those and only
			// do the long opening tags.
			$str = preg_replace('/<\?(php)/i', '&lt;?\\1', $str);
		} else {
			$str = str_replace(array('<?', '?'.'>'), array('&lt;?', '?&gt;'), $str);
		}

		// Compact any exploded words
		// This corrects words like:  j a v a s c r i p t,  These words are compacted back to their correct state.
		$words = array(
			'javascript', 'expression', 'vbscript', 'script', 'base64',
			'applet', 'alert', 'document', 'write', 'cookie', 'window'
		);
		foreach ($words as $word) {
			$word = implode('\s*', str_split($word)).'\s*';
			// We only want to do this when it is followed by a non-word character
			// That way valid stuff like "dealer to" does not become "dealerto"
			$str = preg_replace_callback('#('.substr($word, 0, -3).')(\W)#is', array($this, '_compact_exploded_words'), $str);
		}

		// Remove disallowed Javascript in links or img tags
		// We used to do some version comparisons and use of stripos for PHP5, but it is dog slow compared to these simplified non-capturing preg_match(), especially if the pattern exists in the string
		do {
			$original = $str;
			if (preg_match('/<a/i', $str)) {
				$str = preg_replace_callback('#<a\s+([^>]*?)(?:>|$)#si', array($this, '_js_link_removal'), $str);
			}
			if (preg_match('/<img/i', $str)) {
				$str = preg_replace_callback('#<img\s+([^>]*?)(?:\s?/?>|$)#si', array($this, '_js_img_removal'), $str);
			}
			if (preg_match('/script|xss/i', $str)) {
				$str = preg_replace('#</*(?:script|xss).*?>#si', '[removed]', $str);
			}
		} while ($original !== $str);
		unset($original);

		// Remove evil attributes such as style, onclick and xmlns
		$str = $this->_remove_evil_attributes($str, $is_image);

		// Sanitize naughty HTML elements
		// If a tag containing any of the words in the list below is found, the tag gets converted to entities.
		// So this: <blink> Becomes: &lt;blink&gt;
		$naughty = 'alert|applet|audio|basefont|base|behavior|bgsound|blink|body|embed|expression|form|frameset|frame|head|html|ilayer|iframe|input|isindex|layer|link|meta|object|plaintext|style|script|textarea|title|video|xml|xss';
		$str = preg_replace_callback('#<(/*\s*)('.$naughty.')([^><]*)([><]*)#is', array($this, '_sanitize_naughty_html'), $str);

		// Sanitize naughty scripting elements
		// Similar to above, only instead of looking for  tags it looks for PHP and JavaScript commands that are disallowed. Rather than removing the
		// code, it simply converts the parenthesis to entities rendering the code un-executable.
		// For example:	eval('some code')  Becomes:	eval&#40;'some code'&#41;
		$str = preg_replace('#(alert|cmd|passthru|eval|exec|expression|system|fopen|fsockopen|file|file_get_contents|readfile|unlink)(\s*)\((.*?)\)#si', '\\1\\2&#40;\\3&#41;', $str);

		// Final clean up. This adds a bit of extra precaution in case something got through the above filters
		$str = $this->_do_never_allowed($str);

		// Images are handled in a special way
		// Essentially, we want to know that after all of the character conversion is done whether any unwanted, likely XSS, code was found. If not, we return TRUE, as the image is clean.
		// However, if the string post-conversion does not matched the string post-removal of XSS, then it fails, as there was unwanted XSS code found and removed/changed during processing.
		if ($is_image === TRUE) {
			return ($str === $converted_string);
		}
		return $str;
	}

	/**
	* Generates the XSS hash if needed and returns it.
	*/
	public function xss_hash() {
		if ($this->_xss_hash === '') {
			$this->_xss_hash = md5(uniqid(mt_rand()));
		}
		return $this->_xss_hash;
	}

	/**
	* HTML Entities Decode
	* A replacement for html_entity_decode()
	*
	* The reason we are not using html_entity_decode() by itself is because while it is not technically correct to leave out the semicolon
	* at the end of an entity most browsers will still interpret the entity correctly. html_entity_decode() does not convert entities without
	* semicolons, so we are left with our own little solution here. Bummer.
	*
	* @link	http://php.net/html-entity-decode
	*/
	public function entity_decode($str, $charset = NULL) {
		if (strpos($str, '&') === FALSE) {
			return $str;
		}
		if (empty($charset)) {
			$charset = conf('charset');
		}
		do {
			$matches = $matches1 = 0;
			$str = html_entity_decode($str, ENT_COMPAT, $charset);
			$str = preg_replace_callback('~&#x(0*[0-9a-f]{2,5})~i', function($m) { return chr(hexdec($m[1])); }, $str, -1, $matches);
			$str = preg_replace_callback('~&#([0-9]{2,4})~', function($m) { return chr($m[1]); }, $str, -1, $matches1);
		} while ($matches OR $matches1);
		return $str;
	}

	/**
	*/
	public function remove_invisible_characters($str, $url_encoded = TRUE) {
		$non_displayables = array();
		// every control character except newline (dec 10),
		// carriage return (dec 13) and horizontal tab (dec 09)
		if ($url_encoded) {
			$non_displayables[] = '/%0[0-8bcef]/';	// url encoded 00-08, 11, 12, 14, 15
			$non_displayables[] = '/%1[0-9a-f]/';	// url encoded 16-31
		}
		$non_displayables[] = '/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S';	// 00-08, 11, 12, 14-31, 127
		do {
			$str = preg_replace($non_displayables, '', $str, -1, $count);
		}
		while ($count);
		return $str;
	}

	/**
	*/
	public function sanitize_filename($str, $relative_path = FALSE) {
		$bad = $this->filename_bad_chars;
		if (!$relative_path) {
			$bad[] = './';
			$bad[] = '/';
		}
		$str = $this->remove_invisible_characters($str, FALSE);
		do {
			$old = $str;
			$str = str_replace($bad, '', $str);
		} while ($old !== $str);
		return stripslashes($str);
	}

	/**
	*/
	public function strip_image_tags($str) {
		return preg_replace(array('#<img\s+.*?src\s*=\s*["\'](.+?)["\'].*?\>#', '#<img\s+.*?src\s*=\s*(.+?).*?\>#'), '\\1', $str);
	}

	/**
	* Callback method for xss_clean() to remove whitespace from things like 'j a v a s c r i p t'.
	*/
	protected function _compact_exploded_words($matches) {
		return preg_replace('/\s+/s', '', $matches[1]).$matches[2];
	}

	/**
	* Remove Evil HTML Attributes (like event handlers and style)
	* It removes the evil attribute and either:
	*  - Everything up until a space. For example, everything between the pipes:
	*	<code>
	*		<a |style=document.write('hello');alert('world');| class=link>
	*	</code>
	*  - Everything inside the quotes. For example, everything between the pipes:
	*	<code>
	*		<a |style="document.write('hello'); alert('world');"| class="link">
	*	</code>
	*/
	protected function _remove_evil_attributes($str, $is_image)	{
		// All javascript event handlers (e.g. onload, onclick, onmouseover), style, and xmlns
		$evil_attributes = array('on\w*', 'style', 'xmlns', 'formaction');
		if ($is_image === TRUE) {
			// Adobe Photoshop puts XML metadata into JFIF images, including namespacing, so we have to allow this for images.
			unset($evil_attributes[array_search('xmlns', $evil_attributes)]);
		}
		do {
			$count = 0;
			$attribs = array();
			// find occurrences of illegal attribute strings with quotes (042 and 047 are octal quotes)
			preg_match_all('/('.implode('|', $evil_attributes).')\s*=\s*(\042|\047)([^\\2]*?)(\\2)/is', $str, $matches, PREG_SET_ORDER);
			foreach ($matches as $attr)	{
				$attribs[] = preg_quote($attr[0], '/');
			}
			// find occurrences of illegal attribute strings without quotes
			preg_match_all('/('.implode('|', $evil_attributes).')\s*=\s*([^\s>]*)/is', $str, $matches, PREG_SET_ORDER);
			foreach ($matches as $attr)	{
				$attribs[] = preg_quote($attr[0], '/');
			}
			// replace illegal attribute strings that are inside an html tag
			if (count($attribs) > 0) {
				$str = preg_replace('/(<?)(\/?[^><]+?)([^A-Za-z<>\-])(.*?)('.implode('|', $attribs).')(.*?)([\s><]?)([><]*)/i', '$1$2 $4$6$7$8', $str, -1, $count);
			}
		} while ($count);
		return $str;
	}

	/**
	* Callback method for xss_clean() to remove naughty HTML elements.
	*/
	protected function _sanitize_naughty_html($matches)	{
		// encode opening brace
		return '&lt;'.$matches[1].$matches[2].$matches[3]
			// encode captured opening or closing brace to prevent recursive vectors:
			.str_replace(array('>', '<'), array('&gt;', '&lt;'), $matches[4]);
	}

	/**
	* Callback method for xss_clean() to sanitize links.
	* This limits the PCRE backtracks, making it more performance friendly and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in PHP 5.2+ on link-heavy strings.
	*/
	protected function _js_link_removal($match) {
		return str_replace($match[1], preg_replace(
			'#href=.*?(?:alert\(|alert&\#40;|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|data\s*:)#si',
			'',
			$this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]))
		),
		$match[0]);
	}

	/**
	* Callback method for xss_clean() to sanitize image tags.
	* This limits the PCRE backtracks, making it more performance friendly and prevents PREG_BACKTRACK_LIMIT_ERROR from being triggered in PHP 5.2+ on image tag heavy strings.
	*/
	protected function _js_img_removal($match) {
		return str_replace($match[1], preg_replace(
			'#src=.*?(?:alert\(|alert&\#40;|javascript:|livescript:|mocha:|charset=|window\.|document\.|\.cookie|<script|<xss|base64\s*,)#si',
			'',
			$this->_filter_attributes(str_replace(array('<', '>'), '', $match[1]))
		),
		$match[0]);
	}

	/**
	*/
	protected function _convert_attribute($match) {
		return str_replace(array('>', '<', "\\"), array('&gt;', '&lt;', "\\\\"), $match[0]);
	}

	/**
	* Filters tag attributes for consistency and safety.
	*/
	protected function _filter_attributes($str) {
		$out = '';
		if (preg_match_all('#\s*[a-z\-]+\s*=\s*(\042|\047)([^\\1]*?)\\1#is', $str, $matches)) {
			foreach ($matches[0] as $match) {
				$out .= preg_replace('#/\*.*?\*/#s', '', $match);
			}
		}
		return $out;
	}

	/**
	* HTML Entity Decode Callback
	*/
	protected function _decode_entity($match) {
		return $this->entity_decode($match[0], strtoupper(conf('charset')));
	}

	/**
	* Validate URL entities
	*/
	protected function _validate_entities($str)	{
		// Protect GET variables in URLs, Example: 901119URL5918AMP18930PROTECT8198
		$str = preg_replace('|\&([a-z\_0-9\-]+)\=([a-z\_0-9\-]+)|i', $this->xss_hash().'\\1=\\2', $str);
		// Validate standard character entities.
		// Add a semicolon if missing.  We do this to enable the conversion of entities to ASCII later.
		$str = preg_replace('#(&\#?[0-9a-z]{2,})([\x00-\x20])*;?#i', '\\1;\\2', $str);
		// Validate UTF16 two byte encoding (x00). Just as above, adds a semicolon if missing.
		$str = preg_replace('#(&\#x?)([0-9A-F]+);?#i', '\\1\\2;', $str);
		// Un-Protect GET variables in URLs
		return str_replace($this->xss_hash(), '&', $str);
	}

	/**
	*/
	protected function _do_never_allowed($str) {
		$str = str_replace(array_keys($this->_never_allowed_str), $this->_never_allowed_str, $str);
		foreach ($this->_never_allowed_regex as $regex) {
			$str = preg_replace('#'.$regex.'#is', '[removed]', $str);
		}
		return $str;
	}

	/**
	*/
	protected function _init_csrf() {
		// Is CSRF protection enabled?
		if (conf('csrf_protection') === TRUE) {
			// CSRF config
			foreach (array('csrf_expire', 'csrf_token_name', 'csrf_cookie_name') as $key) {
				if (FALSE !== ($val = conf($key))) {
					$this->{'_'.$key} = $val;
				}
			}
			// Append application specific cookie prefix
			if (conf('cookie_prefix')) {
				$this->_csrf_cookie_name = conf('cookie_prefix').$this->_csrf_cookie_name;
			}
			// Set the CSRF hash
			$this->_csrf_set_hash();
		}
	}

	/**
	*/
	public function csrf_verify() {
		// If it's not a POST request we will set the CSRF cookie
		if (strtoupper($_SERVER['REQUEST_METHOD']) !== 'POST') {
			return $this->csrf_set_cookie();
		}
		// Check if URI has been whitelisted from CSRF checks
		if ($exclude_uris = conf('csrf_exclude_uris')) {
// TODO: check and enable
#			$uri = load_class('URI', 'core');
#			if (in_array($uri->uri_string(), $exclude_uris)) {
#				return $this;
#			}
		}
		// Do the tokens exist in both the _POST and _COOKIE arrays?
		if ( ! isset($_POST[$this->_csrf_token_name], $_COOKIE[$this->_csrf_cookie_name]) || $_POST[$this->_csrf_token_name] !== $_COOKIE[$this->_csrf_cookie_name]) {
			$this->csrf_show_error();
		}

		// We kill this since we're done and we don't want to polute the _POST array
		unset($_POST[$this->_csrf_token_name]);

		// Regenerate on every submission?
		if (conf('csrf_regenerate')) {
			// Nothing should last forever
			unset($_COOKIE[$this->_csrf_cookie_name]);
			$this->_csrf_hash = '';
		}

		$this->_csrf_set_hash();
		$this->csrf_set_cookie();

		return $this;
	}

	/**
	*/
	public function csrf_set_cookie() {
		$expire = time() + $this->_csrf_expire;
		$secure_cookie = (bool) conf('cookie_secure');
#		if ($secure_cookie && ! is_https())	{
#			return FALSE;
#		}
		setcookie(
			$this->_csrf_cookie_name,
			$this->_csrf_hash,
			$expire/*,
			conf('cookie_path'),
			conf('cookie_domain'),
			$secure_cookie,
			conf('cookie_httponly')*/
		);
		return $this;
	}

	/**
	*/
	public function csrf_show_error() {
		_e('The action you have requested is not allowed.');
	}

	/**
	*/
	public function get_csrf_hash() {
		return $this->_csrf_hash;
	}

	/**
	*/
	public function get_csrf_token_name() {
		return $this->_csrf_token_name;
	}

	/**
	* Set CSRF Hash and Cookie
	*/
	protected function _csrf_set_hash() {
		if ($this->_csrf_hash === '') {
			// If the cookie exists we will use it's value.
			// We don't necessarily want to regenerate it with
			// each page load since a page could contain embedded
			// sub-pages causing this feature to fail
			if (isset($_COOKIE[$this->_csrf_cookie_name]) && preg_match('#^[0-9a-f]{32}$#iS', $_COOKIE[$this->_csrf_cookie_name]) === 1) {
				return $this->_csrf_hash = $_COOKIE[$this->_csrf_cookie_name];
			}
			$this->_csrf_hash = md5(uniqid(rand(), TRUE));
			$this->csrf_set_cookie();
		}
		return $this->_csrf_hash;
	}
}
