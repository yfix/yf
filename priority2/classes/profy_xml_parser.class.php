<?php

/**
* This class contains all generic functions to handle
* the creation of XML documents and the parsing of XML documents
*
* Example Usage:
* <code>
* <productlist name="myname" version="1.0">
*  <productgroup name="thisgroup">
*   <product id="1.0">
*    <description>This is a descrption</description>
*    <title>Baked Beans</title>
*    <room store="1">103</room>
*   </product>
*  </productgroup>
* </productlist>
* 
* // Set the root tag
* $xml->xml_set_root( 'productlist', array( 'name' => 'myname', 'version' => '1.0' ) );
* 
* // Add a group
* $xml->xml_add_group( 'productgroup', array( 'name' => 'thisgroup' ) );
* 
* // Build entry content
* $content[] = $xml->xml_build_simple_tag( 'description', "This is a descrption" );
* $content[] = $xml->xml_build_simple_tag( 'title'      , "Baked Beans"          );
* $content[] = $xml->xml_build_simple_tag( 'room'       , '103'         , array( 'store' => 1 ) );
* 
* // Build entry
* $entry[]   = $xml->xml_build_entry( 'product', $content, array( 'id' => '1.0' ) );
* 
* // Add to group 'productlist'
* $xml->xml_add_entry_to_group( 'productgroup', $entry );
* 				
* // Format..
* $xml->xml_format_document();
* 
* // Get XML document
* $filecontents = $xml->xml_document;
* 
* // Parse XML document
* $xml->xml_parse_document( $filecontents );
* 
* // Show XML array
* print_r( $xml->xml_array );
* 
* // print "Baked Beans";
* print $xml->xml_array['productlist']['productgroup']['product'][0]['title']['VALUE'];		  
* 
* // print "1";
* print $xml->xml_array['productlist']['productgroup']['product'][0]['room']['ATTRIBUTES']['store'];	
* </code>
*
*/

/**
* XML Creation and Extraction Functions
*
* Methods and functions for handling XML documents
*
*/
class profy_xml_parser {

	/**
	* XML header
	*
	* @var string
	*/
	var $header            = "";
	
	/**
	* Root tag name
	*
	* @var string
	*/
	var $root_tag          = '';
	
	/**
	* Root attributes
	*
	* @var string
	*/
	var $root_attributes   = "";
	
	/**
	* Array of entries
	*
	* @var array
	*/
	var $entries           = array();
	
	/**
	* String of compiled XML document
	*
	* @var string
	*/
	var $xml_document      = "";
	
	/**
	* Work variable
	*
	* @var int
	*/
	var $depth             = 0;
	
	/**
	* Tmp doc, used during creation
	*
	* @var string
	*/
	var $tmp_doc           = "";
	
	/**
	* Tag groups
	*
	* @var string
	*/
	var $groups            = "";
	
	/**
	* Index numerically flag
	*
	* @var int
	*/
	var $index_numeric     = 0;
	
	/**
	* Collapse duplicate tags flag
	*
	* @var int
	*/
	var $collapse_dups     = 1;
	
	/**
	* Main XML array of parsed components
	*
	* @var array
	*/
	var $xml_array         = array();
	
	/**
	* Collapse newlines in CDATA tags
	*
	* @var int
	*/
	var $collapse_newlines = 1;
	
	/**
	* Use lite parser flag
	*
	* @var int
	*/
	var $lite_parser       = 1;
	
	/**
	* DOC type
	*
	* @var string
	*/
	var $doc_type			= 'utf-8';
	
	/**
	* Constructor
	*
	* @param
	*/
	function profy_xml_parser() {
		// Turn off Lite XML parser if PHP version lower than 5
		if (version_compare(phpversion(), "5.0.0") != 1) {
			$this->lite_parser = 0;
		}
		//$this->header = '<?xml version="1.0" encoding="'.$this->doc_type.'"?'.'>';
	}
	
	/**
	* Parse an XML document into an array of field and values
	*
	* @param	string	Raw XML Data
	* @return	void
	*/
	function xml_parse_document( $xml )	{
		$i = -1;
		// Use "lite" parser
		if ( $this->lite_parser ) {
			$lite = new xml_lite_parse();
			$lite->xml_parse_it( $xml );
			$this->xml_array = $this->_xml_get_children( $lite->stack, $i );
			// Free willy..er..memory
			$lite->garbage_collect();
		// Use PHP EXPAT Parser?
		} else {
			$parser = xml_parser_create();
			xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, 0);
			xml_parser_set_option($parser, XML_OPTION_SKIP_WHITE  , 0);
			xml_parse_into_struct($parser, $xml, $vals); 
			xml_parser_free($parser);
			$this->xml_array = $this->_xml_get_children($vals, $i);
		}
		// Garbage collect
		unset( $vals );
		unset( $xml );
	}
	
	/**
	* Parse an array into an XML document
	*
	* @param	array	Entry array
	* @return	void
	*/
	function xml_format_document($entry = array()) {
		$this->header = '<?xml version="1.0" encoding="'.$this->doc_type.'"?'.'>';
		$this->xml_document = $this->header ? $this->header."\n" : '';
		$this->xml_document .= "<".$this->root_tag.$this->root_attributes.">\n";
		$this->xml_document .= $this->tmp_doc;
		$this->xml_document .= "\n</".$this->root_tag.">";
		$this->tmp_doc       = "";
	}
	
	/**
	* Set the root tag
	*
	* @param	string	Root tag name
	* @param	array	Root tag attributes
	* @return	void
	*/
	function xml_set_root($tag, $attributes = array()) {
		$this->root_tag        = $tag;
		$this->root_attributes = $this->_xml_build_attribute_string( $attributes );
	}
	
	/**
	* Add entry to XML group
	*
	* @param	string	Tag name
	* @param	array	Entry values
	* @return	void
	*/
	function xml_add_entry_to_group($tag, $entry = array()) {
		$this->tmp_doc .= "\t".$this->groups[ $tag ];
		if (is_array($entry) and count($entry)) {
			foreach ( $entry as $e ) {
				$this->tmp_doc .=  "\n\t\t".$e."\n";
			}
		}
		$this->tmp_doc .= "\t</".$tag.">\n";
	}
	
	/**
	* Build an XML entry
	*
	* @param	string	Tag name
	* @param	array	Content array
	* @param	array	Attributes
	* @return	string
	*/
	function xml_build_entry($tag, $content = array(), $attributes = array()) {
		$entry = "<" . $tag . $this->_xml_build_attribute_string($attributes) . ">\n";
		foreach ((array)$content as $c ) {
			$entry .= "\t\t\t".$c."\n";
		}
		$entry .= "\t\t</" . $tag . ">";
		return $entry;
	}
	
	/**
	* Add a group to an XML document
	*
	* @param	string	Tag name
	* @param	array	Attributes
	* @return	string
	*/
	function xml_add_group($tag, $attributes = array()) {
		$this->groups[ $tag ] = "<" . $tag . $this->_xml_build_attribute_string($attributes) . ">";
	}
	
	/**
	* Build and XML simple tag
	*
	* @param	string	Tag name
	* @param	string	Tag value
	* @param	array	Attributes
	* @return	string
	*/
	function xml_build_simple_tag($tag, $description = "", $attributes = array()) {
		return "<" . $tag . $this->_xml_build_attribute_string($attributes) . ">" . $this->_xml_encode_string($description) . "</" . $tag . ">";
	}
	
	/**
	* Build tree node
	*
	* @param	array	Values
	* @param	array	Values
	* @param	string	Counter
	* @param	string	Tag type
	* @return	array
	*/
	function _xml_build_tag($thisvals, $vals, &$i, $type) {
		$tag = array();
		if (isset($thisvals['attributes'])) {
			$tag['ATTRIBUTES'] = $this->_xml_decode_attribute($thisvals['attributes']); 
		}
		if ($type === 'complete') {
			$tag['VALUE'] = $this->_xml_unconvert_safecdata($thisvals['value']);
		} else {
			$tag = array_merge( $tag, $this->_xml_get_children($vals, $i) );
		}
		return $tag;
	}
	
	/**
	* Build a nested array of children
	*
	* @param	array	Values
	* @param	string	Counter
	* @return	array
	*/
	function _xml_get_children($vals, &$i) {
		$children = array();
		// CDATA before children
		if ($i > -1 && isset($vals[$i]['value'])) {
			$children['VALUE'] = $this->_xml_unconvert_safecdata( $vals[$i]['value'] );
		}
		// Loopy loo
		while(++$i < count($vals)) { 
			$type = $vals[$i]['type'];
			// CDATA after children
			if ($type === 'cdata') {
				$children['VALUE'] .= $this->_xml_unconvert_safecdata( $vals[$i]['value'] );
			}
			// COMPLETE: At end of current branch
			// OPEN:    Node has children, recurse
			else if ( $type === 'complete' OR $type === 'open' ) {
				$tag = $this->_xml_build_tag( $vals[$i], $vals, $i, $type );
				if ( $this->index_numeric )	{
					$tag['TAG'] = $vals[$i]['tag'];
					$children[] = $tag;
				}
				else
				{
					$children[$vals[$i]['tag']][] = $tag;
				}
			}
			// End of node?
			else if ($type === 'close')
			{
				break;
			}
		}
		
		if ( $this->collapse_dups )
		{
			foreach ( $children as $key => $value )
			{
				if ( is_array($value) && (count($value) == 1) )
				{
					$children[$key] = $value[0];
				}
			}
		}
		return $children;
	} 
	
	/**
	* Builds attribute string
	*
	* @param	array	Values
	* @return	string
	*/
	function _xml_build_attribute_string( $array = array() ) {
		if ( is_array( $array ) and count( $array ) ) {
			$string = array();
			foreach ( $array as $k => $v ) {
				$v = trim( $this->_xml_encode_attribute($v) );
				$string[] = $k.'="'.$v.'"';
			}
			return ' ' . implode( " ", $string );
		}
	}
	
	/**
	* Encode XML attribute (Make safe for transport)
	*
	* @param	string	Raw data
	* @return	string	Converted Data
	*/
	function _xml_encode_attribute( $t ) {
		$t = preg_replace("/&(?!#[0-9]+;)/s", '&amp;', $t );
		$t = str_replace( "<", "&lt;"  , $t );
		$t = str_replace( ">", "&gt;"  , $t );
		$t = str_replace( '"', "&quot;", $t );
		$t = str_replace( "'", '&#039;', $t );
		return $t;
	}

	/**
	* Decode XML attribute (Make safe for transport)
	*
	* @param	string	Raw data
	* @return	string	Converted Data
	*/
	function _xml_decode_attribute( $t ) {
		$t = str_replace( "&amp;" , "&", $t );
		$t = str_replace( "&lt;"  , "<", $t );
		$t = str_replace( "&gt;"  , ">", $t );
		$t = str_replace( "&quot;", '"', $t );
		$t = str_replace( "&#039;", "'", $t );
		return $t;
	}

	/**
	* Encode XML attribute (Make safe for transport)
	* Encodes a string to make it safe (uses cdata)
	*
	* @param	string	Raw data
	* @return	string	Converted Data
	*/
	function _xml_encode_string( $v ) {
		if ( preg_match( "/['\"\[\]<>&]/", $v ) ) {
			$v = "<![CDATA[" . $this->_xml_convert_safecdata($v) . "]]>";
		}
		if ( $this->collapse_newlines ) {
			$v = str_replace( "\r\n", "\n", $v );
		}
		return $v;
	}

	/**
	* Encode CDATA XML attribute (Make safe for transport)
	* Ensures no embedding of cdata
	*
	* @param	string	Raw data
	* @return	string	Converted Data
	*/
	function _xml_convert_safecdata( $v ) {
		// Legacy
		//$v = str_replace( "<![CDATA[", "<!¢|CDATA|", $v );
		//$v = str_replace( "]]>"      , "|¢]>"      , $v );
		// New
		$v = str_replace( "<![CDATA[", "<!#^#|CDATA|", $v );
		$v = str_replace( "]]>"      , "|#^#]>"      , $v );
		return $v;
	}

	/**
	* Decode CDATA XML attribute (Make safe for transport)
	* Uncoverts safe embedding
	*
	* @param	string	Raw data
	* @return	string	Converted Data
	*/
	function _xml_unconvert_safecdata( $v ) {
		// Legacy
		$v = str_replace( "<!¢|CDATA|", "<![CDATA[", $v );
		$v = str_replace( "|¢]>"      , "]]>"      , $v );
		// New
		$v = str_replace( "<!#^#|CDATA|", "<![CDATA[", $v );
		$v = str_replace( "|#^#]>"      , "]]>"      , $v );
		return $v;
	}
}

/**
* XML-LITE Extraction
*
* Methods and functions for handling XML documents
* Takes parsed XML and puts into EXPAT compatible array
*
*/
class xml_lite_parse {

	var $xml_class;
	var $parser;
	var $preserve_cdata = 1;
	var $stack    = array();
	var $level    = 1;
	var $tagname  = "";
	var $array_id = 0;
	var $last_id  = 0;
	var $tagopen  = array();
	var $xmldoc   = "";
	
	// CONSTRUCTOR
	function xml_lite_parse() {
	}
	
	// Main parser
	function xml_parse_it( $xmldoc ) {
		$parser = new xml_extract();
		
		$this->xmldoc = $xmldoc;
		
		unset( $xmldoc );
		
		// Set up element handlers
		$parser->my_xml_set_element_handler( array(&$this, 'my_start_element'), array(&$this, 'my_end_element') );
		$parser->my_xml_set_character_data_handler(array(&$this, 'my_data_element'));
		
		if ( $this->preserve_cdata ) {
			$parser->my_xml_set_cdata_section_handler( array(&$this, 'my_cdata_element') );
		}
		$parser->xml_parse_document( $this->xmldoc );
	}
	
	// Start element
	function my_start_element( &$parser_obj, $name, $attr ) {
		// Add to stack
		$this->stack[ $this->array_id ] = array(
			'tag'	=> $name,
			'type'	=> 'open',
			'level'	=> $this->level,
			'value'	=> '',
		);
		// Attributes?
		if ( is_array( $attr ) and count( $attr ) )
		{
			 $this->stack[ $this->array_id ]['attributes'] = $attr;
		}
		// Flying higher than an eagle?
		if ( $this->tagname != $name )
		{
			if ( $this->tagopen[ $name ] )
			{
				$this->level = $this->tagopen[ $name ];
			}
			else
			{
				$this->level++;
			}
		}
		// Set current tag name
		$this->tagname = $name;
		// Inc. array ID
		$this->array_id++;
		// Set tag == depth
		$this->tagopen[ $name ] = $this->level;
	}
	
	// Start element
	function my_end_element( &$parser_obj, $name ) {
		$this->stack[ $this->array_id ] = array(
			'tag'	=> $name,
			'type'	=> 'close',
			'level'	=> $this->tagopen[ $name ] - 1,
		);
		// Update already done data?
		if (($this->stack[ $this->array_id - 2 ]['tag'] == $this->tagname)
			&& ($this->stack[ $this->array_id - 1 ]['_data'] == 1)) {

			 // Update previous tag
			 $this->stack[ $this->array_id - 2 ]['value'] = profy_xml_parser::_xml_unconvert_safecdata( $this->stack[ $this->array_id - 1 ]['value'] );
			 $this->stack[ $this->array_id - 2 ]['type']  = 'complete';
			 
			 unset( $this->stack[ $this->array_id - 1 ] );
			 unset( $this->stack[ $this->array_id ] );
			 
			 $this->array_id -= 2;
			 $this->last_id   = $this->array_id - 1;
		}
		$this->tagname = "";

		$this->array_id++;
		$this->level--;
	}
	
	// DATA element
	function my_data_element( &$parser_obj, $data ) {
		if ( $this->tagname ) {
			$this->stack[ $this->array_id ] = array(
				'tag'        => $this->tagname,
				'type'       => 'open',
				'level'      => $this->level,
				'value'      => profy_xml_parser::_xml_unconvert_safecdata($data),
				'_data'      => 1,
			);
		}
		// Inc. array ID
		$this->array_id++;
	}
	
	// CDATA element
	function my_cdata_element( &$parser_obj, $data ) {
		$this->my_data_element( &$parser_obj, $data );
	}
	
	// Free memory
	function garbage_collect() {
		$this->stack    = array();
		$this->tagname  = array();
		$this->array_id = 0;
		$this->level    = 0;
		$this->xmldoc   = "";
	}
}

/**
* XML-LITE Extraction Sub class
*
* Methods and functions for handling XML documents
*
*/
class xml_extract {

	var $xml_array = array();
	var $chr_sofar = "";
	var $handler_cdata_handler;
	var $handler_character_data;
	var $handler_end_element;
	var $handler_start_element;
	
	// Definitions
	var $xml_constants = array(
		'CDATA_TAG' => '![CDATA[',
		'CDATA_LEN' => 8,
		'NOTATION'  => '!NOTATION',
		'DOCTYPE'   => '!DOCTYPE'
	);
							  
	// CONSTRUCTOR
	function xml_extract() {
	}
	
	// MAIN "PARSE" ROUTINE (Roughly mime EXPAT in PHP)
	function xml_parse_document( $xml )	{
		// Grab all relevant XML data
		// Strip off header, DOC TYPE, etc
		$xml = preg_replace( "#^(?:.+?)?(<.*>)(?:.+?)?$#s", "\\1", $xml );
		
		$xml_strlen = strlen( $xml );
		
		// Pick through, char by char
		for( $i = 0 ; $i < $xml_strlen; $i++ )
		{
			$chr = $xml{$i};
			
			switch( $chr )
			{
				case '<':
					if ( substr( $this->chr_sofar, 0, $this->xml_constants['CDATA_LEN'] ) == $this->xml_constants['CDATA_TAG'] )
					{
						// Processing CDATA
						$this->chr_sofar .= $chr;
					}
					else
					{
						$this->parse_between_tags( $this->chr_sofar );
						$this->chr_sofar = '';
					}
					break;
				case '>':
					if ( 
						( substr( $this->chr_sofar, 0, $this->xml_constants['CDATA_LEN'] ) == $this->xml_constants['CDATA_TAG'] )
						 AND
						 ! (
						 	 ( $this->_get_nth_char_from_end( $this->chr_sofar, 0 ) == ']' )
						 	 AND
						 	 ( $this->_get_nth_char_from_end( $this->chr_sofar, 1 ) == ']' )
						   )
					   )
					  	{
						 	$this->chr_sofar .= $chr;
						}
						else
						{
							if( $xml{ strlen($this->chr_sofar +1) } == ']' )
							{
								$this->chr_sofar .= $chr;
							}
							else
							{
								$this->parse_tag( $this->chr_sofar );
								$this->chr_sofar = '';
							}
						}
						break;
				default:
					$this->chr_sofar .= $chr;
			}
		}
		unset($xml);
	}
	
	// Parse tag
	function parse_tag( $text ) {
		$attr = array();
		$text = trim($text);
		$fchr = $text{0};
		
		switch ($fchr) {
			// First char is closing tag?
			case '/':
				$tag_name = substr($text, 1);
				$this->exec_end_element($tag_name);
				break;
			// First char is instruction/doctype?
			case '!':
				$uc_tag_text = strtoupper($text);
				if ( strpos($uc_tag_text, $this->xml_constants['CDATA_TAG']) !== false ) {
					// CDATA text
					$total          = strlen($text);
					$openbrace_cnt  = 0;
					$tn_text        = '';

					for ( $i = 0; $i < $total; $i++ )
					{
						$cc = $text{$i};
						// End of CDATA?
						if ( ($cc == ']') && ( $text{($i + 1)} == ']' ) )
						{
							if( ! $text{($i + 2)} == ']' )
							{
								break;
							}
							else
							{
								$tn_text .= $cc;
							}
						}
						else if ($openbrace_cnt > 1)
						{
							$tn_text .= $cc;
						}
						else if ($cc == '[')
						{
							// Won't get here until first OB reached
							$openbrace_cnt ++;
						}
					}

					if ( $this->handler_cdata_handler == null )
					{
						$this->exec_character_data($tn_text);
					}
					else
					{
						$this->exec_cdata_element($tn_text);
					}
				}
				else if ( strpos( $uc_tag_text, $this->xml_constants['NOTATION'] ) !== false )
				{
					// !NOTATION? Ignore!
					return;
				}
				else if ( substr($text, 0, 2) == '!-' )
				{
					// !Comment? Ignore!
					return;
				}

				break;
			// Case is ?INSTRUCTION?
			case '?':
				// Instruction? Ignore!
				return;
			// Normal tag - woohoo!
			default:
			
				if ( (strpos($text, '"') !== false) || (strpos($text, "'") !== false) )
				{
					$total    = strlen($text);
					$tag_name = '';

					for ($i = 0; $i < $total; $i++)
					{
						$cc = $text{$i};

						if ($cc == ' ')
						{
							$attr = $this->parse_attr(substr($text, $i));
							break;
						}
						else
						{
							$tag_name.= $cc;
						}
					}

					if ( strrpos($text, '/') == ( strlen($text) - 1 ) )
					{
						$this->exec_start_element($tag_name, $attr);
						$this->exec_end_element($tag_name);
					}
					else
					{
						$this->exec_start_element($tag_name, $attr);
					}
				}
				else {
					if ( strpos($text, '/') !== false )
					{
						$text = trim( substr( $text, 0, ( strrchr($text, '/' ) - 1 ) ) );
						$this->exec_start_element($text, $attr);
						$this->exec_end_element($text);
					}
					else
					{
						$this->exec_start_element($text, $attr);
					}
				}
		}
	}
	
	// Parse Attributes
	function parse_attr( $text ) {
		$text         = trim($text);	
		$attr_array   = array();
		$query_entity = false;			
		
		$total        = strlen($text);
		$dump_key     = '';
		$dump_value   = '';
		$cur_state    = 0;  // 0 = none, 1 = key, 2 = value
		$quote_type   = '';
		
		for ($i = 0; $i < $total; $i++) {								
			$cc = $text{$i};
			
			if ( $cur_state == 0 ) {
				if ( trim($cc != '') ) {
					$cur_state = 1;
				}
			}
			switch ($cc) {
				// Tab and we're in a value?
				case "\t":
					if ( $cur_state == 2 ) {
						$dump_value .= $cc;
					} else {
						$cc = '';
					}
					break;
				// Newlines..
				case "\n":
				case "\r":
					$cc = '';
					break;
				// Param value
				case '=':
					if ( $cur_state == 2 ) {
						$dump_value .= $cc;
					} else {
						$cur_state    = 2;
						$quote_type   = '';
						$query_entity = false;
					}
					break;
				// Quoted
				case '"':
					if ($cur_state == 2) {
						if ($quote_type == '') {
							$quote_type = '"';
						} else {
							if ($quote_type == $cc) {
								$attr_array[ trim($dump_key) ] = trim($dump_value);
								$dump_key  = $dump_value = $quote_type = '';
								$cur_state = 0;
							} else {
								$dump_value .= $cc;
							}
						}
					}
					break;
				// Quoted
				case "'":
					if ($cur_state == 2) {
						if ($quote_type == '') {
							$quote_type = "'";
						} else {
							if ($quote_type == $cc) {
								$attr_array[ trim($dump_key) ] = trim($dump_value);
								$dump_key  = $dump_value = $quote_type = '';
								$cur_state = 0;
							} else {
								$dump_value .= $cc;
							}
						}
					}
					break;
				// Entity?
				case '&':
					$query_entity = true;
					$dump_value  .= $cc;
					break;
					
				default:
					if ($cur_state == 1) {
						$dump_key .= $cc;
					} else {
						$dump_value .= $cc;
					}
			}
		}
		return $attr_array;
	}	
	
	// Parse between the element tags
	function parse_between_tags( $t ) {
		if ( trim($t ) != '') {
			$this->exec_character_data($t);
		}
	}
	
	// Exec_events
	function exec_character_data( $data ) {
		call_user_func( $this->handler_character_data, $this, $data );
	}
	
	function exec_start_element( $tagname, $attr ) {
		call_user_func( $this->handler_start_element, $this, $tagname, $attr );
	}
	
	function exec_end_element( $tagname ) {
		call_user_func( $this->handler_end_element, $this, $tagname );
	}
	
	function exec_cdata_element( $data ) {
		call_user_func( $this->handler_cdata_handler, $this, $data );
	}
	
	// Set xml element handlers
	function my_xml_set_element_handler($startHandler, $endHandler) {
		$this->handler_start_element = $startHandler;
		$this->handler_end_element   = $endHandler;
	}
	
	function my_xml_set_character_data_handler($handler) {
		$this->handler_character_data = &$handler;
	}
	
	function my_xml_set_cdata_section_handler($handler) {
		$this->handler_cdata_handler = &$handler;
	}
	
	// Internal: Get nth character of $text
	function _get_nth_char_from_end( $t, $i ) {
		return $t{ ( strlen( $t ) - 1 - $i ) };
	}
}
