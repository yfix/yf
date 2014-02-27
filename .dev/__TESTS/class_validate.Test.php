<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_validate_test extends PHPUnit_Framework_TestCase {
	public function test_password_update() {
		$var = ''; _class('validate')->password_update($var);
		$this->assertEquals( null,  $var);
		$var = 'test'; _class('validate')->password_update($var);
		$this->assertEquals( md5('test'),  $var);
	}
	public function test_md5_not_empty() {
		$var = ''; _class('validate')->md5_not_empty($var);
		$this->assertEquals( '',  $var);
		$var = 'test'; _class('validate')->md5_not_empty($var);
		$this->assertEquals( md5('test'),  $var);
	}
	public function test_valid_url() {
		$this->assertFalse( _class('validate')->valid_url('') );
		$this->assertFalse( _class('validate')->valid_url(null) );
		$this->assertFalse( _class('validate')->valid_url(false) );
		$this->assertFalse( _class('validate')->valid_url(array()) );
		$this->assertFalse( _class('validate')->valid_url(' ') );
		$this->assertFalse( _class('validate')->valid_url(PHP_EOL) );

#		$this->assertFalse( _class('validate')->valid_url(new StdClass()) );
#		$this->assertFalse( _class('validate')->valid_url('fsfsfs') );

#		$this->assertFalse( _class('validate')->valid_url('#') );
#		$this->assertFalse( _class('validate')->valid_url('#id') );

		$this->assertTrue( _class('validate')->valid_url('index') );
		$this->assertTrue( _class('validate')->valid_url('index.html') );
		$this->assertTrue( _class('validate')->valid_url('script.js') );
#		$this->assertTrue( _class('validate')->valid_url('/script.js') );
#		$this->assertTrue( _class('validate')->valid_url('./script.js') );
#		$this->assertTrue( _class('validate')->valid_url('../script.js') );
#		$this->assertTrue( _class('validate')->valid_url('//script.js') );
		$this->assertTrue( _class('validate')->valid_url('http://domain.com/script.js') );
		$this->assertTrue( _class('validate')->valid_url('https://domain.com/script.js') );
		$this->assertTrue( _class('validate')->valid_url('http://domain.com/script.js?key1=val1&key2=val2#fragment') );
		$this->assertTrue( _class('validate')->valid_url('http://domain.com:8080/some_path/script.js?key1=val1&key2=val2#fragment') );
		$this->assertTrue( _class('validate')->valid_url('http://user:pswd@domain.com:8080/some_path/script.js?key1=val1&key2=val2#fragment') );
#		$this->assertTrue( _class('validate')->valid_url('ftp://user:pswd@domain.com:8080/some_path/script.js') );
	}
	public function test_required() {
		$this->assertFalse( _class('validate')->required('') );
		$this->assertFalse( _class('validate')->required(' ') );
		$this->assertFalse( _class('validate')->required(false) );
		$this->assertFalse( _class('validate')->required(null) );
		$this->assertFalse( _class('validate')->required(array()) );

		$this->assertTrue( _class('validate')->required('str') );
		$this->assertTrue( _class('validate')->required(array('str')) );
		$this->assertTrue( _class('validate')->required(array(1,2)) );
		$this->assertTrue( _class('validate')->required(array(' ')) );
	}
	public function test_required_any() {
		$this->assertFalse( _class('validate')->required_any() );
		$this->assertFalse( _class('validate')->required_any(null, array()) );
		$this->assertFalse( _class('validate')->required_any('', array('param' => 'd_day,d_week')) );
		$this->assertFalse( _class('validate')->required_any('', array('param' => 'd_day,d_week'), array('d_day' => '', 'd_week' => '')) );
		$this->assertFalse( _class('validate')->required_any('', array('param' => 'd_*'), array('d_day' => '', 'd_week' => '')) );

		$this->assertTrue( _class('validate')->required_any('', array('param' => 'd_day,d_week'), array('d_day' => 1, 'd_week' => '')) );
		$this->assertTrue( _class('validate')->required_any('', array('param' => 'd_*'), array('d_day' => 1, 'd_week' => '')) );
	}
	public function test_matches() {
		$this->assertFalse( _class('validate')->matches() );
		$this->assertFalse( _class('validate')->matches('', array('param' => 'my_field')) );
		$_POST['my_field'] = '55';
		$this->assertFalse( _class('validate')->matches('', array('param' => 'my_field'), array('my_field' => '55')) );
		$this->assertTrue( _class('validate')->matches('55', array('param' => 'my_field'), array('my_field' => '55')) );
	}
/*
	matches($in, $params = array(), $fields = array()) {
	is_unique($in, $params = array()) {
	is_unique_without($in, $params = array()) {
	exists($in, $params = array()) {
	regex_match($in, $params = array()) {
	differs($in, $params = array(), $fields = array()) {
	min_length($in, $params = array()) {
	max_length($in, $params = array()) {
	exact_length($in, $params = array()) {
	greater_than($in, $params = array()) {
	less_than($in, $params = array()) {
	greater_than_equal_to($in, $params = array()) {
	less_than_equal_to($in, $params = array()) {
	alpha($in) {
	alpha_numeric($in) {
	alpha_numeric_spaces($in) {
	alpha_dash($in) {
	numeric($in) {
	integer($in) {
	decimal($in) {
	is_natural($in) {
	is_natural_no_zero($in) {
	valid_email($in) {
	valid_emails($in) {
	valid_base64($in) {
	prep_url($in) {
	encode_php_tags($in) {
	valid_ip($in, $params = array()) {
	captcha($in, $params = array(), $fields = array()) {
	xss_clean($in) {
	strip_image_tags($in) {
	_valid_ip($ip, $ip_version = 'ipv4') {
	_check_user_nick ($CUR_VALUE = '', $force_value_to_check = null, $name_in_form = 'nick') {
	_check_profile_url ($CUR_VALUE = "", $force_value_to_check = null, $name_in_form = "profile_url") {
	_check_login () {
	_check_location ($cur_country = '', $cur_region = '', $cur_city = '') {
	_check_birth_date ($CUR_VALUE = '') {
*/

}