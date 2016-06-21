<?php

/**
* Translit from RU into EN
*/
class yf_translit {

	public $pairs = [
		'а' => 'a',	'б' => 'b',	'в' => 'v',	'г' => 'g',	
		'д' => 'd',	'е' => 'e',	'ё' => 'e',	'з' => 'z',	
		'и' => 'i',	'й' => 'y',	'к' => 'k',	'л' => 'l',
		'м' => 'm',	'н' => 'n',	'о' => 'o',	'п' => 'p',
		'р' => 'r',	'с' => 's',	'т' => 't',	'у' => 'u',
		'ф' => 'f',	'х' => 'h',	'ъ' => 'j',	'ы' => 'i',
		'э' => 'e',	'і' => 'i',

		'А' => 'A',	'Б' => 'B',	'В' => 'V',	'Г' => 'G',
		'Д' => 'D',	'Е' => 'E',	'Ё' => 'E',	'З' => 'Z',
		'И' => 'I',	'Й' => 'Y',	'К' => 'K',	'Л' => 'L',
		'М' => 'M',	'Н' => 'N',	'О' => 'O',	'П' => 'P',
		'Р' => 'R',	'С' => 'S',	'Т' => 'T',	'У' => 'U',
		'Ф' => 'F',	'Х' => 'H',	'Ъ' => 'J',	'Ы' => 'I',
		'Э' => 'E',	'І' => 'I',

		'ж' => 'zh', 'ц' => 'ts', 'ч' => 'ch', 'ш' => 'sh', 
		'щ' => 'shch', 'ь' => '', 'ю' => 'yu', 'я' => 'ya',

		'Ж' => 'ZH', 'Ц' => 'TS', 'Ч' => 'CH', 'Ш' => 'SH', 
		'Щ' => 'SHCH', 'Ь' => '', 'Ю' => 'YU', 'Я' => 'YA',

		'ї' => 'i', 'Ї' => 'Yi', 'є' => 'ie', 'Є' => 'Ye',
	];

	/**
	* Make translit from russian or ukrainian text
	*/
	function make ($str) {
		if (empty($str) || !preg_match('/[а-яіїє]+/iu', $str)) {
			return $str;
		}
		if ($this->_is_utf8($str)) {
			$str = iconv('UTF-8', 'UTF-8//IGNORE', $str);
		}
		return strtr($str, $this->pairs);
	}

	/**
	* Check if given string is in utf8
	*/
	function _is_utf8($string) {
		return (utf8_encode(utf8_decode($string)) != $string);
	}

	/**
	*/
	function rus2uni($str, $isTo = true) {
		$arr = [
			'ё' => '&#x451;',
			'Ё' => '&#x401;'
		];
		for ($i=192;$i<256;$i++) {
			$arr[chr($i)] = '&#x4'.dechex($i-176).';';
		}
		$str = preg_replace([
			'@([а-я]) @i',
			'@ ([а-я])@i'
		], [
			'$1&#x0a0;',
			'&#x0a0;$1'
		], $str);
		return strtr($str, $isTo ? $arr : array_flip($arr));
	}

	/**
	* 
	*/
	function utf2win1251 ($s) {
		$out = '';

		for ($i=0; $i<strlen($s); $i++) {
			$c1 = substr ($s, $i, 1);
			$byte1 = ord ($c1);
			if ($byte1>>5 == 6) {// 110x xxxx, 110 prefix for 2 bytes unicode
				$i++;
				$c2 = substr ($s, $i, 1);
				$byte2 = ord ($c2);
				$byte1 &= 31; // remove the 3 bit two bytes prefix
				$byte2 &= 63; // remove the 2 bit trailing byte prefix
				$byte2 |= (($byte1 & 3) << 6); // last 2 bits of c1 become first 2 of c2
				$byte1 >>= 2; // c1 shifts 2 to the right

				$word = ($byte1<<8) + $byte2;
				if ($word==1025) $out .= chr(168);					// ?
				elseif ($word==1105) $out .= chr(184);				// ?
				elseif ($word>=0x0410 && $word<=0x044F) $out .= chr($word-848); // ?-? ?-?
				else {  
					$a = dechex($byte1);
					$a = str_pad($a, 2, '0', STR_PAD_LEFT);
					$b = dechex($byte2);
					$b = str_pad($b, 2, '0', STR_PAD_LEFT);
					$out .= '&#x'.$a.$b.';';
				}
			} else {
				$out .= $c1;
			}
		}
		return $out;
	}
}
