<?php

require dirname(__FILE__).'/yf_unit_tests_setup.php';

class class_validate_test extends PHPUnit_Framework_TestCase {
	public function test_required_1() {
		$this->assertEquals(true, _class('validate')->required('str') );
/*
	password_update(&$in) {
	md5_not_empty(&$in) {
	required($in) {
	required_any($in, $params = array(), $fields = array()) {
	matches($in, $params = array(), $fields = array()) {
	is_unique($in, $params = array()) {
	is_unique_without($in, $params = array()) {
	exists($in, $params = array()) {
	regex_match($in, $params = array()) {
	differs($in, $params = array(), $fields = array()) {
	valid_url($in, $params = array()) {
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

}