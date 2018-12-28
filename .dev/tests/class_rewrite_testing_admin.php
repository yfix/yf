<?php

require __DIR__ . '/yf_unit_tests_setup_admin.php';
//require __DIR__.'/class_rewrite_testing_shared.php';
//class class_rewrite_testing_admin_test extends class_rewrite_testing_shared_test {
//}
class class_rewrite_testing_admin_test extends yf\tests\wrapper
{
    private static $host = 'test.dev';
    private static $_bak_settings = [];

    public static function setUpBeforeClass()
    {
        self::$_bak_settings['REWRITE_MODE'] = $GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'];
        $GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
        $_GET = [
            'object' => 'dynamic',
            'action' => 'unit_test_form',
        ];
        $_SERVER['HTTP_HOST'] = self::$host;
        _class('rewrite')->DEFAULT_HOST = self::$host;
    }
    public static function tearDownAfterClass()
    {
        $GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = self::$_bak_settings['REWRITE_MODE'];
    }
    public function test_rewrite_func_url()
    {
        $this->assertEquals(ADMIN_WEB_PATH, url('/'));
        $this->assertEquals(ADMIN_WEB_PATH . '?object=members', url('/members'));
        $this->assertEquals('http://' . self::$host . '/admin/', url('/', ['admin_host' => self::$host]));
        $this->assertEquals('http://' . self::$host . '/admin/?object=members', url('/members', ['admin_host' => self::$host]));
    }
    public function test_rewrite_func_url_user()
    {
        $this->assertEquals('http://' . self::$host . '/', url_user('/'));
        $this->assertEquals('http://' . self::$host . '/profile/1', url_user('/profile/1'));
        $this->assertEquals('http://' . self::$host . '/profile/1', url_user('/profile/show/1'));
        $this->assertEquals('http://' . self::$host . '/profile/view/1', url_user('/profile/view/1'));
    }
    public function test_rewrite_func_url_admin()
    {
        $this->assertEquals(ADMIN_WEB_PATH, url_admin('/'));
        $this->assertEquals(ADMIN_WEB_PATH . '?object=members', url_admin('/members'));
        $this->assertEquals('http://' . self::$host . '/admin/', url_admin('/', ['admin_host' => self::$host]));
        $this->assertEquals('http://' . self::$host . '/admin/?object=members', url_admin('/members', ['admin_host' => self::$host]));
        $this->assertEquals('http://' . self::$host . '/admin/?object=members', url_admin('/?object=members', ['admin_host' => self::$host]));
        $this->assertEquals('http://' . self::$host . '/admin/?object=members', url_admin(['object' => 'members', 'admin_host' => self::$host]));
        $this->assertEquals('http://' . self::$host . '/admin/?object=members&action=some_action', url_admin(['object' => 'members', 'action' => 'some_action', 'admin_host' => self::$host]));
    }
}
