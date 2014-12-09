<?php
 
class colors {
	private $fg_colors = array(
		'black' => '0;30',
		'dark_gray' => '1;30',
		'blue' => '0;34',
		'light_blue' => '1;34',
		'green' => '0;32',
		'light_green' => '1;32',
		'cyan' => '0;36',
		'light_cyan' => '1;36',
		'red' => '0;31',
		'light_red' => '1;31',
		'purple' => '0;35',
		'light_purple' => '1;35',
		'brown' => '0;33',
		'yellow' => '1;33',
		'light_gray' => '0;37',
		'white' => '1;37',
	);
	private $bg_colors = array(
		'black' => '40',
		'red' => '41',
		'green' => '42',
		'yellow' => '43',
		'blue' => '44',
		'magenta' => '45',
		'cyan' => '46',
		'light_gray' => '47',
		'white' => '107',
	);
	public function apply($string, $fg_color = null, $bg_color = null) {
		$colored_string = '';
		if (isset($this->fg_colors[$fg_color])) {
			$colored_string .= "\033[" . $this->fg_colors[$fg_color] . 'm';
		}
		if (isset($this->bg_colors[$bg_color])) {
			$colored_string .= "\033[" . $this->bg_colors[$bg_color] . 'm';
		}
		$colored_string .=  $string . "\033[0m";
		return $colored_string;
	}
	public function get_fg_colors() {
		return array_keys($this->fg_colors);
	}
	public function get_bg_colors() {
		return array_keys($this->bg_colors);
	}
}
 
$colors = new colors();
echo $colors->apply('SUCCESS', 'white', 'green') . PHP_EOL;
echo $colors->apply('WARNING', 'white', 'yellow') . PHP_EOL;
echo $colors->apply('INFO', 'white', 'blue') . PHP_EOL;
echo $colors->apply('ERROR', 'white', 'red') . PHP_EOL;

foreach ($colors->get_fg_colors() as $fg) {
	foreach ($colors->get_bg_colors() as $bg) {
		echo $colors->apply($fg.','.$bg, $fg, $bg).' ';
	}
	echo PHP_EOL;
}
