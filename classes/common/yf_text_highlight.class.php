<?php

/**
* Custom text hightlighting methods
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_text_highlight {

	/**
	* HTML syntax highlighting
	*/
	function _regex_html_tag($html = "") {
		if (empty($html)) return false;
		// Too many embedded code/quote/html/sql tags can crash Opera and Moz
		if (preg_match("/\[(quote|code|html|sql)\].+?\[(quote|code|html|sql)\].+?\[(quote|code|html|sql)\].+?\[(quote|code|html|sql)\].+?\[(quote|code|html|sql)\]/i", $html)) {
			return $html;
		}
		// Take a stab at removing most of the common
		// smilie characters.
		$html = preg_replace("#:#"	, "&#58;", $html);
		$html = preg_replace("#\[#"	, "&#91;", $html);
		$html = preg_replace("#\]#"	, "&#93;", $html);
		$html = preg_replace("#\)#"	, "&#41;", $html);
		$html = preg_replace("#\(#"	, "&#40;", $html);
		
		$html = preg_replace("/^<br>/"	, "", $html);
		$html = preg_replace("#^<br />#", "", $html);
		$html = preg_replace("/^\s+/"	, "", $html);
		
		$html = preg_replace("#&lt;([^&<>]+)&gt;#"								, "&lt;<span style='color:blue'>\\1</span>&gt;"			, $html); // Matches <tag>
		$html = preg_replace("#&lt;([^&<>]+)=#"									, "&lt;<span style='color:blue'>\\1</span>="			, $html); // Matches <tag
		$html = preg_replace("#&lt;/([^&]+)&gt;#"								, "&lt;/<span style='color:blue'>\\1</span>&gt;"		, $html); // Matches </tag>
		$html = preg_replace("!=(&quot;|&#39;)(.+?)?(&quot;|&#39;)(\s|&gt;)!"	, "=\\1<span style='color:orange'>\\2</span>\\3\\4"		, $html); // Matches ='this'
		$html = preg_replace("!&#60;&#33;--(.+?)--&#62;!"						, "&lt;&#33;<span style='color:red'>--\\1--</span>&gt;"	, $html);
		
		$wrap = $this->_wrap_style('html');
		
		return $wrap['START']. $html. $wrap['END'];
	}
		
	/**
	* SQL syntax highlighting
	*/
	function _regex_sql_tag($sql = "") {
		if (empty($sql)) return false;
		// Too many embedded code/quote/html/sql tags can crash Opera and Moz
		if (preg_match("/\[(quote|code|html|sql)\].+?\[(quote|code|html|sql)\].+?\[(quote|code|html|sql)\].+?\[(quote|code|html|sql)\].+?\[(quote|code|html|sql)\].+?\[(quote|code|html|sql)\].+?\[(quote|code|html|sql)\].+?\[(quote|code|html|sql)\].+?\[(quote|code|html|sql)\]/i", $sql)) {
			return $sql;
		}
		// Knock off any preceeding newlines (which have
		// since been converted into <br>)
		$sql = preg_replace("/^<br>/"	, "", $sql);
		$sql = preg_replace("#^<br />#"	, "", $sql);
		$sql = preg_replace("/^\s+/"	, "", $sql);
		// Make certain regex work..
		if (!preg_match( "/\s+$/" , $sql)) $sql = $sql.' ';
		
		$sql = preg_replace("#(=|\+|\-|&gt;|&lt;|~|==|\!=|LIKE|NOT LIKE|REGEXP)#i"	, "<span style='color:orange'>\\1</span>", $sql);
		$sql = preg_replace("#(MAX|AVG|SUM|COUNT|MIN)\(#i"							, "<span style='color:blue'>\\1</span>(", $sql);
		$sql = preg_replace("!(&quot;|&#39;|&#039;)(.+?)(&quot;|&#39;|&#039;)!i"	, "<span style='color:red'>\\1\\2\\3</span>", $sql);
		$sql = preg_replace("#\s{1,}(AND|OR)\s{1,}#i"								, " <span style='color:blue'>\\1</span> ", $sql);
		$sql = preg_replace("#(LEFT|JOIN|WHERE|MODIFY|CHANGE|AS|DISTINCT|IN|ASC|DESC|ORDER BY)\s{1,}#i" , "<span style='color:green'>\\1</span> ", $sql);
		$sql = preg_replace("#LIMIT\s*(\d+)\s*,\s*(\d+)#i"							, "<span style='color:green'>LIMIT</span> <span style='color:orange'>\\1, \\2</span>", $sql);
		$sql = preg_replace("#(FROM|INTO)\s{1,}(\S+?)\s{1,}#i"						, "<span style='color:green'>\\1</span> <span style='color:orange'>\\2</span> ", $sql);
		$sql = preg_replace("#(SELECT|INSERT|UPDATE|DELETE|ALTER TABLE|DROP)#i"		, "<span style='color:blue;font-weight:bold'>\\1</span>", $sql);
		
		$html = $this->_wrap_style('sql');
		
		return $html['START']. $sql. $html['END'];
	}
	
	/**
	* wrap style:code and quote table HTML generator
	*/
	function _wrap_style($type = 'quote', $extra = "") {
		$used = array(
		   'quote' => array(
				'title'		=> 'QUOTE',
				'css_top'	=> 'quotetop',
				'css_main'	=> 'quotemain',
			),
		   'code'  => array(
				'title'		=> 'CODE',
				'css_top'	=> 'codetop',
				'css_main'	=> 'codemain',
			),
		   'sql'   => array(
				'title'		=> 'SQL',
				'css_top'	=> 'sqltop',
				'css_main'	=> 'sqlmain',
			),
		   'html'  => array(
				'title'		=> 'HTML',
				'css_top'	=> 'htmltop',
				'css_main'	=> 'htmlmain',
			),
		);
		return array(
			'START' => "<div class='".$used[$type]['css_top']."'>".$used[$type]['title']. $extra."</div><div class='".$used[$type]['css_main']."'>",
			'END'   => "</div>"
		);
	}
}
