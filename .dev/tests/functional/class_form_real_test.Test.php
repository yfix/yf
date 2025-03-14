<?php

require_once __DIR__ . '/db_real_abstract.php';

/**
 */
class class_form_real_test extends db_real_abstract
{
    public static function db_name()
    {
        return self::$DB_NAME;
    }
    public static function table_name($name)
    {
        return $name;
    }
    public static function setUpBeforeClass(): void
    {
        self::$_bak['DB_DRIVER'] = self::$DB_DRIVER;
        self::$DB_DRIVER = 'mysqli';
        self::_connect();
        self::utils()->truncate_database(self::db_name());
        self::$_bak['ERROR_AUTO_REPAIR'] = self::db()->ERROR_AUTO_REPAIR;
        self::db()->ERROR_AUTO_REPAIR = true;
        $GLOBALS['db'] = self::db();
        $GLOBALS['CONF']['form2']['CONF_CSRF_PROTECTION'] = false;
    }
    public static function tearDownAfterClass(): void
    {
        self::utils()->truncate_database(self::db_name());
        self::$DB_DRIVER = self::$_bak['DB_DRIVER'];
        self::db()->ERROR_AUTO_REPAIR = self::$_bak['ERROR_AUTO_REPAIR'];
    }
    public function test_insert_if_ok()
    {
        $this->assertFalse((bool) self::utils()->table_exists('static_pages'));
        $this->assertEmpty(self::db()->from('static_pages')->get());
        $this->assertTrue((bool) self::utils()->table_exists('static_pages'));
        $this->assertEmpty(self::db()->from('static_pages')->get());

        $this->assertFalse((bool) self::utils()->column_info_item('static_pages', 'text', 'nullable'));
        $this->assertTrue((bool) self::utils()->alter_column('static_pages', 'text', ['nullable' => true]));
        $this->assertTrue((bool) self::utils()->column_info_item('static_pages', 'text', 'nullable'));

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $form_id = md5(microtime());
        $_POST = [
            'name' => 'for_unit_tests',
            'active' => '1',
            '__form_id__' => $form_id,
        ];
        $this->assertTrue(main()->is_post());

        form($_POST)
            ->text('name')
            ->text('text')
            ->active_box()
            ->validate(['name' => 'trim|required', '__form_id__' => $form_id])
            ->insert_if_ok('static_pages', ['name', 'text'])
            ->render(); // !! Important to call it to run validate() and insert_if_ok() processing

        $first = self::db()->from('static_pages')->get();

        $names = ['name', 'text', 'active'];
        foreach ($names as $name) {
            $this->assertEqualsCanonicalizing($_POST[$name], $first[$name]);
        }

        $this->assertTrue((bool) self::utils()->truncate_table('static_pages'));
        $this->assertEmpty(self::db()->from('static_pages')->get());

        form($_POST)
            ->text('name')
            ->text('text')
            ->active_box()
            ->validate(['name' => 'trim|required'])
            ->insert_if_ok('static_pages', ['name', 'text'], ['text' => null])
            ->render(); // !! Important to call it to run validate() and insert_if_ok() processing

        $first = self::db()->from('static_pages')->get();

        $names = ['name', 'text', 'active'];
        foreach ($names as $name) {
            $this->assertEqualsCanonicalizing($_POST[$name], $first[$name]);
        }

        $_SERVER['REQUEST_METHOD'] = null;
        $_POST = [];
    }
    public function test_validate_custom_error()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $params = ['do_not_remove_errors' => 1];

        $form_id = md5(microtime());
        $_POST = [
            'name' => 'for_unit_tests',
            'active' => '1',
            '__form_id__' => $form_id,
        ];
        $this->assertTrue(main()->is_post());

        common()->USER_ERRORS = [];
        $this->assertEmpty(common()->USER_ERRORS);
        $custom_error = 'Such field as "%field" is empty...';
        form($_POST, $params)
            ->text('text')
            ->validate(['text' => 'trim|required', '__form_id__' => $form_id])
            ->render(); // !! Important to call it to run validate() and insert_if_ok() processing
        $cur_error = common()->USER_ERRORS['text'];
        $this->assertNotEmpty($cur_error);
        $this->assertNotEquals($custom_error, $cur_error);

        common()->USER_ERRORS = [];
        $this->assertEmpty(common()->USER_ERRORS);
        form($_POST, $params)
            ->text('text', ['validate_error' => $custom_error])
            ->validate(['text' => 'trim|required', '__form_id__' => $form_id])
            ->render(); // !! Important to call it to run validate() and insert_if_ok() processing
        $this->assertEquals(str_replace('%field', 'Text', $custom_error), common()->USER_ERRORS['text']);

        common()->USER_ERRORS = [];
        $this->assertEmpty(common()->USER_ERRORS);
        $_POST['text'] = 'something';
        $custom_error = ['integer' => 'Custom error: "%field" should be of type integer'];
        form($_POST, $params)
            ->text('text', ['validate_error' => $custom_error])
            ->validate(['text' => 'trim|required|integer', '__form_id__' => $form_id])
            ->render(); // !! Important to call it to run validate() and insert_if_ok() processing
        $this->assertEquals(str_replace('%field', 'Text', $custom_error['integer']), common()->USER_ERRORS['text']);

        common()->USER_ERRORS = [];
        $this->assertEmpty(common()->USER_ERRORS);
        $_POST['text'] = '1234';
        form($_POST, $params)
            ->text('text', ['validate_error' => $custom_error])
            ->validate(['text' => 'trim|required|integer', '__form_id__' => $form_id])
            ->render(); // !! Important to call it to run validate() and insert_if_ok() processing
        $this->assertEmpty(common()->USER_ERRORS);

        common()->USER_ERRORS = [];
        $_SERVER['REQUEST_METHOD'] = null;
        $_POST = [];
    }
    public function test_validate_post_empty_value()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $params = ['do_not_remove_errors' => 1];

        $cats = [1 => 1, 2 => 2, 3 => 3, 4 => 4];

        $form_id = md5(microtime());
        $_POST = [
            'cat_id' => [],
            '__form_id__' => $form_id,
        ];
        $this->assertTrue(main()->is_post());

        common()->USER_ERRORS = [];
        $this->assertEmpty(common()->USER_ERRORS);
        form($_POST, $params)
            ->multi_select('cat_id', $cats)
            ->validate(['cat_id' => 'required', '__form_id__' => $form_id])
            ->render(); // !! Important to call it to run validate() and insert_if_ok() processing
        $cur_error = common()->USER_ERRORS['cat_id'];
        $this->assertNotEmpty($cur_error);

        common()->USER_ERRORS = [];
        $this->assertEmpty(common()->USER_ERRORS);
        form($_POST, $params)
            ->multi_select('cat_id', $cats)
            ->validate(['cat_id[]' => 'required', '__form_id__' => $form_id])
            ->render(); // !! Important to call it to run validate() and insert_if_ok() processing
        $cur_error = common()->USER_ERRORS['cat_id'];
        $this->assertNotEmpty($cur_error);

        $_POST = [
            'cat_id' => ['   '],
            '__form_id__' => $form_id,
        ];

        common()->USER_ERRORS = [];
        $this->assertEmpty(common()->USER_ERRORS);
        form($_POST, $params)
            ->multi_select('cat_id', $cats)
            ->validate(['cat_id' => 'trim|required', '__form_id__' => $form_id])
            ->render(); // !! Important to call it to run validate() and insert_if_ok() processing
        $cur_error = common()->USER_ERRORS['cat_id'];
        $this->assertNotEmpty($cur_error);

        common()->USER_ERRORS = [];
        $_SERVER['REQUEST_METHOD'] = null;
        $_POST = [];
    }
    public function test_validate_multi_select()
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $params = ['do_not_remove_errors' => 1];

        $cats = [1 => 1, 2 => 2, 3 => 3, 4 => 4];

        $form_id = md5(microtime());
        $_POST = [
            'cat_id' => [1, 2],
            '__form_id__' => $form_id,
        ];
        $this->assertTrue(main()->is_post());

        common()->USER_ERRORS = [];
        $this->assertEmpty(common()->USER_ERRORS);
        form($_POST, $params)
            ->multi_select('cat_id', $cats)
            ->validate(['cat_id' => 'trim|required|integer', '__form_id__' => $form_id])
            ->render(); // !! Important to call it to run validate() and insert_if_ok() processing
        $this->assertEmpty(common()->USER_ERRORS);

        $_POST = [
            'cat_id' => [1, 'bad', 'unexpected', 'values', 'and some good', 2],
            '__form_id__' => $form_id,
        ];
        $this->assertTrue(main()->is_post());

        common()->USER_ERRORS = [];
        $this->assertEmpty(common()->USER_ERRORS);
        form($_POST, $params)
            ->multi_select('cat_id', $cats)
            ->validate(['cat_id' => 'trim|required|integer', '__form_id__' => $form_id])
            ->render(); // !! Important to call it to run validate() and insert_if_ok() processing
        $cur_error = common()->USER_ERRORS['cat_id'];
        $this->assertNotEmpty($cur_error);

        $_POST = [
            'cat_id1' => [1, 2],
            'cat_id2' => [2, 1],
            'cat_id3' => [3],
            '__form_id__' => $form_id,
        ];
        $this->assertTrue(main()->is_post());

        common()->USER_ERRORS = [];
        $this->assertEmpty(common()->USER_ERRORS);
        form($_POST, $params)
            ->multi_select('cat_id1', $cats)
            ->multi_select('cat_id2', $cats)
            ->multi_select('cat_id3', $cats)
            ->validate([
                'cat_id1' => 'trim|required|integer',
                'cat_id2' => 'trim|required|integer',
                'cat_id3' => 'trim|required|integer',
                '__form_id__' => $form_id,
            ])
            ->render(); // !! Important to call it to run validate() and insert_if_ok() processing
        $this->assertEmpty(common()->USER_ERRORS);

        common()->USER_ERRORS = [];
        $_SERVER['REQUEST_METHOD'] = null;
        $_POST = [];
    }
}
