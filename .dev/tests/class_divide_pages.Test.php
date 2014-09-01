<?php

require_once __DIR__.'/yf_unit_tests_setup.php';

class class_divide_pages_test extends PHPUnit_Framework_TestCase {
	public function _get_expected_html($href, $num_pages = 10, $next = 2) {
		$html = 
			'<div class="pagination"> <ul> </ul> <ul>
					<li class="disabled"><a>1</a></li>';
		foreach (range($next, $num_pages) as $page) {
			$html .= '<li><a href="'. $href. $page. '" title="Page '.$page.'">'.$page.'</a></li>';
		}
		$html .= ' <li class="next"><a href="'. $href. $next. '" title="Next page">&raquo;</a></li>
				</ul> <ul> </ul>
			</div>';
		return $this->_cleanup_html($html);
	}
	public function _cleanup_html($html) {
		return preg_replace('/[\t\s\n]+/ims', ' ', $html);
	}
	public function test_complex() {
		$per_page = 10;
		conf('per_page', $per_page);
		$this->assertEquals( $per_page, conf('per_page') );
		$this->assertEquals( 0, conf('user_per_page') );
		$this->assertEquals( 0, conf('admin_per_page') );

		$_GET = array();
		$_GET['object'] = __CLASS__;
		$_GET['action'] = __FUNCTION__;
		$_GET['id'] = 12345678;
		$href = './?object='.$_GET['object'].'&action='.$_GET['action'].'&id='.$_GET['id'];

		$num_pages = 5;
		$data = range(1, $per_page * $num_pages);

		$sql = 'SELECT * FROM user';
		$expect_for_sql = array_values(array(
			'limit_sql'		=> ' LIMIT 0, '.$per_page,
			'pages_html'	=> $this->_get_expected_html($href.'&page=', $num_pages),
			'total_records'	=> count($data),
			'first_record'	=> 0,
			'total_pages'	=> (int)ceil(count($data) / $per_page),
			'limited_pages' => 0,
			'per_page'		=> $per_page,
			'requested_page'=> 0,
		));
		$result = common()->divide_pages($sql, $href, '', '', $num_records = count($data));
		$result[1] = $this->_cleanup_html($result[1]);
		$this->assertEquals( $expect_for_sql, $result );

		$expect_for_array = array_values(array(
			'items'			=> array_slice($data, 0, $per_page, true),
			'pages_html'	=> $this->_get_expected_html($href.'&page=', $num_pages),
			'total_records'	=> count($data),
			'first_record'	=> 0,
			'total_pages'	=> (int)ceil(count($data) / $per_page),
			'limited_pages' => 0,
			'per_page'		=> $per_page,
			'requested_page'=> 0,
		));

		$result = common()->divide_pages($data, $href);
		$result[1] = $this->_cleanup_html($result[1]);
		$this->assertEquals( $expect_for_array, $result );

		$result = common()->divide_pages($data, $href, 'slide');
		$result[1] = $this->_cleanup_html($result[1]);
		$this->assertEquals( $expect_for_array, $result );

		conf('per_page', 100500);
		$result = common()->divide_pages($data, $href);
		$result[1] = $this->_cleanup_html($result[1]);
		$this->assertNotEquals( $expect_for_array, $result );

		$result = common()->divide_pages($data, $href, '', $per_page);
		$result[1] = $this->_cleanup_html($result[1]);
		$this->assertEquals( $expect_for_array, $result );
		conf('per_page', $per_page);

		$data = range(1, $per_page * $num_pages - 1);
		$result = common()->divide_pages($data, $href, '', '', $per_page * $num_pages);
		$result[1] = $this->_cleanup_html($result[1]);
		$this->assertEquals( $expect_for_array, $result );
		$data = range(1, $per_page * $num_pages);
	}
}