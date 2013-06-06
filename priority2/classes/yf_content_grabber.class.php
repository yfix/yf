<?php

class yf_content_grabber {

	function _run_grabber(){
		$Q = db()->query("SELECT * FROM `".db('rss_items')."` WHERE `status`=0");
		while ($A = db()->fetch_assoc($Q)) {
			db()->UPDATE("rss_items", array(
				"status"	=> 1,
			), "`id`=".$A["id"]);	

			$this->_grab($A["link"]);
		}
	}

	function _grab($url, $test=""){
	
		if(empty($url)){
			echo "no url";
			return;
		}
		
		$content_original = file_get_contents($url);
		
		//$preg_match=preg_match("/Content-Type:\s+\S+;\s*charset=(\S+)/i", $content_original,$charset_header);
		$preg_match=preg_match("/<meta\s+http-equiv\s*=\s*[\"|']?Content-Type[\"|']?\s+content\s*=\s*[\"|']\S+;\s*charset\s*=\s*(\S+)[\"|']\s*\/?>/i", $content_original,$charset_meta);
		if (($charset_meta[1] != "UTF-8") and ($charset_meta[1] != "")){
			$content_original=iconv($charset_meta[1],"UTF-8",$content_original);
		}
				
		$Q = db()->query("SELECT * FROM `".db('grab_patterns')."` ORDER BY `name` DESC");
		while ($A = db()->fetch_assoc($Q)) {
			$pattern_info[] = $A;
		}

		foreach ((array)$pattern_info as $key => $value){	
			preg_match($value["pattern"],$content_original, $matches); 
			$content = $matches[1];
						
			// filter
			if(!empty($content)){			
			
				$pattern_id = $value["id"];
				$pattern_name = $value["name"];
			
				if(!empty($value["replace_pattern"])){
				
					$value["replace_pattern"] = str_replace("\r", "", $value["replace_pattern"]);
					
					$replace_pattern = explode("\n", $value["replace_pattern"]);
				
					foreach ((array)$replace_pattern as $replace_key => $replace_pattern_value){
						if(!empty($value)){
							$content = preg_replace($replace_pattern_value, "", $content, 1);				
						}
					}
				}
				break;
			}
		}
		
		if($test=="test"){
			$pattern_name = "<b>Pattern name:</b> ".$pattern_name."<BR>";
			if(empty($content)) $content = "<font color='red'>Content not found</font>";
			return $pattern_name.$content;
		}
		
		db()->INSERT("grab_content", array(
			"url"					=> _es($url),
			"content"				=> _es($content),
			"pattern_id"			=> intval($pattern_id),			
		));
	}
}
