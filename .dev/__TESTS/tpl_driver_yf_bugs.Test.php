<?php

require_once dirname(__FILE__).'/tpl__setup.php';

class tpl_driver_yf_bugs_test extends tpl_abstract {
	public function test_bug_01() {
		$this->assertEquals('#description ', self::_tpl( '#description {execute(main,_show_block123123)}', array('description' => 'test') ));
	}
	public function test_bug_02() {
		$this->assertEquals(' {} ', self::_tpl( ' {} ', array('' => '') ));
	}
	public function test_bug_03() {
		$a = array('quantity' => 10, 'active' => 1);
		$this->assertEquals(' ok ', self::_tpl( '{if("quantity" gt 0)} ok {/if}', $a ));
		$this->assertEquals(' ok ', self::_tpl( '{if("active" ne 0)} ok {/if}', $a ));
		$this->assertEquals(' ok ', self::_tpl( '{if("quantity" gt "0" and "active" ne "0")} ok {/if}', $a ));
		$this->assertEquals(' ok ', self::_tpl( '{if(quantity gt 0 and active ne 0)} ok {/if}', $a ));
		$this->assertEquals(' ok ', self::_tpl( '{if("quantity" gt 0 and active ne 0)} ok {/if}', $a ));
#		$this->assertEquals(' ok ', self::_tpl( '{if("quantity" gt 0 and "active" ne 0)} ok {/if}', $a ));

#		$a = array('quantity' => 10, 'active' => 0);
#		$this->assertEquals('', self::_tpl( '{if("quantity" gt 0 and "active" ne 0)} ok {/if}', $a ));
#		$a = array('quantity' => 0, 'active' => 0);
#		$this->assertEquals('', self::_tpl( '{if("quantity" gt 0 and "active" ne 0)} ok {/if}', $a ));
	}
}