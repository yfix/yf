<?php

class yf_synonymizer {

	public $limit = 100;

	function process($str = "", $vars = array()){		
		return $this->myPreg($this->_replace_vars($str, $vars));
	}

	function _replace_vars($str = "", $vars = array()){
		if (empty($vars)) {
			return $str;
		}
		foreach ((array)$vars as $key => $item){
			$str = str_replace("%".strtoupper($key)."%", $item, $str);
		}
		return $str;
	}

	function myPreg($str, $counter = 0){
		$_p_sharp = "#([0-9\,\&]+?)#";
	
		$str =  preg_replace_callback("/\[([^\[\]\{\}]+?)\](".$_p_sharp.")/si", array(&$this, 'myCallbackSquare'), $str);	

		$str =  preg_replace_callback("/\[([^\[\]\{\}]+?)\](?!".$_p_sharp.")/si", array(&$this, 'myCallbackSquareNormal'), $str);
		
		$str =  preg_replace_callback("/{([^\{\}\[\]]+?)}/si", array(&$this, 'myCallbackFigure'), $str);
		
		if ($counter > $this->limit) {
			return $str;
		}
		
		if (preg_match("/\{(.+?)\}/si", $str)){
			return $this->myPreg($str, $counter + 1);
		} elseif (preg_match("/\[([^\[\]]+?)\]/si", $str)) {
			return $this->myPreg($str, $counter + 1);
		} else {
			return $str;
		}
		
	}

	function myCallbackFigure($matches){
		$da = strrchr($matches[0], "{");
		$data = explode("{", $da);
		$parse = '{'.$data[count($data)-1];
		unset($data);
		preg_match("/\{(.*?)\}/si", $parse, $m);
		$d = explode("|", $m[1]);
		$str = $d[rand(0, count($d)-1)];
		return str_replace("{", '', str_replace("}", '', $str));
	}
	

	function myCallbackSquare($matches){
		
		$parts = explode("|", $matches[1]);	
		

		if (is_numeric($matches[3])){
			$max = (int) $matches[3];
		} else{
		
		
				$match1 =  preg_match("/([0-9]+[,]+)/si", $matches[3], $m);
				$match2 =  preg_match("/([0-9]+[\&]+)/si", $matches[3], $m);
		
			if (($match1) or ($match2)){   // if #1,4#				
				$partsX = explode(",", $matches[3]);
				
				$match2?$partsX = explode("&", $matches[3]):$partsX = explode(",", $matches[3]);

				$match2?$last_divide = " and ":$last_divide = ", ";
				
	
				
				$a = (int) @$partsX[0];
				$b = (int) @$partsX[1];		
				
				$max = mt_rand($a, $b);
				
				// if #3,#
				if($b == 0){
					$max = $a;
				}			
				
			} else {		
				$max = count($parts);		
			}
		}
		
		$min = 0;
	
		shuffle($parts);
		
		for($i = $min; $i < $max; $i++){			
			
			$i == $min?$divide = "":$divide = ", "; 			
			if(($i == ($max - 1)) and ($i != $min))	$divide = $last_divide;
			
			$value .= $divide.$parts[$i];
		}
		
	return $value;	
	}

	
	function myCallbackSquareNormal($matches){
		$parts = explode("|", $matches[1]);	
		shuffle($parts);		
		return str_replace("[", "", str_replace("]", "", implode(" " , $parts)));
	}
}
