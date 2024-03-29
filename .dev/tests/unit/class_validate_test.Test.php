<?php

require_once __DIR__ . '/yf_unit_tests_setup.php';

class class_validate_test extends yf\tests\wrapper
{
    public static function tearDownAfterClass() : void
    {
        common()->USER_ERRORS = [];
    }
    public function test_input_is_valid()
    {
        $this->assertTrue(_class('validate')->_input_is_valid([], ['key' => 'trim']));
        $this->assertTrue(_class('validate')->_input_is_valid(['key' => 'val'], ['key' => 'required']));
        $this->assertTrue(_class('validate')->_input_is_valid(['key' => 'val'], ['key' => 'trim|required']));
        $this->assertTrue(_class('validate')->_input_is_valid(['key' => ['val1', 'val2']], ['key' => 'required']));
        $this->assertTrue(_class('validate')->_input_is_valid(['key' => ['val1', 'val2']], ['key' => 'required', 'other_key' => 'trim']));
        $this->assertTrue(_class('validate')->_input_is_valid(['key' => ['val1', 'val2']], ['key' => 'trim|required']));
        $this->assertTrue(_class('validate')->_input_is_valid(['key' => ['val1', 'val2'], 'key2' => 'v2'], ['key' => 'trim|required', 'key2' => 'required']));

        $this->assertFalse(_class('validate')->_input_is_valid([], ['key' => 'required']));
        $this->assertFalse(_class('validate')->_input_is_valid([], ['key' => 'trim|required']));
        $this->assertFalse(_class('validate')->_input_is_valid(['key' => ''], ['key' => 'trim|required']));
        $this->assertFalse(_class('validate')->_input_is_valid(['key' => ' '], ['key' => 'trim|required']));
        $this->assertFalse(_class('validate')->_input_is_valid(['key' => []], ['key' => 'trim|required']));
        $this->assertFalse(_class('validate')->_input_is_valid(['key' => []], ['key' => 'trim|required']));
        $this->assertFalse(_class('validate')->_input_is_valid(['key' => ['val1', 'val2'], 'key2' => ''], ['key' => 'trim|required', 'key2' => 'required']));
        $this->assertFalse(_class('validate')->_input_is_valid(['key' => ['val1', 'val2'], 'key2' => ' '], ['key' => 'trim|required', 'key2' => 'required']));
    }
    public function test_func_validate()
    {
        $this->assertTrue(validate([], ['key' => 'trim']));
        $this->assertTrue(validate(['key' => 'val'], ['key' => 'required']));
        $this->assertTrue(validate(['key' => 'val'], ['key' => 'trim|required']));
        $this->assertTrue(validate(['key' => ['val1', 'val2']], ['key' => 'required']));
        $this->assertTrue(validate(['key' => ['val1', 'val2']], ['key' => 'required', 'other_key' => 'trim']));
        $this->assertTrue(validate(['key' => ['val1', 'val2']], ['key' => 'trim|required']));
        $this->assertTrue(validate(['key' => ['val1', 'val2'], 'key2' => 'v2'], ['key' => 'trim|required', 'key2' => 'required']));

        $this->assertFalse(validate([], ['key' => 'required']));
        $this->assertFalse(validate([], ['key' => 'trim|required']));
        $this->assertFalse(validate(['key' => ''], ['key' => 'trim|required']));
        $this->assertFalse(validate(['key' => ' '], ['key' => 'trim|required']));
        $this->assertFalse(validate(['key' => []], ['key' => 'trim|required']));
        $this->assertFalse(validate(['key' => []], ['key' => 'trim|required']));
        $this->assertFalse(validate(['key' => ['val1', 'val2'], 'key2' => ''], ['key' => 'trim|required', 'key2' => 'required']));
        $this->assertFalse(validate(['key' => ['val1', 'val2'], 'key2' => ' '], ['key' => 'trim|required', 'key2' => 'required']));

        $this->assertTrue(validate(' test ', 'trim|required'));
        $this->assertFalse(validate('  ', 'trim|required'));
        $this->assertTrue(validate(['key' => ['val1', 'val2'], 'key2' => 'v2'], 'trim|required'));
        $this->assertFalse(validate(['key' => ['val1', 'val2'], 'key2' => ' '], 'trim|required'));
    }
    public function test_password_update()
    {
        $var = '';
        _class('validate')->password_update($var);
        $this->assertNull($var);
        $var = 'test';
        _class('validate')->password_update($var);
        $this->assertEquals(md5('test'), $var);

        $this->assertTrue(validate($var, 'password_update'));
    }
    public function test_md5_not_empty()
    {
        $var = '';
        _class('validate')->md5_not_empty($var);
        $this->assertEquals('', $var);
        $var = 'test';
        _class('validate')->md5_not_empty($var);
        $this->assertEquals(md5('test'), $var);

        $this->assertTrue(validate($var, 'md5_not_empty'));
    }
    public function test_required()
    {
        $this->assertFalse(_class('validate')->required(''));
        $this->assertFalse(_class('validate')->required(' '));
        $this->assertFalse(_class('validate')->required(false));
        $this->assertFalse(_class('validate')->required(null));
        $this->assertFalse(_class('validate')->required([]));
        $this->assertFalse(_class('validate')->required([' ']));
        $this->assertFalse(_class('validate')->required(['', '', '']));
        $this->assertFalse(_class('validate')->required([[], []]));
        $this->assertFalse(_class('validate')->required([[[]], []]));
        $this->assertFalse(_class('validate')->required([[['', '', '']], []]));

        $this->assertTrue(_class('validate')->required('str'));
        $this->assertTrue(_class('validate')->required(['str']));
        $this->assertTrue(_class('validate')->required([1, 2]));

        $this->assertFalse(validate('', 'required'));
        $this->assertTrue(validate('str', 'required'));
        $this->assertTrue(validate(['str'], 'required'));
        $this->assertTrue(validate([1, 2], 'required'));
    }
    public function test_required_any()
    {
        $this->assertFalse(_class('validate')->required_any(null, []));
        $this->assertFalse(_class('validate')->required_any('', ['param' => 'd_day,d_week']));
        $this->assertFalse(_class('validate')->required_any('', ['param' => 'd_day,d_week'], ['d_day' => '', 'd_week' => '']));
        $this->assertFalse(_class('validate')->required_any('', ['param' => 'd_*'], ['d_day' => '', 'd_week' => '']));
        $this->assertFalse(_class('validate')->required_any('', ['param' => 'd_?????'], ['d_day' => 1, 'd_week' => '']));

        $this->assertTrue(_class('validate')->required_any('', ['param' => 'd_day,d_week'], ['d_day' => 1, 'd_week' => '']));
        $this->assertTrue(_class('validate')->required_any('', ['param' => 'd_*'], ['d_day' => 1, 'd_week' => '']));
        $this->assertTrue(_class('validate')->required_any('', ['param' => 'd_???*'], ['d_day' => 1, 'd_week' => '']));
    }
    public function test_required_if()
    {
        $this->assertFalse(_class('validate')->required_if(null, []));
        $this->assertTrue(_class('validate')->required_if('', ['param' => 'pswd']));
        $this->assertTrue(_class('validate')->required_if('some', ['param' => 'pswd']));
        $this->assertTrue(_class('validate')->required_if('', ['param' => 'pswd'], ['pswd' => false]));
        $this->assertTrue(_class('validate')->required_if('some', ['param' => 'pswd'], ['pswd' => false]));
        $this->assertTrue(_class('validate')->required_if('', ['param' => 'pswd'], ['pswd' => 0]));
        $this->assertTrue(_class('validate')->required_if('some', ['param' => 'pswd'], ['pswd' => 0]));
        $this->assertTrue(_class('validate')->required_if('', ['param' => 'pswd'], ['pswd' => '']));
        $this->assertTrue(_class('validate')->required_if('some', ['param' => 'pswd'], ['pswd' => '']));
        $this->assertTrue(_class('validate')->required_if('', ['param' => 'pswd'], ['other' => '']));
        $this->assertTrue(_class('validate')->required_if('some', ['param' => 'pswd'], ['other' => '']));
        $this->assertFalse(_class('validate')->required_if('', ['param' => 'pswd'], ['pswd' => '  ']));
        $this->assertTrue(_class('validate')->required_if('some', ['param' => 'pswd'], ['pswd' => '  ']));
        $this->assertFalse(_class('validate')->required_if('', ['param' => 'pswd'], ['pswd' => 'not_empty']));
        $this->assertTrue(_class('validate')->required_if('some', ['param' => 'pswd'], ['pswd' => 'not_empty']));
    }
    public function test_matches()
    {
        $this->assertFalse(_class('validate')->matches('', ['param' => 'my_field']));
        $_POST['my_field'] = '55';
        $this->assertFalse(_class('validate')->matches('', ['param' => 'my_field'], ['my_field' => '55']));
        $this->assertTrue(_class('validate')->matches('55', ['param' => 'my_field'], ['my_field' => '55']));
    }
    public function test_differs()
    {
        $this->assertTrue(_class('validate')->differs('', ['param' => 'my_field']));
        $_POST['my_field'] = '55';
        $this->assertTrue(_class('validate')->differs('', ['param' => 'my_field'], ['my_field' => '55']));
        $this->assertFalse(_class('validate')->differs('55', ['param' => 'my_field'], ['my_field' => '55']));
    }
    public function test_regex_match()
    {
        $regex = '/^[a-z]+@[a-z]+\.[a-z]+$/';
        $this->assertFalse(_class('validate')->regex_match('testme@yfixnet', ['param' => $regex]));
        $this->assertTrue(_class('validate')->regex_match('testme@yfix.net', ['param' => $regex]));

        //		$this->assertFalse( validate('testme@yfixnet', 'regex_match['.$regex.']') );
//		$this->assertTrue( validate('testme@yfix.net', 'regex_match['.$regex.']') );
    }
    public function test_min_length()
    {
        $this->assertFalse(_class('validate')->min_length('12345'));
        $this->assertFalse(_class('validate')->min_length('1234', ['param' => '5']));
        $this->assertTrue(_class('validate')->min_length('12345', ['param' => '5']));
        $this->assertTrue(_class('validate')->min_length('123456', ['param' => '5']));

        $this->assertFalse(validate('1234', 'min_length[5]'));
        $this->assertTrue(validate('12345', 'min_length[5]'));
        $this->assertTrue(validate('123456', 'min_length[5]'));
    }
    public function test_max_length()
    {
        $this->assertFalse(_class('validate')->max_length('12345'));
        $this->assertTrue(_class('validate')->max_length('1234', ['param' => '5']));
        $this->assertTrue(_class('validate')->max_length('12345', ['param' => '5']));
        $this->assertFalse(_class('validate')->max_length('123456', ['param' => '5']));

        $this->assertTrue(validate('1234', 'max_length[5]'));
        $this->assertTrue(validate('12345', 'max_length[5]'));
        $this->assertFalse(validate('123456', 'max_length[5]'));
    }
    public function test_exact_length()
    {
        $this->assertFalse(_class('validate')->exact_length('12345'));
        $this->assertFalse(_class('validate')->exact_length('1234', ['param' => '5']));
        $this->assertTrue(_class('validate')->exact_length('12345', ['param' => '5']));
        $this->assertFalse(_class('validate')->exact_length('123456', ['param' => '5']));

        $this->assertFalse(validate('1234', 'exact_length[5]'));
        $this->assertTrue(validate('12345', 'exact_length[5]'));
        $this->assertFalse(validate('123456', 'exact_length[5]'));
    }
    public function test_length()
    {
        $this->assertFalse(_class('validate')->length('12345'));
        $this->assertFalse(_class('validate')->length('1234', ['param' => '5']));
        $this->assertTrue(_class('validate')->length('12345', ['param' => '5']));
        $this->assertFalse(_class('validate')->length('123456', ['param' => '5']));
        $this->assertTrue(_class('validate')->length('123456', ['param' => '1,10']));
        $this->assertFalse(_class('validate')->length('123456', ['param' => '8,10']));

        $this->assertFalse(validate('1234', 'length[5]'));
        $this->assertTrue(validate('12345', 'length[5]'));
        $this->assertFalse(validate('123456', 'length[5]'));
        $this->assertTrue(validate('123456', 'length[1,10]'));
        $this->assertFalse(validate('123456', 'length[8,10]'));
    }
    public function test_greater_than()
    {
        $this->assertTrue(_class('validate')->greater_than('12345'));
        $this->assertTrue(_class('validate')->greater_than('12345', ['param' => '0']));
        $this->assertFalse(_class('validate')->greater_than('4', ['param' => '5']));
        $this->assertFalse(_class('validate')->greater_than('5', ['param' => '5']));
        $this->assertTrue(_class('validate')->greater_than('6', ['param' => '5']));

        $this->assertTrue(validate('12345', 'greater_than[0]'));
        $this->assertFalse(validate('4', 'greater_than[5]'));
        $this->assertFalse(validate('5', 'greater_than[5]'));
        $this->assertTrue(validate('6', 'greater_than[5]'));
    }
    public function test_less_than()
    {
        $this->assertFalse(_class('validate')->less_than('12345'));
        $this->assertFalse(_class('validate')->less_than('12345', ['param' => '0']));
        $this->assertTrue(_class('validate')->less_than('4', ['param' => '5']));
        $this->assertFalse(_class('validate')->less_than('5', ['param' => '5']));
        $this->assertFalse(_class('validate')->less_than('6', ['param' => '5']));

        $this->assertFalse(validate('12345', 'less_than[0]'));
        $this->assertTrue(validate('4', 'less_than[5]'));
        $this->assertFalse(validate('5', 'less_than[5]'));
        $this->assertFalse(validate('6', 'less_than[5]'));
    }
    public function test_greater_than_equal_to()
    {
        $this->assertTrue(_class('validate')->greater_than_equal_to('12345'));
        $this->assertTrue(_class('validate')->greater_than_equal_to('12345', ['param' => '0']));
        $this->assertFalse(_class('validate')->greater_than_equal_to('4', ['param' => '5']));
        $this->assertTrue(_class('validate')->greater_than_equal_to('5', ['param' => '5']));
        $this->assertTrue(_class('validate')->greater_than_equal_to('6', ['param' => '5']));

        $this->assertTrue(validate('12345', 'greater_than_equal_to[0]'));
        $this->assertFalse(validate('4', 'greater_than_equal_to[5]'));
        $this->assertTrue(validate('5', 'greater_than_equal_to[5]'));
        $this->assertTrue(validate('6', 'greater_than_equal_to[5]'));
    }
    public function test_less_than_equal_to()
    {
        $this->assertFalse(_class('validate')->less_than_equal_to('12345'));
        $this->assertFalse(_class('validate')->less_than_equal_to('12345', ['param' => '0']));
        $this->assertTrue(_class('validate')->less_than_equal_to('4', ['param' => '5']));
        $this->assertTrue(_class('validate')->less_than_equal_to('5', ['param' => '5']));
        $this->assertFalse(_class('validate')->less_than_equal_to('6', ['param' => '5']));

        $this->assertFalse(validate('12345', 'less_than_equal_to[0]'));
        $this->assertTrue(validate('4', 'less_than_equal_to[5]'));
        $this->assertTrue(validate('5', 'less_than_equal_to[5]'));
        $this->assertFalse(validate('6', 'less_than_equal_to[5]'));
    }
    public function test_alpha()
    {
        $this->assertFalse(_class('validate')->alpha(''));
        $this->assertFalse(_class('validate')->alpha(null));
        $this->assertFalse(_class('validate')->alpha(false));
        $this->assertFalse(_class('validate')->alpha([]));

        $this->assertFalse(_class('validate')->alpha('~'));
        $this->assertTrue(_class('validate')->alpha('a'));
        $this->assertTrue(_class('validate')->alpha('abcdefghijklmnopqrstuvwxyz'));
        $this->assertTrue(_class('validate')->alpha('ABCDEFGHIJKLMNOPQRSTUVWXYZ'));
        $this->assertFalse(_class('validate')->alpha('абвгдеёжзиЙклмнопрстуфхцчшщьъэюяієї'));
        $this->assertFalse(_class('validate')->alpha('АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЭЮЯІЄЇ'));
        $this->assertFalse(_class('validate')->alpha('0'));

        $this->assertFalse(validate('~', 'alpha'));
        $this->assertTrue(validate('a', 'alpha'));
        $this->assertTrue(validate('abcdefghijklmnopqrstuvwxyz', 'alpha'));
        $this->assertTrue(validate('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'alpha'));
        $this->assertFalse(validate('абвгдеёжзиЙклмнопрстуфхцчшщьъэюяієї', 'alpha'));
        $this->assertFalse(validate('АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЭЮЯІЄЇ', 'alpha'));
        $this->assertFalse(validate('0', 'alpha'));
    }
    public function test_alpha_spaces()
    {
        $this->assertFalse(_class('validate')->alpha_spaces(''));
        $this->assertFalse(_class('validate')->alpha_spaces(null));
        $this->assertFalse(_class('validate')->alpha_spaces(false));

        $this->assertFalse(_class('validate')->alpha_spaces('~'));
        $this->assertTrue(_class('validate')->alpha_spaces('a'));
        $this->assertTrue(_class('validate')->alpha_spaces('abcdefghijklmnopqrstuvwxyz'));
        $this->assertTrue(_class('validate')->alpha_spaces('ABCDEFGHIJKLMNOPQRSTUVWXYZ'));
        $this->assertFalse(_class('validate')->alpha_spaces('абвгдеёжзиЙклмнопрстуфхцчшщьъэюяієї'));
        $this->assertFalse(_class('validate')->alpha_spaces('АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЭЮЯІЄЇ'));
        $this->assertFalse(_class('validate')->alpha_spaces('0'));
        $this->assertTrue(_class('validate')->alpha_spaces(' abcdefghijklmnopqrstuvwxyz'));
        $this->assertTrue(_class('validate')->alpha_spaces(' ABCDEFGHIJKLMNOPQRSTUVWXYZ'));
        $this->assertTrue(_class('validate')->alpha_spaces(' '));

        $this->assertFalse(validate('~', 'alpha_spaces'));
        $this->assertTrue(validate('a', 'alpha_spaces'));
        $this->assertTrue(validate('abcdefghijklmnopqrstuvwxyz', 'alpha_spaces'));
        $this->assertTrue(validate('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'alpha_spaces'));
        $this->assertFalse(validate('абвгдеёжзиЙклмнопрстуфхцчшщьъэюяієї', 'alpha_spaces'));
        $this->assertFalse(validate('АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЭЮЯІЄЇ', 'alpha_spaces'));
        $this->assertFalse(validate('0', 'alpha_spaces'));
        $this->assertTrue(validate(' abcdefghijklmnopqrstuvwxyz', 'alpha_spaces'));
        $this->assertTrue(validate(' ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'alpha_spaces'));
        $this->assertTrue(validate(' ', 'alpha_spaces'));
    }
    public function test_alpha_numeric()
    {
        $this->assertFalse(_class('validate')->alpha_numeric(''));
        $this->assertFalse(_class('validate')->alpha_numeric(null));
        $this->assertFalse(_class('validate')->alpha_numeric(false));

        $this->assertFalse(_class('validate')->alpha_numeric('~'));
        $this->assertTrue(_class('validate')->alpha_numeric('a'));
        $this->assertTrue(_class('validate')->alpha_numeric('abcdefghijklmnopqrstuvwxyz01234567890'));
        $this->assertTrue(_class('validate')->alpha_numeric('ABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890'));
        $this->assertTrue(_class('validate')->alpha_numeric('0123456789'));

        $this->assertFalse(validate('~', 'alpha_numeric'));
        $this->assertTrue(validate('a', 'alpha_numeric'));
        $this->assertTrue(validate('abcdefghijklmnopqrstuvwxyz01234567890', 'alpha_numeric'));
        $this->assertTrue(validate('ABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890', 'alpha_numeric'));
        $this->assertTrue(validate('0123456789', 'alpha_numeric'));
    }
    public function test_alpha_numeric_spaces()
    {
        $this->assertFalse(_class('validate')->alpha_numeric_spaces(''));
        $this->assertFalse(_class('validate')->alpha_numeric_spaces(null));
        $this->assertFalse(_class('validate')->alpha_numeric_spaces(false));

        $this->assertFalse(_class('validate')->alpha_numeric_spaces('~'));
        $this->assertTrue(_class('validate')->alpha_numeric_spaces('a'));
        $this->assertTrue(_class('validate')->alpha_numeric_spaces(' abcdefghijklmnopqrstuvwxyz01234567890 '));
        $this->assertTrue(_class('validate')->alpha_numeric_spaces(' ABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890 '));
        $this->assertTrue(_class('validate')->alpha_numeric_spaces('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'));
        $this->assertTrue(_class('validate')->alpha_numeric_spaces('0123456789'));
        $this->assertTrue(_class('validate')->alpha_numeric_spaces(' '));

        $this->assertFalse(validate('~', 'alpha_numeric_spaces'));
        $this->assertTrue(validate('a', 'alpha_numeric_spaces'));
        $this->assertTrue(validate(' abcdefghijklmnopqrstuvwxyz01234567890 ', 'alpha_numeric_spaces'));
        $this->assertTrue(validate(' ABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890 ', 'alpha_numeric_spaces'));
        $this->assertTrue(validate('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 'alpha_numeric_spaces'));
        $this->assertTrue(validate('0123456789', 'alpha_numeric_spaces'));
        $this->assertTrue(validate(' ', 'alpha_numeric_spaces'));
    }
    public function test_alpha_dash()
    {
        $this->assertFalse(_class('validate')->alpha_dash(''));
        $this->assertFalse(_class('validate')->alpha_dash(null));
        $this->assertFalse(_class('validate')->alpha_dash(false));

        $this->assertFalse(_class('validate')->alpha_dash('~'));
        $this->assertTrue(_class('validate')->alpha_dash('a'));
        $this->assertTrue(_class('validate')->alpha_dash('abcdefghijklmnopqrstuvwxyz0123456789_-'));
        $this->assertTrue(_class('validate')->alpha_dash('_'));
        $this->assertTrue(_class('validate')->alpha_dash('-_-'));

        $this->assertFalse(validate('~', 'alpha_dash'));
        $this->assertTrue(validate('a', 'alpha_dash'));
        $this->assertTrue(validate('abcdefghijklmnopqrstuvwxyz0123456789_-', 'alpha_dash'));
        $this->assertTrue(validate('_', 'alpha_dash'));
        $this->assertTrue(validate('-_-', 'alpha_dash'));
    }
    public function test_unicode_alpha()
    {
        $this->assertFalse(_class('validate')->unicode_alpha(''));
        $this->assertFalse(_class('validate')->unicode_alpha(null));
        $this->assertFalse(_class('validate')->unicode_alpha(false));

        $this->assertFalse(_class('validate')->unicode_alpha('~'));
        $this->assertTrue(_class('validate')->unicode_alpha('a'));
        $this->assertTrue(_class('validate')->unicode_alpha('abcdefghijklmnopqrstuvwxyz'));
        $this->assertTrue(_class('validate')->unicode_alpha('ABCDEFGHIJKLMNOPQRSTUVWXYZ'));
        $this->assertTrue(_class('validate')->unicode_alpha('абвгдеёжзиЙклмнопрстуфхцчшщьъэюяієї'));
        $this->assertTrue(_class('validate')->unicode_alpha('АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЭЮЯІЄЇ'));
        $this->assertFalse(_class('validate')->unicode_alpha('0'));

        $this->assertFalse(validate('~', 'unicode_alpha'));
        $this->assertTrue(validate('a', 'unicode_alpha'));
        $this->assertTrue(validate('abcdefghijklmnopqrstuvwxyz', 'unicode_alpha'));
        $this->assertTrue(validate('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'unicode_alpha'));
        $this->assertTrue(validate('абвгдеёжзиЙклмнопрстуфхцчшщьъэюяієї', 'unicode_alpha'));
        $this->assertTrue(validate('АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЭЮЯІЄЇ', 'unicode_alpha'));
        $this->assertFalse(validate('0', 'unicode_alpha'));
    }
    public function test_unicode_alpha_spaces()
    {
        $this->assertFalse(_class('validate')->unicode_alpha_spaces(''));
        $this->assertFalse(_class('validate')->unicode_alpha_spaces(null));
        $this->assertFalse(_class('validate')->unicode_alpha_spaces(false));

        $this->assertFalse(_class('validate')->unicode_alpha_spaces('~'));
        $this->assertTrue(_class('validate')->unicode_alpha_spaces('a'));
        $this->assertTrue(_class('validate')->unicode_alpha_spaces('abcdefghijklmnopqrstuvwxyz'));
        $this->assertTrue(_class('validate')->unicode_alpha_spaces('ABCDEFGHIJKLMNOPQRSTUVWXYZ'));
        $this->assertTrue(_class('validate')->unicode_alpha_spaces('абвгдеёжзиЙклмнопрстуфхцчшщьъэюяієї'));
        $this->assertTrue(_class('validate')->unicode_alpha_spaces('АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЭЮЯІЄЇ'));
        $this->assertTrue(_class('validate')->unicode_alpha_spaces(' абвгдеёжзиЙклмнопрстуфхцчшщьъэюяієїАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЭЮЯІЄЇ'));
        $this->assertFalse(_class('validate')->unicode_alpha_spaces('0'));
        $this->assertTrue(_class('validate')->unicode_alpha_spaces(' abcdefghijklmnopqrstuvwxyz '));
        $this->assertTrue(_class('validate')->unicode_alpha_spaces(' ABCDEFGHIJKLMNOPQRSTUVWXYZ '));
        $this->assertTrue(_class('validate')->unicode_alpha_spaces(' '));

        $this->assertFalse(validate('~', 'unicode_alpha_spaces'));
        $this->assertTrue(validate('a', 'unicode_alpha_spaces'));
        $this->assertTrue(validate('abcdefghijklmnopqrstuvwxyz', 'unicode_alpha_spaces'));
        $this->assertTrue(validate('ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'unicode_alpha_spaces'));
        $this->assertTrue(validate('абвгдеёжзиЙклмнопрстуфхцчшщьъэюяієї', 'unicode_alpha_spaces'));
        $this->assertTrue(validate('АБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЭЮЯІЄЇ', 'unicode_alpha_spaces'));
        $this->assertTrue(validate(' абвгдеёжзиЙклмнопрстуфхцчшщьъэюяієїАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЭЮЯІЄЇ', 'unicode_alpha_spaces'));
        $this->assertFalse(validate('0', 'unicode_alpha_spaces'));
        $this->assertTrue(validate(' abcdefghijklmnopqrstuvwxyz ', 'unicode_alpha_spaces'));
        $this->assertTrue(validate(' ABCDEFGHIJKLMNOPQRSTUVWXYZ ', 'unicode_alpha_spaces'));
        $this->assertTrue(validate(' ', 'unicode_alpha_spaces'));
    }
    public function test_unicode_alpha_numeric()
    {
        $this->assertFalse(_class('validate')->unicode_alpha_numeric(''));
        $this->assertFalse(_class('validate')->unicode_alpha_numeric(null));
        $this->assertFalse(_class('validate')->unicode_alpha_numeric(false));

        $this->assertFalse(_class('validate')->unicode_alpha_numeric('~'));
        $this->assertTrue(_class('validate')->unicode_alpha_numeric('a'));
        $this->assertTrue(_class('validate')->unicode_alpha_numeric('abcdefghijklmnopqrstuvwxyz01234567890'));
        $this->assertTrue(_class('validate')->unicode_alpha_numeric('ABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890'));
        $this->assertTrue(_class('validate')->unicode_alpha_numeric('абвгдеёжзиЙклмнопрстуфхцчшщьъэюяієїАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЭЮЯІЄЇ0123456789'));
        $this->assertTrue(_class('validate')->unicode_alpha_numeric('0123456789'));

        $this->assertFalse(validate('~', 'unicode_alpha_numeric'));
        $this->assertTrue(validate('a', 'unicode_alpha_numeric'));
        $this->assertTrue(validate('abcdefghijklmnopqrstuvwxyz01234567890', 'unicode_alpha_numeric'));
        $this->assertTrue(validate('ABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890', 'unicode_alpha_numeric'));
        $this->assertTrue(validate('абвгдеёжзиЙклмнопрстуфхцчшщьъэюяієїАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЭЮЯІЄЇ0123456789', 'unicode_alpha_numeric'));
        $this->assertTrue(validate('0123456789', 'unicode_alpha_numeric'));
    }
    public function test_unicode_alpha_numeric_spaces()
    {
        $this->assertFalse(_class('validate')->unicode_alpha_numeric_spaces(''));
        $this->assertFalse(_class('validate')->unicode_alpha_numeric_spaces(null));
        $this->assertFalse(_class('validate')->unicode_alpha_numeric_spaces(false));

        $this->assertFalse(_class('validate')->unicode_alpha_numeric_spaces('~'));
        $this->assertTrue(_class('validate')->unicode_alpha_numeric_spaces('a'));
        $this->assertTrue(_class('validate')->unicode_alpha_numeric_spaces(' abcdefghijklmnopqrstuvwxyz01234567890 '));
        $this->assertTrue(_class('validate')->unicode_alpha_numeric_spaces(' ABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890 '));
        $this->assertTrue(_class('validate')->unicode_alpha_numeric_spaces('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'));
        $this->assertTrue(_class('validate')->unicode_alpha_numeric_spaces(' абвгдеёжзиЙклмнопрстуфхцчшщьъэюяієїАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЭЮЯІЄЇ0123456789'));
        $this->assertTrue(_class('validate')->unicode_alpha_numeric_spaces('0123456789'));
        $this->assertTrue(_class('validate')->unicode_alpha_numeric_spaces(' '));

        $this->assertFalse(validate('~', 'unicode_alpha_numeric_spaces'));
        $this->assertTrue(validate('a', 'unicode_alpha_numeric_spaces'));
        $this->assertTrue(validate(' abcdefghijklmnopqrstuvwxyz01234567890 ', 'unicode_alpha_numeric_spaces'));
        $this->assertTrue(validate(' ABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890 ', 'unicode_alpha_numeric_spaces'));
        $this->assertTrue(validate('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ', 'unicode_alpha_numeric_spaces'));
        $this->assertTrue(validate(' абвгдеёжзиЙклмнопрстуфхцчшщьъэюяієїАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЭЮЯІЄЇ0123456789', 'unicode_alpha_numeric_spaces'));
        $this->assertTrue(validate('0123456789', 'unicode_alpha_numeric_spaces'));
        $this->assertTrue(validate(' ', 'unicode_alpha_numeric_spaces'));
    }
    public function test_unicode_alpha_dash()
    {
        $this->assertFalse(_class('validate')->unicode_alpha_dash(''));
        $this->assertFalse(_class('validate')->unicode_alpha_dash(null));
        $this->assertFalse(_class('validate')->unicode_alpha_dash(false));

        $this->assertFalse(_class('validate')->unicode_alpha_dash('~'));
        $this->assertTrue(_class('validate')->unicode_alpha_dash('a'));
        $this->assertTrue(_class('validate')->unicode_alpha_dash('abcdefghijklmnopqrstuvwxyz0123456789_-'));
        $this->assertTrue(_class('validate')->unicode_alpha_dash('abcdefghijklmnopqrstuvwxyz0123456789_-абвгдеёжзиЙклмнопрстуфхцчшщьъэюяієїАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЭЮЯІЄЇ0123456789'));
        $this->assertTrue(_class('validate')->unicode_alpha_dash('_'));
        $this->assertTrue(_class('validate')->unicode_alpha_dash('-_-'));

        $this->assertFalse(validate('~', 'unicode_alpha_dash'));
        $this->assertTrue(validate('a', 'unicode_alpha_dash'));
        $this->assertTrue(validate('abcdefghijklmnopqrstuvwxyz0123456789_-', 'unicode_alpha_dash'));
        $this->assertTrue(validate('abcdefghijklmnopqrstuvwxyz0123456789_-абвгдеёжзиЙклмнопрстуфхцчшщьъэюяієїАБВГДЕЁЖЗИЙКЛМНОПРСТУФХЦЧШЩЬЪЭЮЯІЄЇ0123456789', 'unicode_alpha_dash'));
        $this->assertTrue(validate('_', 'unicode_alpha_dash'));
        $this->assertTrue(validate('-_-', 'unicode_alpha_dash'));
    }
    public function test_numeric()
    {
        $this->assertFalse(_class('validate')->numeric(''));
        $this->assertFalse(_class('validate')->numeric(null));
        $this->assertFalse(_class('validate')->numeric(false));

        $this->assertFalse(_class('validate')->numeric('~'));
        $this->assertFalse(_class('validate')->numeric('abcdefghijklmnopqrstuvwxyz'));
        $this->assertTrue(_class('validate')->numeric('0123456789'));
        $this->assertTrue(_class('validate')->numeric(123456789));
        //		$this->assertTrue( _class('validate')->numeric(1.1) );
        $this->assertTrue(_class('validate')->numeric('1.1'));
        $this->assertTrue(_class('validate')->numeric('-1.1'));

        $this->assertFalse(validate('~', 'numeric'));
        $this->assertFalse(validate('abcdefghijklmnopqrstuvwxyz', 'numeric'));
        $this->assertTrue(validate('0123456789', 'numeric'));
        $this->assertTrue(validate(123456789, 'numeric'));
        //		$this->assertTrue( validate(1.1,'numeric') );
        $this->assertTrue(validate('1.1', 'numeric'));
        $this->assertTrue(validate('-1.1', 'numeric'));
    }
    public function test_integer()
    {
        $this->assertFalse(_class('validate')->integer(''));
        $this->assertFalse(_class('validate')->integer(null));
        $this->assertFalse(_class('validate')->integer(false));

        $this->assertFalse(_class('validate')->integer('~'));
        $this->assertFalse(_class('validate')->integer('abcdefghijklmnopqrstuvwxyz'));
        $this->assertTrue(_class('validate')->integer('0'));
        $this->assertTrue(_class('validate')->integer('1230'));
        $this->assertTrue(_class('validate')->integer(1234567890));
        $this->assertTrue(_class('validate')->integer(-1234567890));
        $this->assertFalse(_class('validate')->integer(1.1));
        $this->assertFalse(_class('validate')->integer(-111.11));

        $this->assertFalse(validate('~', 'integer'));
        $this->assertFalse(validate('abcdefghijklmnopqrstuvwxyz', 'integer'));
        $this->assertTrue(validate('0', 'integer'));
        $this->assertTrue(validate('1230', 'integer'));
        $this->assertTrue(validate(1234567890, 'integer'));
        $this->assertTrue(validate(-1234567890, 'integer'));
        $this->assertFalse(validate(1.1, 'integer'));
        $this->assertFalse(validate(-111.11, 'integer'));
    }
    public function test_decimal()
    {
        $this->assertFalse(_class('validate')->decimal(''));
        $this->assertFalse(_class('validate')->decimal(null));
        $this->assertFalse(_class('validate')->decimal(false));

        $this->assertFalse(_class('validate')->decimal('~'));
        $this->assertFalse(_class('validate')->decimal('abcdefghijklmnopqrstuvwxyz'));
        $this->assertFalse(_class('validate')->decimal('0'));
        $this->assertFalse(_class('validate')->decimal(1));
        $this->assertFalse(_class('validate')->decimal(0.0));
        $this->assertTrue(_class('validate')->decimal('0.0'));
        //		$this->assertTrue( _class('validate')->decimal(1.1) );
        //		$this->assertTrue( _class('validate')->decimal(-111.11) );

        $this->assertFalse(validate('~', 'decimal'));
        $this->assertFalse(validate('abcdefghijklmnopqrstuvwxyz', 'decimal'));
        $this->assertFalse(validate('0', 'decimal'));
        $this->assertFalse(validate(1, 'decimal'));
        $this->assertFalse(validate(0.0, 'decimal'));
        $this->assertTrue(validate('0.0', 'decimal'));
        //		$this->assertTrue( validate(1.1,'decimal') );
//		$this->assertTrue( validate(-111.11,'decimal') );
    }
    public function test_is_natural()
    {
        $this->assertFalse(_class('validate')->is_natural(''));
        $this->assertFalse(_class('validate')->is_natural(null));
        $this->assertFalse(_class('validate')->is_natural(false));

        $this->assertFalse(_class('validate')->is_natural('~'));
        $this->assertFalse(_class('validate')->is_natural('abcdefghijklmnopqrstuvwxyz'));
        $this->assertTrue(_class('validate')->is_natural('0'));
        $this->assertTrue(_class('validate')->is_natural('1'));
        $this->assertTrue(_class('validate')->is_natural(1234567890));
        $this->assertFalse(_class('validate')->is_natural(-1));
        $this->assertFalse(_class('validate')->is_natural(1.1));

        $this->assertFalse(validate('~', 'is_natural'));
        $this->assertFalse(validate('abcdefghijklmnopqrstuvwxyz', 'is_natural'));
        $this->assertTrue(validate('0', 'is_natural'));
        $this->assertTrue(validate('1', 'is_natural'));
        $this->assertTrue(validate(1234567890, 'is_natural'));
        $this->assertFalse(validate(-1, 'is_natural'));
        $this->assertFalse(validate(1.1, 'is_natural'));
    }
    public function test_is_natural_no_zero()
    {
        $this->assertFalse(_class('validate')->is_natural_no_zero(''));
        $this->assertFalse(_class('validate')->is_natural_no_zero(null));
        $this->assertFalse(_class('validate')->is_natural_no_zero(false));

        $this->assertFalse(_class('validate')->is_natural_no_zero('~'));
        $this->assertFalse(_class('validate')->is_natural_no_zero('abcdefghijklmnopqrstuvwxyz'));
        $this->assertFalse(_class('validate')->is_natural_no_zero('0'));
        $this->assertTrue(_class('validate')->is_natural_no_zero('1'));
        $this->assertTrue(_class('validate')->is_natural_no_zero(1234567890));
        $this->assertFalse(_class('validate')->is_natural_no_zero(-1234567890));
        $this->assertFalse(_class('validate')->is_natural_no_zero(1.1));

        $this->assertFalse(validate('~', 'is_natural_no_zero'));
        $this->assertFalse(validate('abcdefghijklmnopqrstuvwxyz', 'is_natural_no_zero'));
        $this->assertFalse(validate('0', 'is_natural_no_zero'));
        $this->assertTrue(validate('1', 'is_natural_no_zero'));
        $this->assertTrue(validate(1234567890, 'is_natural_no_zero'));
        $this->assertFalse(validate(-1234567890, 'is_natural_no_zero'));
        $this->assertFalse(validate(1.1, 'is_natural_no_zero'));
    }
    public function test_valid_email()
    {
        $this->assertFalse(_class('validate')->valid_email(''));
        $this->assertFalse(_class('validate')->valid_email(null));
        $this->assertFalse(_class('validate')->valid_email(false));
        $this->assertFalse(_class('validate')->valid_email([]));

        $this->assertFalse(_class('validate')->valid_email(' '));
        $this->assertFalse(_class('validate')->valid_email(PHP_EOL));
        //		$this->assertTrue( _class('validate')->valid_email('testme@localhost') );
        $this->assertTrue(_class('validate')->valid_email('testme@yfix.net'));
        $this->assertFalse(_class('validate')->valid_email('testme.something.wrong.yfix.net'));

        $this->assertFalse(validate(' ', 'valid_email'));
        $this->assertFalse(validate(PHP_EOL, 'valid_email'));
        //		$this->assertTrue( validate('testme@localhost','valid_email') );
        $this->assertTrue(validate('testme@yfix.net', 'valid_email'));
        $this->assertFalse(validate('testme.something.wrong.yfix.net', 'valid_email'));
    }
    public function test_email()
    {
        $this->assertFalse(_class('validate')->email(''));
        $this->assertFalse(_class('validate')->email(null));
        $this->assertFalse(_class('validate')->email(false));
        $this->assertFalse(_class('validate')->email([]));

        $this->assertFalse(_class('validate')->email(' '));
        $this->assertFalse(_class('validate')->email(PHP_EOL));
        //		$this->assertTrue( _class('validate')->email('testme@localhost') );
        $this->assertTrue(_class('validate')->email('testme@yfix.net'));
        $this->assertFalse(_class('validate')->email('testme.something.wrong.yfix.net'));

        $this->assertFalse(validate(' ', 'email'));
        $this->assertFalse(validate(PHP_EOL, 'email'));
        //		$this->assertTrue( validate('testme@localhost','email') );
        $this->assertTrue(validate('testme@yfix.net', 'email'));
        $this->assertFalse(validate('testme.something.wrong.yfix.net', 'email'));
    }
    public function test_valid_emails()
    {
        $this->assertFalse(_class('validate')->valid_emails(''));
        $this->assertFalse(_class('validate')->valid_emails(null));
        $this->assertFalse(_class('validate')->valid_emails(false));
        $this->assertFalse(_class('validate')->valid_emails([]));

        $this->assertFalse(_class('validate')->valid_emails(' '));
        $this->assertFalse(_class('validate')->valid_emails(PHP_EOL));
        $this->assertTrue(_class('validate')->valid_emails('testme@yfix.net'));
        $this->assertFalse(_class('validate')->valid_emails('testme.something.wrong.yfix.net'));
        $this->assertTrue(_class('validate')->valid_emails('testme@yfix.net,testme2@yfix.net'));
        $this->assertTrue(_class('validate')->valid_emails('testme@yfix.net,testme2@yfix.net,testme3@yfix.net,testme4@yfix.net,testme5@yfix.net'));
        $this->assertFalse(_class('validate')->valid_emails('testme@yfix.net,testme2@yfix.net,testme3@yfix.net,testme4@yfix.net,@yfix.net'));

        $this->assertFalse(validate(' ', 'valid_emails'));
        $this->assertFalse(validate(PHP_EOL, 'valid_emails'));
        $this->assertTrue(validate('testme@yfix.net', 'valid_emails'));
        $this->assertFalse(validate('testme.something.wrong.yfix.net', 'valid_emails'));
        $this->assertTrue(validate('testme@yfix.net,testme2@yfix.net', 'valid_emails'));
        $this->assertTrue(validate('testme@yfix.net,testme2@yfix.net,testme3@yfix.net,testme4@yfix.net,testme5@yfix.net', 'valid_emails'));
        $this->assertFalse(validate('testme@yfix.net,testme2@yfix.net,testme3@yfix.net,testme4@yfix.net,@yfix.net', 'valid_emails'));
    }
    public function test_valid_base64()
    {
        $this->assertFalse(_class('validate')->valid_base64(''));
        $this->assertFalse(_class('validate')->valid_base64(null));
        $this->assertFalse(_class('validate')->valid_base64(false));
        $this->assertFalse(@_class('validate')->valid_base64([]));

        $this->assertFalse(_class('validate')->valid_base64(' '));
        $this->assertFalse(_class('validate')->valid_base64(PHP_EOL));
        $this->assertTrue(_class('validate')->valid_base64('abcdefghijklmnopqrstuvwxyz0123456789'));
        $this->assertTrue(_class('validate')->valid_base64('aGVsbG8=')); // base64_encode("hello")
        $this->assertFalse(_class('validate')->valid_base64('abcdefghijklmnopqrstuvwxyz0123456789/=_'));

        $this->assertFalse(_class('validate')->valid_base64(' '));
        $this->assertFalse(_class('validate')->valid_base64(PHP_EOL));
        $this->assertTrue(_class('validate')->valid_base64('abcdefghijklmnopqrstuvwxyz0123456789'));
        $this->assertTrue(_class('validate')->valid_base64('aGVsbG8=')); // base64_encode("hello")
        $this->assertFalse(_class('validate')->valid_base64('abcdefghijklmnopqrstuvwxyz0123456789/=_'));
    }
    public function test_valid_url()
    {
        $this->assertFalse(_class('validate')->valid_url(''));
        $this->assertFalse(_class('validate')->valid_url(null));
        $this->assertFalse(_class('validate')->valid_url(false));
        $this->assertFalse(_class('validate')->valid_url([]));

        $this->assertFalse(_class('validate')->valid_url(' '));
        $this->assertFalse(_class('validate')->valid_url(PHP_EOL));
        $this->assertTrue(_class('validate')->valid_url('index'));
        $this->assertTrue(_class('validate')->valid_url('index.html'));
        $this->assertTrue(_class('validate')->valid_url('script.js'));
        //		$this->assertTrue( _class('validate')->valid_url('/script.js') );
        //		$this->assertTrue( _class('validate')->valid_url('./script.js') );
        //		$this->assertTrue( _class('validate')->valid_url('../script.js') );
        //		$this->assertTrue( _class('validate')->valid_url('//script.js') );
        $this->assertTrue(_class('validate')->valid_url('http://domain.com/script.js'));
        $this->assertTrue(_class('validate')->valid_url('https://domain.com/script.js'));
        $this->assertTrue(_class('validate')->valid_url('http://domain.com/script.js?key1=val1&key2=val2#fragment'));
        $this->assertTrue(_class('validate')->valid_url('http://domain.com:8080/some_path/script.js?key1=val1&key2=val2#fragment'));
        $this->assertTrue(_class('validate')->valid_url('http://user:pswd@domain.com:8080/some_path/script.js?key1=val1&key2=val2#fragment'));
        //		$this->assertTrue( _class('validate')->valid_url('ftp://user:pswd@domain.com:8080/some_path/script.js') );

        $this->assertFalse(validate(' ', 'valid_url'));
        $this->assertFalse(validate(PHP_EOL, 'valid_url'));
        $this->assertTrue(validate('index', 'valid_url'));
        $this->assertTrue(validate('index.html', 'valid_url'));
        $this->assertTrue(validate('script.js', 'valid_url'));
        //		$this->assertTrue( validate('/script.js','valid_url') );
        //		$this->assertTrue( validate('./script.js','valid_url') );
        //		$this->assertTrue( validate('../script.js','valid_url') );
        //		$this->assertTrue( validate('//script.js','valid_url') );
        $this->assertTrue(validate('http://domain.com/script.js', 'valid_url'));
        $this->assertTrue(validate('https://domain.com/script.js', 'valid_url'));
        $this->assertTrue(validate('http://domain.com/script.js?key1=val1&key2=val2#fragment', 'valid_url'));
        $this->assertTrue(validate('http://domain.com:8080/some_path/script.js?key1=val1&key2=val2#fragment', 'valid_url'));
        $this->assertTrue(validate('http://user:pswd@domain.com:8080/some_path/script.js?key1=val1&key2=val2#fragment', 'valid_url'));
        //		$this->assertTrue( validate('ftp://user:pswd@domain.com:8080/some_path/script.js','valid_url') );
    }
    public function test_valid_hostname()
    {
        $this->assertTrue(_class('validate')->valid_hostname('yahoo.com'));
        $this->assertTrue(_class('validate')->valid_hostname('facebook.com'));
        $this->assertTrue(_class('validate')->valid_hostname('google.to.cc'));
        $this->assertTrue(_class('validate')->valid_hostname('mkyong-info.com'));
        $this->assertTrue(_class('validate')->valid_hostname('mkyong.com.au'));
        $this->assertTrue(_class('validate')->valid_hostname('verdi.com.my'));
        $this->assertTrue(_class('validate')->valid_hostname('google.t.co'));
        $this->assertTrue(_class('validate')->valid_hostname('google.t.t.co'));
        $this->assertTrue(_class('validate')->valid_hostname('abc.99.com'));
        $this->assertTrue(_class('validate')->valid_hostname('abc.mkyong-info.com'));
        $this->assertTrue(_class('validate')->valid_hostname('abc-123.mkyong-99b.com.my'));
        $this->assertTrue(_class('validate')->valid_hostname('travel.travel'));
        $this->assertTrue(_class('validate')->valid_hostname('www.travel.travel'));
        $this->assertTrue(_class('validate')->valid_hostname('zp.ua'));
        $this->assertTrue(_class('validate')->valid_hostname('i.ua'));
        $this->assertTrue(_class('validate')->valid_hostname('a12.b34.c56.d78.e90.long.sub.hostname.name'));
        $this->assertTrue(_class('validate')->valid_hostname('test.info'));
        $this->assertTrue(_class('validate')->valid_hostname('t.co'));
        $this->assertTrue(_class('validate')->valid_hostname('localhost'));

        $this->assertFalse(_class('validate')->valid_hostname('123,345.com'));
        $this->assertFalse(_class('validate')->valid_hostname('.com.my'));
        $this->assertFalse(_class('validate')->valid_hostname('-bad.com'));
        $this->assertFalse(_class('validate')->valid_hostname('-.bad.com'));
        //		$this->assertFalse( _class('validate')->valid_hostname('yfix.net123') );
        //		$this->assertFalse( _class('validate')->valid_hostname('0.bad.com') );
        //		$this->assertFalse( _class('validate')->valid_hostname('google.t') );
        //		$this->assertFalse( _class('validate')->valid_hostname('google.t.t.t') );
        $this->assertFalse(_class('validate')->valid_hostname('youtube.com/users/abc'));
        $this->assertFalse(_class('validate')->valid_hostname('%*.com'));
        $this->assertFalse(_class('validate')->valid_hostname('test..com'));
        $this->assertFalse(_class('validate')->valid_hostname('test.test..com'));
        $this->assertFalse(_class('validate')->valid_hostname('test..test.com'));
    }
    public function test_valid_ip()
    {
        $this->assertFalse(_class('validate')->valid_ip(''));
        $this->assertFalse(_class('validate')->valid_ip(null));
        $this->assertFalse(_class('validate')->valid_ip(false));
        $this->assertFalse(_class('validate')->valid_ip([]));
        $this->assertFalse(_class('validate')->valid_ip(' '));
        $this->assertFalse(_class('validate')->valid_ip(PHP_EOL));
        $this->assertTrue(_class('validate')->valid_ip('127.0.0.1'));
        //		$this->assertTrue( _class('validate')->valid_ip('127.1') );
        $this->assertTrue(_class('validate')->valid_ip('192.168.1.1'));
        $this->assertTrue(_class('validate')->valid_ip('255.255.255.255'));
        $this->assertTrue(_class('validate')->valid_ip('0.0.0.0'));
        $this->assertTrue(_class('validate')->valid_ip('8.8.8.8'));
        $this->assertFalse(_class('validate')->valid_ip('.8.8.8'));
        $this->assertFalse(_class('validate')->valid_ip('256.8.8.8'));
        $this->assertFalse(_class('validate')->valid_ip('256.256.256.256'));
    }
    public function test_xss_clean()
    {
        $this->assertEquals('test', _class('validate')->xss_clean('test'));
        $this->assertEquals('Hello, i try to [removed]alert&#40;\'Hack\'&#41;;[removed] your site', _class('validate')->xss_clean('Hello, i try to <script>alert(\'Hack\');</script> your site'));
    }
    public function test_active_url()
    {
        $this->assertTrue(_class('validate')->active_url('google.com'));
        $this->assertTrue(_class('validate')->active_url('http://google.com'));
        $this->assertTrue(_class('validate')->active_url('https://google.com'));
    }
    public function test_between()
    {
        $this->assertFalse(_class('validate')->between('0', '1,10'));
        $this->assertTrue(_class('validate')->between('5', '1,10'));
        $this->assertFalse(_class('validate')->between('50', '1,10'));
        $this->assertTrue(_class('validate')->between('a', 'a,z'));
        $this->assertFalse(_class('validate')->between('a', 'b,z'));
    }
    public function test_chars()
    {
        $this->assertTrue(_class('validate')->chars('0', '1,10'));
        $this->assertTrue(_class('validate')->chars('a', 'a,b,c'));
        $this->assertFalse(_class('validate')->chars('d', 'a,b,c'));
    }
    // TODO: need to setup datetime settings in portable way to pass tests on travis.ci and drone.io
    public function test_before_date()
    {
        date_default_timezone_set('UTC');
        //		$this->assertFalse( _class('validate')->before_date('2014-03-01', '') );
        $this->assertFalse(_class('validate')->before_date('2014-03-01', '2013-12-12'));
        //		$this->assertTrue( _class('validate')->before_date('2014-03-01', '2014-12-12') );
    }
    public function test_after_date()
    {
        date_default_timezone_set('UTC');
        //		$this->assertTrue( _class('validate')->after_date('2014-03-01', '') );
        //		$this->assertTrue( _class('validate')->after_date('2014-03-01', '2013-12-12') );
        $this->assertFalse(_class('validate')->after_date('2014-03-01', '2014-12-12'));
    }
    public function test_valid_date()
    {
        date_default_timezone_set('UTC');
        $this->assertTrue(_class('validate')->valid_date('2014-03-01'));
        $this->assertTrue(_class('validate')->valid_date('2014-03-01 01:01:01'));
        $this->assertTrue(_class('validate')->valid_date('2014-03-01 23:23:23'));
        $this->assertTrue(_class('validate')->valid_date('2014-12-31 23:59:59'));
        $this->assertTrue(_class('validate')->valid_date('1970-01-01 00:00:00'));
        //		$this->assertFalse( _class('validate')->valid_date('197-01-01 00:00:00') );
        //		$this->assertFalse( _class('validate')->valid_date('197-01-01') );
        $this->assertFalse(_class('validate')->valid_date('197'));
        $this->assertFalse(_class('validate')->valid_date(''));
    }
    public function test_valid_date_format()
    {
        date_default_timezone_set('UTC');
        $this->assertTrue(_class('validate')->valid_date_format('2014-03-01', 'Y-m-d'));
        $this->assertTrue(_class('validate')->valid_date_format('6.1.2009 13:00+01:00', 'j.n.Y H:iP'));
        $this->assertFalse(_class('validate')->valid_date_format('6.1.2009', 'j.n.Y H:iP'));
    }
    public function test_phone_cleanup()
    {
        $this->assertEquals('', _class('validate')->phone_cleanup(''));
        $this->assertEquals('', _class('validate')->phone_cleanup('1'));
        $this->assertEquals('', _class('validate')->phone_cleanup('azvbbgggggdgdfs'));
        $this->assertEquals('', _class('validate')->phone_cleanup('123'));
        $this->assertEquals('', _class('validate')->phone_cleanup('123456'));
        $this->assertEquals('', _class('validate')->phone_cleanup('12345678'));

        $this->assertEquals('+380631234567', _class('validate')->phone_cleanup('631234567'));
        $this->assertEquals('+380631234567', _class('validate')->phone_cleanup('0631234567'));
        $this->assertEquals('+380631234567', _class('validate')->phone_cleanup('63-123-45-67'));
        $this->assertEquals('+380631234567', _class('validate')->phone_cleanup('063-123-45-67'));
        $this->assertEquals('+380631234567', _class('validate')->phone_cleanup('63 123 45 67'));
        $this->assertEquals('+380631234567', _class('validate')->phone_cleanup('063 123 45 67'));
        $this->assertEquals('+380631234567', _class('validate')->phone_cleanup(' 63 - 123 - 45 - 67 '));
        $this->assertEquals('+380631234567', _class('validate')->phone_cleanup(' 063 - 123 - 45 - 67 '));
        $this->assertEquals('+380631234567', _class('validate')->phone_cleanup(' +38 063 - 123 - 45 - 67 '));
        $this->assertEquals('+380631234567', _class('validate')->phone_cleanup('+380631234567'));

        $this->assertEquals('+150631234567', _class('validate')->phone_cleanup('631234567', ['param' => '15']));
        $this->assertEquals('+150631234567', _class('validate')->phone_cleanup('0631234567', ['param' => '15']));
        $this->assertEquals('+150631234567', _class('validate')->phone_cleanup('+150631234567', ['param' => '15']));

        $this->assertEquals('+79090000000', _class('validate')->phone_cleanup('9090000000', ['param' => '7']));
        $this->assertEquals('+79090000000', _class('validate')->phone_cleanup('+79090000000', ['param' => '7']));
    }
    public function test_valid_phone()
    {
        $this->assertFalse(_class('validate')->valid_phone(''));
        $this->assertFalse(_class('validate')->valid_phone('1'));
        $this->assertFalse(_class('validate')->valid_phone('azvbbgggggdgdfs'));
        $this->assertFalse(_class('validate')->valid_phone('123'));
        $this->assertFalse(_class('validate')->valid_phone('123456'));
        $this->assertFalse(_class('validate')->valid_phone('12345678'));

        $this->assertTrue(_class('validate')->valid_phone('631234567'));
        $this->assertTrue(_class('validate')->valid_phone('0631234567'));
        $this->assertTrue(_class('validate')->valid_phone('63-123-45-67'));
        $this->assertTrue(_class('validate')->valid_phone('063-123-45-67'));
        $this->assertTrue(_class('validate')->valid_phone('63 123 45 67'));
        $this->assertTrue(_class('validate')->valid_phone('063 123 45 67'));
        $this->assertTrue(_class('validate')->valid_phone(' 63 - 123 - 45 - 67 '));
        $this->assertTrue(_class('validate')->valid_phone(' 063 - 123 - 45 - 67 '));
        $this->assertTrue(_class('validate')->valid_phone(' +38 063 - 123 - 45 - 67 '));
        $this->assertTrue(_class('validate')->valid_phone('+380631234567'));

        $this->assertTrue(_class('validate')->valid_phone('631234567', ['param' => '15']));
        $this->assertTrue(_class('validate')->valid_phone('0631234567', ['param' => '15']));
        $this->assertTrue(_class('validate')->valid_phone('+150631234567', ['param' => '15']));
    }
    public function test_standard_text()
    {
        // TODO: standard_text Returns FALSE if form field is not valid text (letters, numbers, whitespace, dashes, periods and underscores are allowed)
    }
    public function test_valid_image()
    {
        // TODO
    }
    public function test_mime()
    {
        // TODO
    }
    public function test_depends_on()
    {
        // TODO: from kohana: depends_on Returns FALSE if form field(s) defined in parameter are not filled independs_on[field_name]
    }
    public function test_credit_card()
    {
        // TODO: from kohana: credit_card Returns FALSE if credit card is not validcredit_card[mastercard]
    }
    public function test_cleanup_10()
    {
        $rules_raw = [
            'name' => 'trim',
        ];
        $rules_cleaned = [
            'name' => [
                ['trim', null],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_11()
    {
        $rules_raw = [
            'name' => ['trim'],
        ];
        $rules_cleaned = [
            'name' => [
                ['trim', null],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_12()
    {
        $rules_raw = [
            'name' => ['trim', new stdClass(), null, '', ' ', false, "\t\t"],
        ];
        $rules_cleaned = [
            'name' => [
                ['trim', null],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_13()
    {
        $rules_raw = [
            'name' => 'trim||||||||||||',
        ];
        $rules_cleaned = [
            'name' => [
                ['trim', null],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_14()
    {
        $rules_raw = [
            'name' => ['trim||||||||||||', false, null, ' | '],
        ];
        $rules_cleaned = [
            'name' => [
                ['trim', null],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_15()
    {
        $rules_raw = [
            'name' => [' trim | ', false, null, ' | '],
        ];
        $rules_cleaned = [
            'name' => [
                ['trim', null],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_22()
    {
        $rules_raw = [
            'name' => 'trim|required',
        ];
        $rules_cleaned = [
            'name' => [
                ['trim', null],
                ['required', null],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_23()
    {
        $rules_raw = [
            'name' => ['trim', 'required'],
        ];
        $rules_cleaned = [
            'name' => [
                ['trim', null],
                ['required', null],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_24()
    {
        $rules_raw = [
            'captcha' => 'trim|captcha',
        ];
        $rules_cleaned = [
            'captcha' => [
                ['trim', null],
                ['captcha', null],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_25()
    {
        $rules_raw = [
            'name' => ['trim|required|min_length[2]|max_length[12]|is_unique[user.login]|xss_clean'],
        ];
        $rules_cleaned = [
            'name' => [
                ['trim', null],
                ['required', null],
                ['min_length', '2'],
                ['max_length', '12'],
                ['is_unique', 'user.login'],
                ['xss_clean', null],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_26_1()
    {
        $closure = function ($in) {
            return module('register')->_login_not_exists($in);
        };
        $rules_raw = [
            'name' => ['trim|required|min_length[2]|max_length[12]|is_unique[user.login]|xss_clean', $closure],
        ];
        $rules_cleaned = [
            'name' => [
                ['trim', null],
                ['required', null],
                ['min_length', '2'],
                ['max_length', '12'],
                ['is_unique', 'user.login'],
                ['xss_clean', null],
                [$closure, null],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_26_2()
    {
        $closure = function ($in) {
            return module('register')->_login_not_exists($in);
        };
        $rules_raw = [
            'name' => ['trim|required|min_length:2|max_length:12|is_unique:user.login|xss_clean', $closure],
        ];
        $rules_cleaned = [
            'name' => [
                ['trim', null],
                ['required', null],
                ['min_length', '2'],
                ['max_length', '12'],
                ['is_unique', 'user.login'],
                ['xss_clean', null],
                [$closure, null],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_27()
    {
        $closure = function ($in) {
            return module('register')->_login_not_exists($in);
        };
        $rules_raw = [
            'name' => ['trim|required', 'min_length[2]|max_length[12]|is_unique[user.login]', 'xss_clean', $closure],
        ];
        $rules_cleaned = [
            'name' => [
                ['trim', null],
                ['required', null],
                ['min_length', '2'],
                ['max_length', '12'],
                ['is_unique', 'user.login'],
                ['xss_clean', null],
                [$closure, null],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_31()
    {
        $closure = function ($in) {
            return module('register')->_login_not_exists($in);
        };
        $rules_raw = [
            'name' => ['trim|required', 'min_length[2]|max_length[12]|is_unique[user.login]', 'xss_clean', $closure],
            'captcha' => 'trim|captcha',
            'content' => 'trim|required',
        ];
        $rules_cleaned = [
            'name' => [
                ['trim', null],
                ['required', null],
                ['min_length', '2'],
                ['max_length', '12'],
                ['is_unique', 'user.login'],
                ['xss_clean', null],
                [$closure, null],
            ],
            'captcha' => [
                ['trim', null],
                ['captcha', null],
            ],
            'content' => [
                ['trim', null],
                ['required', null],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_32()
    {
        $closure = function ($in) {
            return module('register')->_login_not_exists($in);
        };
        $rules_raw = [
            '__before__' => 'trim',
            'name' => ['required', 'min_length[2]|max_length[12]|is_unique[user.login]', 'xss_clean', $closure],
            'captcha' => 'captcha',
            'content' => 'required',
        ];
        $rules_cleaned = [
            'name' => [
                ['trim', null],
                ['required', null],
                ['min_length', '2'],
                ['max_length', '12'],
                ['is_unique', 'user.login'],
                ['xss_clean', null],
                [$closure, null],
            ],
            'captcha' => [
                ['trim', null],
                ['captcha', null],
            ],
            'content' => [
                ['trim', null],
                ['required', null],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_33_1()
    {
        $closure = function ($in) {
            return module('register')->_login_not_exists($in);
        };
        $rules_raw = [
            '__before__' => ['trim', 'required'],
            '__after__' => 'md5',
            'name' => ['min_length[2]', 'max_length[12]|is_unique[user.login]', 'xss_clean', $closure],
            'captcha' => 'captcha',
            'content' => '',
        ];
        $rules_cleaned = [
            'name' => [
                ['trim', null],
                ['required', null],
                ['min_length', '2'],
                ['max_length', '12'],
                ['is_unique', 'user.login'],
                ['xss_clean', null],
                [$closure, null],
                ['md5', null],
            ],
            'captcha' => [
                ['trim', null],
                ['required', null],
                ['captcha', null],
                ['md5', null],
            ],
            'content' => [
                ['trim', null],
                ['required', null],
                ['md5', null],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_33_2()
    {
        $closure = function ($in) {
            return module('register')->_login_not_exists($in);
        };
        $rules_raw = [
            '__before__' => ['trim', 'required'],
            '__after__' => 'md5',
            'name' => ['min_length:2', 'max_length:12|is_unique:user.login', 'xss_clean', $closure],
            'captcha' => 'captcha',
            'content' => '',
        ];
        $rules_cleaned = [
            'name' => [
                ['trim', null],
                ['required', null],
                ['min_length', '2'],
                ['max_length', '12'],
                ['is_unique', 'user.login'],
                ['xss_clean', null],
                [$closure, null],
                ['md5', null],
            ],
            'captcha' => [
                ['trim', null],
                ['required', null],
                ['captcha', null],
                ['md5', null],
            ],
            'content' => [
                ['trim', null],
                ['required', null],
                ['md5', null],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_34()
    {
        $rules_raw = ['test' => 'min_length:2|max_length:12|is_unique:user.login|between:1,10|chars:a,b,c,d|regex:[a-z0-9]+'];
        $rules_cleaned = [
            'test' => [
                ['min_length', '2'],
                ['max_length', '12'],
                ['is_unique', 'user.login'],
                ['between', '1,10'],
                ['chars', 'a,b,c,d'],
                ['regex', '[a-z0-9]+'],
            ],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
    public function test_cleanup_split_trim()
    {
        $rules_raw = [
            'test1,test2,test3' => 'min_length:2|max_length:12|is_unique:user.login|between:1,10|chars:a,b,c,d|regex:[a-z0-9]+',
        ];
        $a = [
            ['min_length', '2'],
            ['max_length', '12'],
            ['is_unique', 'user.login'],
            ['between', '1,10'],
            ['chars', 'a,b,c,d'],
            ['regex', '[a-z0-9]+'],
        ];
        $rules_cleaned = [
            'test1' => $a,
            'test2' => $a,
            'test3' => $a,
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));

        $rules_raw = [
            ' test1, test2, test3 ' => 'min_length:2|max_length:12|is_unique:user.login|between:1,10|chars:a,b,c,d|regex:[a-z0-9]+',
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));

        $rules_raw = [
            'test1' => 'min_length:2|max_length:12',
            'test2' => 'min_length:2|max_length:12',
            'test3' => 'min_length:2|max_length:12',
            'test1,test2,test3 ' => 'is_unique:user.login|between:1,10|chars:a,b,c,d|regex:[a-z0-9]+',
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));

        $rules_raw = [
            'test1' => 'min_length:2|max_length:12',
            'test2,test3' => 'min_length:2|max_length:12',
            'test1,test2,test3 ' => 'is_unique:user.login|between:1,10|chars:a,b,c,d|regex:[a-z0-9]+',
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));

        $rules_raw = [
            'test1' => ['min_length:2', 'max_length:12'],
            'test2,test3' => 'min_length:2|max_length:12',
            'test1,test2,test3 ' => 'is_unique:user.login|between:1,10|chars:a,b,c,d|regex:[a-z0-9]+',
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));

        $rules_raw = [
            'test1' => ['min_length:2', 'max_length:12'],
            'test2,test3' => ['min_length:2', 'max_length:12'],
            'test1,test2,test3 ' => ['is_unique:user.login|between:1,10|chars:a,b,c,d|regex:[a-z0-9]+'],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));

        $rules_raw = [
            'test1' => ['min_length:2', 'max_length:12'],
            'test2,test3' => ['min_length:2', 'max_length:12'],
            'test1,test2,test3 ' => ['is_unique:user.login', 'between:1,10', 'chars:a,b,c,d', 'regex:[a-z0-9]+'],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));

        $rules_raw = [
            'test1' => 'min_length:2|max_length:12',
            'test2' => 'min_length:2|max_length:12',
            'test3' => 'min_length:2|max_length:12',
            'test1,test2,test3 ' => ['is_unique:user.login', 'between:1,10', 'chars:a,b,c,d', 'regex:[a-z0-9]+'],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));

        $rules_raw = [
            ' test1 ' => 'min_length:2|max_length:12',
            ' test2 ' => 'min_length:2|max_length:12',
            ' test3 ' => 'min_length:2|max_length:12',
            ' test1 , test2 , test3 ' => ['is_unique:user.login', 'between:1,10', 'chars:a,b,c,d', 'regex:[a-z0-9]+'],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));

        $rules_raw = [
            ' ' => '',
            ' test1 ' => 'min_length:2|max_length:12',
            '  ' => [''],
            ' test2    ' => 'min_length:2|max_length:12',
            ' test3  ' => 'min_length:2|max_length:12',
            '   ' => '  ',
            '  test1, test2 ,test3  ' => ['is_unique:user.login', 'between:1,10', 'chars:a,b,c,d', 'regex:[a-z0-9]+'],
        ];
        $this->assertEquals($rules_cleaned, _class('validate')->_validate_rules_cleanup($rules_raw));
    }
}
