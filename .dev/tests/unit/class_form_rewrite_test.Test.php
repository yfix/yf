<?php


require_once __DIR__ . '/yf_unit_tests_setup.php';

/* TODO:
*/

class class_form_rewrite_test extends yf\tests\wrapper
{
    private static $_bak_settings = [];

    public static function setUpBeforeClass() : void
    {
        $_GET = [
            'object' => 'dynamic',
            'action' => 'unit_test_form',
            'id' => 'id/with/slashes',
        ];
        self::$_bak_settings['REWRITE_MODE'] = $GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] ?? null;
        $GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = true;
        $GLOBALS['CONF']['form2']['CONF_CSRF_PROTECTION'] = false;
        _class('form2')->CONF_CSRF_PROTECTION = false;
    }

    public static function tearDownAfterClass() : void
    {
        $GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = self::$_bak_settings['REWRITE_MODE'];
    }

    public function test_rewrite_form_url()
    {
        // if (version_compare(PHP_VERSION, '8.0.0') >= 0) {
            // $this->markTestSkipped('Currently not passing in 8+');
        // }
        $this->assertEquals(
            '<form method="post" action="http://' . $_SERVER['HTTP_HOST'] . '/dynamic/unit_test_form/id%2Fwith%2Fslashes" class="form-horizontal" name="form_action" autocomplete="1"><fieldset></fieldset></form>',
            str_replace(PHP_EOL, '', trim(form()))
        );
        $GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = self::$_bak_settings['REWRITE_MODE'];
        $this->assertEquals(
            '<form method="post" action="http://' . $_SERVER['HTTP_HOST'] . '/?object=dynamic&action=unit_test_form&id=id%2Fwith%2Fslashes" class="form-horizontal" name="form_action" autocomplete="1"><fieldset></fieldset></form>',
            str_replace(PHP_EOL, '', trim(form()))
        );
    }
}
