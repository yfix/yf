<?php

/**
* Test sub-class
*/
class yf_test_diff {

	/**
	*/
	function test () {
		$DIFF_OBJ = _class("diff");
//		$DIFF->method = "PHP";
		$str1 = 
"class Text_Diff_Op_add extends Text_Diff_Op {

	function Text_Diff_Op_add(\$lines)
	{
		\$this->final = \$lines;
		\$this->orig = false;
	}

	function &reverse()
	{
		\$reverse = &new Text_Diff_Op_delete(\$this->final);
		return \$reverse;
	}

}";
		$str2 = 
"class Text_Diff_Op_add extends Text_Diff_Op {
	function Text_Diff_Op_add(\$lines) {
		\$this->final = \$lines;
		\$this->orig = false;
	}
	function &reverse() {
		\$reverse = &new Text_Diff_Op_delete(\$this->final);
		return \$reverse;
	}
	function &reverse2() {
		\$reverse = &new Text_Diff_Op_delete(\$this->final);
		return \$reverse;
	}
}";

		$body = $DIFF_OBJ->get_differences($str1, $str2);
		return "<pre style='font-weight:bold;'>".$body."</pre>";
	}
}
