var userAgent = navigator.userAgent.toLowerCase();
var is_opera  = ((userAgent.indexOf('opera') != -1) || (typeof(window.opera) != 'undefined'));
var is_saf	= ((userAgent.indexOf('applewebkit') != -1) || (navigator.vendor == 'Apple Computer, Inc.'));
var is_webtv  = (userAgent.indexOf('webtv') != -1);
var is_ie	 = ((userAgent.indexOf('msie') != -1) && (!is_opera) && (!is_saf) && (!is_webtv));
var is_ie4	= ((is_ie) && (userAgent.indexOf('msie 4.') != -1));
var is_moz	= ((navigator.product == 'Gecko') && (!is_saf));
var is_kon	= (userAgent.indexOf('konqueror') != -1);
var is_ns	 = ((userAgent.indexOf('compatible') == -1) && (userAgent.indexOf('mozilla') != -1) && (!is_opera) && (!is_webtv) && (!is_saf));
var is_ns4	= ((is_ns) && (parseInt(navigator.appVersion) == 4));
var is_mac	= (userAgent.indexOf('mac') != -1);

// yf_PHP_Emulator class

/**
* PHP Function Emulator Class
*/
function yf_PHP_Emulator()
{
}

// yf_PHP_Emulator Methods

/**
* Find a string within a string (case insensitive)
*
* @param	string	Haystack
* @param	string	Needle
* @param	integer	Offset
*
* @return	mixed	Not found: false / Found: integer position
*/
yf_PHP_Emulator.prototype.stripos = function(haystack, needle, offset) {
	if (typeof offset == 'undefined') {
		offset = 0;
	}
	index = haystack.toLowerCase().indexOf(needle.toLowerCase(), offset);
	return (index == -1 ? false : index);
}

/**
* Trims leading whitespace
*
* @param	string	String to trim
*
* @return	string
*/
yf_PHP_Emulator.prototype.ltrim = function(str) {
	return str.replace(/^\s+/g, '');
}

/**
* Trims trailing whitespace
*
* @param	string	String to trim
*
* @return	string
*/
yf_PHP_Emulator.prototype.rtrim = function(str) {
	return str.replace(/(\s+)$/g, '');
}

/**
* Trims leading and trailing whitespace
*
* @param	string	String to trim
*
* @return	string
*/
yf_PHP_Emulator.prototype.trim = function(str) {
	return this.ltrim(this.rtrim(str));
}

/**
* Emulation of PHP's preg_quote()
*
* @param	string	String to process
*
* @return	string
*/
yf_PHP_Emulator.prototype.preg_quote = function(str) {
	// replace + { } ( ) [ ] | / ? ^ $ \ . = ! < > : * with backslash+character
	return str.replace(/(\+|\{|\}|\(|\)|\[|\]|\||\/|\?|\^|\$|\\|\.|\=|\!|\<|\>|\:|\*)/g, "\\$1");
}

/**
* Emulates unhtmlspecialchars in yf_ulletin
*
* @param	string	String to process
*
* @return	string
*/
yf_PHP_Emulator.prototype.unhtmlspecialchars = function(str) {
	f = new Array(/&lt;/g, /&gt;/g, /&quot;/g, /&amp;/g);
	r = new Array('<', '>', '"', '&');
	for (var i in f) {
		str = str.replace(f[i], r[i]);
	}
	return str;
}

/**
* Unescape CDATA from yf__AJAX_XML_Builder PHP class
*
* @param	string	Escaped CDATA
*
* @return	string
*/
yf_PHP_Emulator.prototype.unescape_cdata = function(str) {
	var r1 = /<\=\!\=\[\=C\=D\=A\=T\=A\=\[/g;
	var r2 = /\]\=\]\=>/g;
	return str.replace(r1, '<![CDATA[').replace(r2, ']]>');
}

/**
* Emulates PHP's htmlspecialchars()
*
* @param	string	String to process
*
* @return	string
*/
yf_PHP_Emulator.prototype.htmlspecialchars = function(str) {
	//var f = new Array(/&(?!#[0-9]+;)/g, /</g, />/g, /"/g);
	var f = new Array(
		(is_mac && is_ie ? new RegExp('&', 'g') : new RegExp('&(?!#[0-9]+;)', 'g')),
		new RegExp('<', 'g'),
		new RegExp('>', 'g'),
		new RegExp('"', 'g')
	);
	var r = new Array(
		'&amp;',
		'&lt;',
		'&gt;',
		'&quot;'
	);
	for (var i = 0; i < f.length; i++) {
		str = str.replace(f[i], r[i]);
	}
	return str;
}

/**
* Searches an array for a value
*
* @param	string	Needle
* @param	array	Haystack
* @param	boolean	Case insensitive
*
* @return	integer	Not found: -1 / Found: integer index
*/
yf_PHP_Emulator.prototype.in_array = function(ineedle, haystack, caseinsensitive) {
	var needle = new String(ineedle);
	if (caseinsensitive) {
		needle = needle.toLowerCase();
		for (var i in haystack) {
			if (haystack[i].toLowerCase() == needle) {
				return i;
			}
		}
	} else {
		for (var i in haystack)	{
			if (haystack[i] == needle) {
				return i;
			}
		}
	}
	return -1;
}

/**
* Emulates PHP's strpad()
*
* @param	string	Text to pad
* @param	integer	Length to pad
* @param	string	String with which to pad
*
* @return	string
*/
yf_PHP_Emulator.prototype.str_pad = function(text, length, padstring) {
	text = new String(text);
	padstring = new String(padstring);
	if (text.length < length) {
		padtext = new String(padstring);
		while (padtext.length < (length - text.length)) {
			padtext += padstring;
		}
		text = padtext.substr(0, (length - text.length)) + text;
	}
	return text;
}

/**
* A sort of emulation of PHP's urlencode - not 100% the same, but accomplishes the same thing
*
* @param	string	String to encode
*
* @return	string
*/
yf_PHP_Emulator.prototype.urlencode = function(text) {
	text = text.toString();
	// this escapes 128 - 255, as JS uses the unicode code points for them.
	// This causes problems with submitting text via AJAX with the UTF-8 charset.
	var matches = text.match(/[\x90-\xFF]/g);
	if (matches) {
		for (var matchid = 0; matchid < matches.length; matchid++) {
			var char_code = matches[matchid].charCodeAt(0);
			text = text.replace(matches[matchid], '%u00' + (char_code & 0xFF).toString(16).toUpperCase());
		}
	}
	return escape(text).replace(/\+/g, "%2B");
}

/**
* Works a bit like ucfirst, but with some extra options
*
* @param	string	String with which to work
* @param	string	Cut off string before first occurence of this string
*
* @return	string
*/
yf_PHP_Emulator.prototype.ucfirst = function(str, cutoff) {
	if (typeof cutoff != 'undefined') {
		var cutpos = str.indexOf(cutoff);
		if (cutpos > 0)	{
			str = str.substr(0, cutpos);
		}
	}
	str = str.split(' ');
	for (var i = 0; i < str.length; i++) {
		str[i] = str[i].substr(0, 1).toUpperCase() + str[i].substr(1);
	}
	return str.join(' ');
}

// initialize the PHP emulator
var PHP = new yf_PHP_Emulator();