<?php


require_once __DIR__ . '/yf_unit_tests_setup.php';

/* TODO:
* tab_start()
* fieldset_start()
* row_start()
* _dd_row_html()
* _input_assing_params_from_validate()
*/

class class_form_test extends yf\tests\wrapper
{
    private static $_bak_settings = [];
    private static $css = [];
    private static $action = '';
    public static function setUpBeforeClass() : void
    {
        $_GET = [
            'object' => 'dynamic',
            'action' => 'unit_test_form',
        ];
        self::$_bak_settings['REWRITE_MODE'] = $GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] ?? null;
        $GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = false;
        self::$_bak_settings['CONF_BOXES_USE_BTN_GROUP'] = $GLOBALS['PROJECT_CONF']['form2']['CONF_BOXES_USE_BTN_GROUP'] ?? null;
        $GLOBALS['PROJECT_CONF']['form2']['CONF_BOXES_USE_BTN_GROUP'] = false;
        _class('form2')->CONF_BOXES_USE_BTN_GROUP = false;
        $GLOBALS['CONF']['form2']['CONF_CSRF_PROTECTION'] = false;
        _class('form2')->CONF_CSRF_PROTECTION = false;

        _class('html')->_ids = [];

        $css = _class('html5fw');

        $css_framework = conf('css_framework') ?: 'bs2';
        $obj = _class('html5fw_' . $css_framework, 'classes/html5fw/');
        $css = get_object_vars($obj);

        self::$css = $css;

        self::$action = url('/dynamic/unit_test_form');
    }
    public static function tearDownAfterClass() : void
    {
        $GLOBALS['PROJECT_CONF']['tpl']['REWRITE_MODE'] = self::$_bak_settings['REWRITE_MODE'];
        $GLOBALS['PROJECT_CONF']['form2']['CONF_BOXES_USE_BTN_GROUP'] = self::$_bak_settings['CONF_BOXES_USE_BTN_GROUP'];
        _class('form2')->CONF_BOXES_USE_BTN_GROUP = self::$_bak_settings['CONF_BOXES_USE_BTN_GROUP'];
    }
    public function test_empty_form()
    {
        $this->assertEquals(str_replace(
            PHP_EOL,
            '',
            '<form method="post" action="' . self::$action . '" class="form-horizontal" name="form_action" autocomplete="1">' .
            '<fieldset>' .
            '</fieldset>' .
            '</form>'
        ), str_replace(PHP_EOL, '', trim(form())));
    }
    public function test_input_text()
    {
        $this->assertEquals(str_replace(
            PHP_EOL,
            '',
            '<form method="post" action="' . self::$action . '" class="form-horizontal" name="form_action" autocomplete="1">' .
            '<fieldset>' .
            '<div class="' . self::$css['CLASS_FORM_GROUP'] . '">' .
            '<label class="' . self::$css['CLASS_LABEL'] . '" for="name">Name</label>' .
            '<div class="' . _attr_class_clean(self::$css['CLASS_CONTROLS'] . ' ' . self::$css['CLASS_DESC']) . '">' .
            '<input name="name" type="text" id="name" class="form-control" placeholder="Name">' .
            '</div>' .
            '</div>' .
            '</fieldset>' .
            '</form>'
        ), str_replace(PHP_EOL, '', trim(form()->text('name'))));
    }
    public function test_input_text_no_form()
    {
        $form = form('', ['no_form' => 1])->text('name');

        $this->assertEquals(str_replace(
            PHP_EOL,
            '',
            '<div class="' . self::$css['CLASS_FORM_GROUP'] . '">' .
            '<label class="' . self::$css['CLASS_LABEL'] . '" for="name">Name</label>' .
            '<div class="' . _attr_class_clean(self::$css['CLASS_CONTROLS'] . ' ' . self::$css['CLASS_DESC']) . '">' .
            '<input name="name" type="text" id="name" class="form-control" placeholder="Name">' .
            '</div>' .
            '</div>'
        ), str_replace(PHP_EOL, '', trim($form)));
    }
    public function test_form_from_array()
    {
        $a = [['text', 'name']];
        $this->assertEquals(
            '<form method="post" action="' . self::$action . '" class="form-horizontal" name="form_action" autocomplete="1"><fieldset><div class="' . self::$css['CLASS_FORM_GROUP'] . '">' .
            '<label class="' . self::$css['CLASS_LABEL'] . '" for="name">Name</label><div class="' . _attr_class_clean(self::$css['CLASS_CONTROLS'] . ' ' . self::$css['CLASS_DESC']) . '"><input name="name" type="text" id="name" class="form-control" placeholder="Name"></div></div>' .
            '</fieldset></form>',
            str_replace(PHP_EOL, '', trim(form()->array_to_form($a)))
        );
    }
    public function test_form_auto()
    {
        $data = ['user' => 'name', 'email' => 'some@email.com'];
        $this->assertEquals(
            '<form method="post" action="' . self::$action . '" class="form-horizontal" name="form_action" autocomplete="1"><fieldset><div class="' . self::$css['CLASS_FORM_GROUP'] . '">' .
            '<div class="' . _attr_class_clean(self::$css['CLASS_CONTROLS_BUTTONS'] . ' ' . self::$css['CLASS_NO_LABEL_BUTTONS']) . '"><button type="submit" name="back_link" id="back_link" class="btn btn-default btn-primary" value="Save"><i class="icon-save fa fa-save"></i> Save</button></div>' .
            '</div></fieldset></form>',
            str_replace(PHP_EOL, '', trim(form($data)->auto()))
        );
    }
    public function test_input_text_simple()
    {
        $this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim(form_item($r)->text('name')));
        $this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim(self::form_no_chain($r)->text('name')));
        $this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim(self::form_no_chain($r)->text('name', '')));
        $this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim(self::form_no_chain($r)->text('name', '', ['stacked' => 1])));
        $this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name">', trim(self::form_no_chain($r)->text('name', '', ['stacked' => 1])));
        $this->assertEquals('<span class="stacked-item"><input name="name" type="text" id="name" class="form-control" placeholder="Name">' . PHP_EOL . '</span>', trim(form('', ['no_form' => 1])->text('name', '', ['stacked' => 1])));
        $this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Desc">', trim(self::form_no_chain($r)->text('name', ['desc' => 'Desc'])));
        $this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Desc">', trim(self::form_no_chain($r)->text('name', 'Desc')));
    }
    public function test_input_text_value()
    {
        $r['name'] = 'value1';
        $this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Desc" value="value1">', trim(self::form_no_chain($r)->text('name', ['desc' => 'Desc'])));
        $this->assertEquals('<span class="stacked-item"><input name="name" type="text" id="name" class="form-control" placeholder="Desc" value="value1">' . PHP_EOL . '</span>', trim(form($r, ['no_form' => 1])->text('name', ['stacked' => 1, 'desc' => 'Desc'])));
        $this->assertEquals('<input name="name" type="text" id="name" class="form-control" style="color:red;" placeholder="Desc" value="value1">', self::form_no_chain($r)->text('name', ['desc' => 'Desc', 'style' => 'color:red;']));
        $this->assertEquals('<input name="name" type="text" id="name" class="form-control" style="color:red;" placeholder="Desc" value="value1">', self::form_no_chain($r)->text('name', ['desc' => 'Desc', 'style' => 'color:red;', 'value' => 'value1']));
    }
    public function test_input_text_attr_data()
    {
        $this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name" data-something="5">', trim(self::form_no_chain($r)->text('name', ['data-something' => '5'])));
        $this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name" data-a1="a11" data-b1="b11">', trim(self::form_no_chain($r)->text('name', ['data-a1' => 'a11', 'data-b1' => 'b11'])));
        $this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name" data-test-escape="!@#$%^&*(&quot;&apos;&lt;&gt;?&gt;&lt;:;">', trim(self::form_no_chain($r)->text('name', ['data-test-escape' => '!@#$%^&*("\'<>?><:;'])));
    }
    public function test_input_text_attr_ng()
    {
        $this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name" ng-something="5">', trim(self::form_no_chain($r)->text('name', ['ng-something' => '5'])));
        $this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name" ng-a1="a11" ng-b1="b11">', trim(self::form_no_chain($r)->text('name', ['ng-a1' => 'a11', 'ng-b1' => 'b11'])));
        $this->assertEquals('<input name="name" type="text" id="name" class="form-control" placeholder="Name" ng-test-escape="!@#$%^&*(&quot;&apos;&lt;&gt;?&gt;&lt;:;">', trim(self::form_no_chain($r)->text('name', ['ng-test-escape' => '!@#$%^&*("\'<>?><:;'])));
    }
    public function test_input_textarea()
    {
        $this->assertEquals('<textarea id="name" name="name" placeholder="Name" contenteditable="true" class="form-control"></textarea>', trim(self::form_no_chain($r)->textarea('name')));
        $this->assertEquals('<textarea id="name" name="name" placeholder="Name" contenteditable="true" class="form-control"></textarea>', trim(self::form_no_chain($r)->textarea('name', '')));
        $this->assertEquals('<textarea id="name" name="name" placeholder="Desc" contenteditable="true" class="form-control"></textarea>', trim(self::form_no_chain($r)->textarea('name', '', ['desc' => 'Desc'])));
        $this->assertEquals('<textarea id="name" name="name" placeholder="Desc" contenteditable="true" class="form-control"></textarea>', trim(self::form_no_chain($r)->textarea('name', ['desc' => 'Desc'])));
        $this->assertEquals('<textarea id="name" name="name" placeholder="Desc" contenteditable="true" class="ckeditor form-control"></textarea>', trim(self::form_no_chain($r)->textarea('name', ['desc' => 'Desc', 'ckeditor' => true])));
    }
    public function test_input_hidden()
    {
        $this->assertEquals('<input type="hidden" id="hdn" name="hdn">', trim(self::form_no_chain($r)->hidden('hdn')));
        $this->assertEquals('<input type="hidden" id="hdn" name="hdn" value="val1">', trim(self::form_no_chain($r)->hidden('hdn', ['value' => 'val1'])));
    }
    public function test_container()
    {
        $this->assertEquals('<form method="post" action="' . self::$action . '" class="form-horizontal" name="form_action" autocomplete="1"><fieldset>' .
            '<div class="' . self::$css['CLASS_FORM_GROUP'] . '"><div class="' . _attr_class_clean(self::$css['CLASS_CONTROLS'] . self::$css['CLASS_NO_LABEL']) . '"><section id="test"></section></div></div></fieldset></form>', str_replace(PHP_EOL, '', trim(form()->container('<section id="test"></section>'))));
        $this->assertEquals('<section id="test"></section>', trim(self::form_no_chain($r)->container('<section id="test"></section>')));
    }
    public function test_select_box()
    {
        $data = ['k1' => 'v1',	'k2' => 'v2'];
        $this->assertEquals(str_replace(
            PHP_EOL,
            '',
            '<select name="myselect" id="select_box_1" class="form-control"><option value="k1">v1</option><option value="k2">v2</option></select>'
            ), str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->select_box('myselect', $data))));
        $this->assertEquals(str_replace(
            PHP_EOL,
            '',
            '<select name="myselect" id="select_box_2" class="form-control"><option value="k1">v1</option><option value="k2">v2</option></select>'
            ), str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->select_box('myselect', $data))));
        $this->assertEquals(str_replace(
            PHP_EOL,
            '',
            '<select name="myselect" id="select_box_3" class="form-control" data-unittest="val"><option value="k1">v1</option><option value="k2">v2</option></select>'
            ), str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->select_box('myselect', $data, ['data-unittest' => 'val']))));
    }
    public function test_select_box_subarray()
    {
        $data = [
            'group1' => ['k1' => 'v1', 'k2' => 'v2'],
            'group2' => ['k3' => 'v3',	'k4' => 'v4'],
        ];
        $selected = 'k3';
        $this->assertEquals('<select name="myselect" id="select_box_1" class="form-control"><optgroup label="group1" title="group1"><option value="k1">v1</option><option value="k2">v2</option></optgroup>' .
            '<optgroup label="group2" title="group2"><option value="k3">v3</option><option value="k4">v4</option></optgroup></select>', str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->select_box('myselect', $data, ['force_id' => 'select_box_1']))));
        $this->assertEquals('<select name="myselect" id="select_box_2" class="form-control"><optgroup label="group1" title="group1"><option value="k1">v1</option><option value="k2">v2</option></optgroup>' .
            '<optgroup label="group2" title="group2"><option value="k3" selected="selected">v3</option><option value="k4">v4</option></optgroup></select>', str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->select_box('myselect', $data, ['selected' => $selected, 'force_id' => 'select_box_2']))));
        $r['myselect'] = $selected;
        $this->assertEquals('<select name="myselect" id="select_box_3" class="form-control"><optgroup label="group1" title="group1"><option value="k1">v1</option><option value="k2">v2</option></optgroup>' .
            '<optgroup label="group2" title="group2"><option value="k3" selected="selected">v3</option><option value="k4">v4</option></optgroup></select>', str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->select_box('myselect', $data, ['force_id' => 'select_box_3']))));
    }
    public function test_multi_select_box()
    {
        $data = ['k1' => 'v1', 'k2' => 'v2', 'k3' => 'v2'];
        $selected = ['k2' => '1', 'k3' => '1'];
        $this->assertEquals('<select name="myselect[]" id="multi_select_1" class="form-control" multiple="multiple"><option value="k1">v1</option><option value="k2">v2</option><option value="k3">v2</option></select>', str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->multi_select_box('myselect', $data))));
        $this->assertEquals('<select name="myselect[]" id="multi_select_2" class="form-control" multiple="multiple"><option value="k1">v1</option><option value="k2" selected="selected">v2</option>' .
            '<option value="k3" selected="selected">v2</option></select>', str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->multi_select_box('myselect', $data, ['selected' => $selected]))));
        $r['myselect'] = $selected;
        $this->assertEquals('<select name="myselect[]" id="multi_select_3" class="form-control" multiple="multiple"><option value="k1">v1</option><option value="k2" selected="selected">v2</option>' .
            '<option value="k3" selected="selected">v2</option></select>', str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->multi_select_box('myselect', $data))));
    }
    public function test_check_box()
    {
        $html = html();
        $def_class = $html->CLASS_LABEL_CHECKBOX . ' ' . $html->CLASS_LABEL_CHECKBOX_INLINE;

        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="id" id="id" value="1"> &nbsp;<span>Id</span></label>', trim(self::form_no_chain($r)->check_box('id')));
        $this->assertEquals('<label class="' . $def_class . ' active"><input type="checkbox" name="id" id="id" value="1" checked="checked"> &nbsp;<span>Id</span></label>', trim(self::form_no_chain($r)->check_box('id', ['selected' => 'true'])));
        $this->assertEquals('<label class="' . $def_class . ' active"><input type="checkbox" name="id" id="id" value="1" checked="checked"> &nbsp;<span>Id</span></label>', trim(self::form_no_chain($r)->check_box('id', '1', ['selected' => 'true'])));
        $this->assertEquals('<label class="' . $def_class . ' active"><input type="checkbox" name="is_public" id="is_public" value="1" checked="checked"> &nbsp;<span>Is public</span></label>', trim(self::form_no_chain($r)->check_box('is_public', '1', ['selected' => 'true'])));
        $this->assertEquals('<label class="' . $def_class . ' active"><input type="checkbox" name="is_public" id="is_public" value="1" checked="checked"> &nbsp;<span>Is public</span></label>', trim(self::form_no_chain($r)->check_box('is_public', '1', ['checked' => 'true'])));
        $this->assertEquals('<label class="' . $def_class . ' active"><input type="checkbox" name="is_public" id="is_public" value="1" checked="checked"> &nbsp;<span>Is public</span></label>', trim(self::form_no_chain($r)->check_box('is_public', ['checked' => 'true'])));
        $this->assertEquals(
            '<label class="testme active"><input type="checkbox" name="is_public" id="is_public" value="1" checked="checked"> &nbsp;<span>Is public</span></label>',
            trim(self::form_no_chain($r)->check_box(
                'is_public',
                [
                'checked' => 'true',
                'class_label_checkbox' => 'testme',
            ]
        ))
        );
        $this->assertEquals(
            '<label class="' . $def_class . ' testme active"><input type="checkbox" name="is_public" id="is_public" value="1" checked="checked"> &nbsp;<span>Is public</span></label>',
            trim(self::form_no_chain($r)->check_box(
                'is_public',
                [
                'checked' => 'true',
                'class_add_label_checkbox' => 'testme',
            ]
        ))
        );
        $this->assertEquals(
            '<label class="' . $def_class . ' testme active"><input type="checkbox" name="is_public" id="myid" value="myval" checked="checked" class="myclass" style="color:red;" data-some="mydata"> &nbsp;<span>Is public</span></label>',
            trim(self::form_no_chain($r)->check_box(
                'is_public',
                [
                'checked' => 'true',
                'class_add_label_checkbox' => 'testme',
                'class' => 'myclass',
                'style' => 'color:red;',
                'value' => 'myval',
                'force_id' => 'myid',
                'data-some' => 'mydata',
            ]
        ))
        );
        //		$this->assertEquals(
//			'<label class="'.$def_class.' active"><input type="checkbox" name="is_public" id="is_public" value="1" checked="checked"> &nbsp;</label>'
//			, trim(self::form_no_chain($r)->check_box('is_public', array(
//				'checked' => 'true',
//				'no_label' => true,
//			)
//		)));
    }
    public function test_multi_check_box()
    {
        $html = html();
        $data = ['k1' => 'v1', 'k2' => 'v2'];
        $selected = ['k2' => '1'];
        $def_class = $html->CLASS_LABEL_CHECKBOX . ' ' . $html->CLASS_LABEL_CHECKBOX_INLINE;

        $this->assertEquals(
            '<label class="' . $def_class . '"><input type="checkbox" name="mycheck_k1" id="multi_check_box_1" value="k1"> &nbsp;<span>v1</span></label>'
            . '<label class="' . $def_class . '"><input type="checkbox" name="mycheck_k2" id="multi_check_box_2" value="k2"> &nbsp;<span>v2</span></label>',
            str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->multi_check_box('mycheck', $data)))
        );
        $this->assertEquals(
            '<label class="' . $def_class . '"><input type="checkbox" name="mycheck_k1" id="multi_check_box_3" value="k1"> &nbsp;<span>v1</span></label>'
            . '<label class="' . $def_class . ' active"><input type="checkbox" name="mycheck_k2" id="multi_check_box_4" value="k2" checked="checked"> &nbsp;<span>v2</span></label>',
            str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->multi_check_box('mycheck', $data, ['selected' => $selected])))
        );
    }
    // public function test_radio_box()
    // {
    //     $data = ['k1' => 'v1', 'k2' => 'v2'];
    //     $selected = 'k2';
    //     $this->assertEquals(
    //         '<label class="radio radio-inline"><input type="radio" name="myradio" id="radio_box_1_1" value="k1"><span>v1</span></label>'
    //         . '<label class="radio radio-inline"><input type="radio" name="myradio" id="radio_box_1_2" value="k2"><span>v2</span></label>',
    //         str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->radio_box('myradio', $data, ['force_id' => 'radio_box_1'])))
    //     );
    //     $this->assertEquals(
    //         '<label class="radio radio-inline"><input type="radio" name="myradio" id="radio_box_1_1" value="k1"><span>v1</span></label>'
    //         . '<label class="radio radio-inline active"><input type="radio" name="myradio" id="radio_box_1_2" value="k2" checked="checked"><span>v2</span></label>',
    //         str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->radio_box('myradio', $data, ['selected' => $selected, 'force_id' => 'radio_box_1'])))
    //     );
    // }
    public function test_div_box()
    {
        $data = ['k1' => 'v1', 'k2' => 'v2'];
        $selected = 'k2';
        $this->assertEquals(
            '<li class="dropdown" style="list-style-type:none;" id="mydiv"><a class="dropdown-toggle" data-toggle="dropdown">Mydiv&nbsp;<span class="caret"></span></a>' .
            '<ul class="dropdown-menu"><li class="dropdown"><a data-value="k1">v1</a></li><li class="dropdown"><a data-value="k2">v2</a></li></ul></li>',
            str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->div_box('mydiv', $data)))
        );
        $this->assertEquals(
            '<li class="dropdown" style="list-style-type:none;" id="mydiv"><a class="dropdown-toggle" data-toggle="dropdown">v2&nbsp;<span class="caret"></span></a>' .
            '<ul class="dropdown-menu"><li class="dropdown"><a data-value="k1">v1</a></li><li class="dropdown active"><a data-value="k2" data-selected="selected">v2</a></li></ul></li>',
            str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->div_box('mydiv', $data, ['selected' => $selected])))
        );
    }
    public function test_list_box()
    {
        $data = ['k1' => 'v1', 'k2' => 'v2'];
        $selected = 'k2';
        $this->assertEquals(
            '<div id="mylist" class="bfh-selectbox" data-name="mylist"><div data-value="k1">v1</div><div data-value="k2">v2</div></div>',
            str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->list_box('mylist', $data)))
        );
        $this->assertEquals(
            '<div id="mylist" class="bfh-selectbox" data-name="mylist" data-value="k2"><div data-value="k1">v1</div><div data-value="k2">v2</div></div>',
            str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->list_box('mylist', $data, ['selected' => $selected])))
        );
    }
    public function test_fieldset_start()
    {
        $this->assertEquals('<fieldset name="f1">', trim(self::form_no_chain($r)->fieldset_start('f1')));
    }
    public function test_input()
    {
        $this->assertEquals(
            '<input name="test" type="text" id="test" class="form-control" placeholder="Test">',
            trim(self::form_no_chain($r)->input('test'))
        );
    }
    public function test_password()
    {
        $this->assertEquals('<input name="test" type="password" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->password('test')));
    }
    public function test_file()
    {
        $this->assertEquals('<input name="test" type="file" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->file('test')));
    }
    public function test_button()
    {
        $this->assertEquals('<input name="test" type="button" id="test" class="form-control btn btn-default" placeholder="Test" value="Test">', trim(self::form_no_chain($r)->button('test')));
    }
    public function test_login()
    {
        $this->assertEquals('<input name="login" type="text" id="login" class="form-control" placeholder="Login">', trim(self::form_no_chain($r)->login()));
        $this->assertEquals('<input name="test" type="text" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->login('test')));
    }
    public function test_email()
    {
        $this->assertEquals('<input name="email" type="email" id="email" class="form-control" placeholder="Email">', trim(self::form_no_chain($r)->email()));
        $this->assertEquals('<input name="test" type="email" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->email('test')));
    }
    public function test_number()
    {
        $this->assertEquals('<input name="test" type="number" id="test" class="form-control" placeholder="Test" maxlength="10">', trim(self::form_no_chain($r)->number('test')));
    }
    public function test_integer()
    {
        $this->assertEquals('<input name="test" type="number" id="test" class="form-control" placeholder="Test" maxlength="10">', trim(self::form_no_chain($r)->integer('test')));
    }
    public function test_float()
    {
        $this->assertEquals('<input name="test" type="number" id="test" class="form-control" placeholder="Test" maxlength="10" step="0.01">', trim(self::form_no_chain($r)->float('test')));
    }
    public function test_decimal()
    {
        $this->assertEquals('<input name="test" type="number" id="test" class="form-control" placeholder="Test" maxlength="10" step="0.01">', trim(self::form_no_chain($r)->decimal('test')));
    }
    public function test_money()
    {
        $this->assertEquals('<input name="test" type="number" id="test" class="form-control" placeholder="Test" maxlength="8" step="0.01">', trim(self::form_no_chain($r)->money('test')));
    }
    public function test_price()
    {
        $this->assertEquals('<input name="test" type="number" id="test" class="form-control" placeholder="Test" maxlength="8" min="0" step="0.01">', trim(self::form_no_chain($r)->price('test')));
    }
    public function test_url()
    {
        $this->assertEquals('<input name="url" type="url" id="url" class="form-control" placeholder="Url">', trim(self::form_no_chain($r)->url()));
        $this->assertEquals('<input name="test" type="url" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->url('test')));
    }
    public function test_color()
    {
        $this->assertEquals('<input name="color" type="color" id="color" class="form-control" placeholder="Color">', trim(self::form_no_chain($r)->color()));
        $this->assertEquals('<input name="test" type="color" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->color('test')));
    }
    public function test_date()
    {
        $this->assertEquals('<input name="date" type="date" id="date" class="form-control" placeholder="Date">', trim(self::form_no_chain($r)->date()));
        $this->assertEquals('<input name="test" type="date" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->date('test')));
    }
    public function test_datetime()
    {
        $this->assertEquals('<input name="datetime" type="datetime" id="datetime" class="form-control" placeholder="Datetime">', trim(self::form_no_chain($r)->datetime()));
        $this->assertEquals('<input name="test" type="datetime" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->datetime('test')));
    }
    public function test_datetime_local()
    {
        $this->assertEquals('<input name="datetime_local" type="datetime-local" id="datetime_local" class="form-control" placeholder="Datetime local">', trim(self::form_no_chain($r)->datetime_local()));
        $this->assertEquals('<input name="test" type="datetime-local" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->datetime_local('test')));
    }
    public function test_month()
    {
        $this->assertEquals('<input name="month" type="month" id="month" class="form-control" placeholder="Month">', trim(self::form_no_chain($r)->month()));
        $this->assertEquals('<input name="test" type="month" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->month('test')));
    }
    public function test_range()
    {
        $this->assertEquals('<input name="range" type="range" id="range" class="form-control" placeholder="Range">', trim(self::form_no_chain($r)->range()));
        $this->assertEquals('<input name="test" type="range" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->range('test')));
    }
    public function test_search()
    {
        $this->assertEquals('<input name="search" type="search" id="search" class="form-control" placeholder="Search">', trim(self::form_no_chain($r)->search()));
        $this->assertEquals('<input name="test" type="search" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->search('test')));
    }
    public function test_tel()
    {
        $this->assertEquals('<input name="tel" type="tel" id="tel" class="form-control" placeholder="Tel">', trim(self::form_no_chain($r)->tel()));
        $this->assertEquals('<input name="test" type="tel" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->tel('test')));
    }
    public function test_phone()
    {
        $this->assertEquals('<input name="phone" type="tel" id="phone" class="form-control" placeholder="Phone">', trim(self::form_no_chain($r)->phone()));
        $this->assertEquals('<input name="test" type="tel" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->phone('test')));
    }
    public function test_time()
    {
        $this->assertEquals('<input name="time" type="time" id="time" class="form-control" placeholder="Time">', trim(self::form_no_chain($r)->time()));
        $this->assertEquals('<input name="test" type="time" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->time('test')));
    }
    public function test_week()
    {
        $this->assertEquals('<input name="week" type="week" id="week" class="form-control" placeholder="Week">', trim(self::form_no_chain($r)->week()));
        $this->assertEquals('<input name="test" type="week" id="test" class="form-control" placeholder="Test">', trim(self::form_no_chain($r)->week('test')));
    }
    // public function test_active_box()
    // {
    //     $this->assertEquals(
    //         '<label class="radio radio-inline"><input type="radio" name="active" id="radio_box_1_1" value="0"><span><span class="btn btn-default btn-mini btn-xs btn-warning">'
    //         . '<i class="icon-ban-circle fa fa-ban"></i> Disabled</span></span></label><label class="radio radio-inline"><input type="radio" name="active" id="radio_box_1_2" value="1">'
    //         . '<span><span class="btn btn-default btn-mini btn-xs btn-success"><i class="icon-ok fa fa-check"></i> Active</span></span></label>',
    //         str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->active_box('', ['force_id' => 'radio_box_1'])))
    //     );
    //     $this->assertEquals(
    //         '<label class="radio radio-inline"><input type="radio" name="test" id="radio_box_1_1" value="0"><span><span class="btn btn-default btn-mini btn-xs btn-warning">'
    //         . '<i class="icon-ban-circle fa fa-ban"></i> Disabled</span></span></label><label class="radio radio-inline"><input type="radio" name="test" id="radio_box_1_2" value="1">'
    //         . '<span><span class="btn btn-default btn-mini btn-xs btn-success"><i class="icon-ok fa fa-check"></i> Active</span></span></label>',
    //         str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->active_box('test', ['force_id' => 'radio_box_1'])))
    //     );
    // }
    // public function test_allow_deny_box()
    // {
    //     $this->assertEquals(
    //         '<label class="radio radio-inline"><input type="radio" name="active" id="radio_box_1_1" value="DENY"><span><span class="btn btn-default btn-mini btn-xs btn-warning">'
    //         . '<i class="icon-ban-circle fa fa-ban"></i> Deny</span></span></label><label class="radio radio-inline"><input type="radio" name="active" id="radio_box_1_2" value="ALLOW">'
    //         . '<span><span class="btn btn-default btn-mini btn-xs btn-success"><i class="icon-ok fa fa-check"></i> Allow</span></span></label>',
    //         str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->allow_deny_box('', ['force_id' => 'radio_box_1'])))
    //     );
    //     $this->assertEquals(
    //         '<label class="radio radio-inline"><input type="radio" name="test" id="radio_box_1_1" value="DENY"><span><span class="btn btn-default btn-mini btn-xs btn-warning">'
    //         . '<i class="icon-ban-circle fa fa-ban"></i> Deny</span></span></label><label class="radio radio-inline"><input type="radio" name="test" id="radio_box_1_2" value="ALLOW">'
    //         . '<span><span class="btn btn-default btn-mini btn-xs btn-success"><i class="icon-ok fa fa-check"></i> Allow</span></span></label>',
    //         str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->allow_deny_box('test', ['force_id' => 'radio_box_1'])))
    //     );
    // }
    // public function test_yes_no_box()
    // {
    //     $this->assertEquals(
    //         '<label class="radio radio-inline"><input type="radio" name="active" id="radio_box_1_1" value="0"><span><span class="btn btn-default btn-mini btn-xs btn-warning">'
    //         . '<i class="icon-ban-circle fa fa-ban"></i> No</span></span></label><label class="radio radio-inline"><input type="radio" name="active" id="radio_box_1_2" value="1">'
    //         . '<span><span class="btn btn-default btn-mini btn-xs btn-success"><i class="icon-ok fa fa-check"></i> Yes</span></span></label>',
    //         str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->yes_no_box('', ['force_id' => 'radio_box_1'])))
    //     );
    //     $this->assertEquals(
    //         '<label class="radio radio-inline"><input type="radio" name="test" id="radio_box_1_1" value="0"><span><span class="btn btn-default btn-mini btn-xs btn-warning">'
    //         . '<i class="icon-ban-circle fa fa-ban"></i> No</span></span></label><label class="radio radio-inline"><input type="radio" name="test" id="radio_box_1_2" value="1">'
    //         . '<span><span class="btn btn-default btn-mini btn-xs btn-success"><i class="icon-ok fa fa-check"></i> Yes</span></span></label>',
    //         str_replace(PHP_EOL, '', trim(self::form_no_chain($r)->yes_no_box('test', ['force_id' => 'radio_box_1'])))
    //     );
    // }
    public function test_submit()
    {
        $this->assertEquals('<button type="submit" id="save" class="btn btn-default btn-primary" value="Save">Save</button>', trim(self::form_no_chain($r)->submit()));
        $this->assertEquals('<button type="submit" name="test" id="test" class="btn btn-default btn-primary" value="Save">Save</button>', trim(self::form_no_chain($r)->submit('test')));
    }
    public function test_save()
    {
        $this->assertEquals(
            '<button type="submit" id="save" class="btn btn-default btn-primary" value="Save"><i class="icon-save fa fa-save"></i> Save</button>',
            trim(self::form_no_chain($r)->save())
        );
        $this->assertEquals(
            '<button type="submit" name="test" id="test" class="btn btn-default btn-primary" value="Save"><i class="icon-save fa fa-save"></i> Save</button>',
            trim(self::form_no_chain($r)->save('test'))
        );
    }
    public function test_save_and_back()
    {
        //		$r['back_link'] = 'http://somewhere.com/';
        $this->assertEquals('<button type="submit" name="back_link" id="back_link" class="btn btn-default btn-primary" value="Save"><i class="icon-save fa fa-save"></i> Save</button>', trim(self::form_no_chain($r)->save_and_back()));
        $this->assertEquals('<button type="submit" name="test" id="test" class="btn btn-default btn-primary" value="Save"><i class="icon-save fa fa-save"></i> Save</button>', trim(self::form_no_chain($r)->save_and_back('test')));
    }
    public function test_save_and_clear()
    {
        $this->assertEquals('<button type="submit" name="clear_link" id="clear_link" class="btn btn-default btn-primary" value="Save"><i class="icon-save fa fa-save"></i> Save</button>', trim(self::form_no_chain($r)->save_and_clear()));
        $this->assertEquals('<button type="submit" name="test" id="test" class="btn btn-default btn-primary" value="Save"><i class="icon-save fa fa-save"></i> Save</button>', trim(self::form_no_chain($r)->save_and_clear('test')));
    }
    public function test_info()
    {
        $this->assertEquals('<span class=" label label-info"></span>', trim(self::form_no_chain($r)->info('test')));
        $r['test'] = 'some info';
        $this->assertEquals('<span class=" label label-info">some info</span>', trim(self::form_no_chain($r)->info('test')));
    }
    public function test_info_date()
    {
        $this->assertEquals('<span class=" label label-info"></span>', trim(self::form_no_chain($r)->info_date()));
        $this->assertEquals('<span class=" label label-info"></span>', trim(self::form_no_chain($r)->info_date('test')));
        $r['test'] = '2015-01-01';
        $this->assertEquals('<span class=" label label-info">2015/01/01</span>', trim(self::form_no_chain($r)->info_date('test', '%Y/%m/%d')));
    }
    public function test_info_link()
    {
        $this->assertEquals('<span class=" label label-info"></span>', trim(self::form_no_chain($r)->info_link()));
        $this->assertEquals('<span class=" label label-info"></span>', trim(self::form_no_chain($r)->info_link('test')));
        $r['test'] = './?object=someobject&action=someaction';
        $this->assertEquals('<a href="./?object=someobject&action=someaction" name="test" class="btn btn-default btn-mini btn-xs" title="Test">./?object=someobject&action=someaction</a>', trim(self::form_no_chain($r)->info_link('test')));
    }
    public function test_tbl_link()
    {
        $this->assertEquals('<a name="test" href="./?object=someobject&action=someaction" class="btn btn-default btn-mini btn-xs"><i class="icon-tasks fa fa-tasks"></i> test</a>', trim(self::form_no_chain($r)->tbl_link('test', './?object=someobject&action=someaction')));
    }
    public function test_tbl_link_edit()
    {
        $this->assertEquals('<a name="Edit" class="btn btn-default btn-mini btn-xs ajax_edit"><i class="icon-edit fa fa-edit"></i> Edit</a>', trim(self::form_no_chain($r)->tbl_link_edit()));
        $this->assertEquals('<a name="test" class="btn btn-default btn-mini btn-xs ajax_edit"><i class="icon-edit fa fa-edit"></i> test</a>', trim(self::form_no_chain($r)->tbl_link_edit('test')));
        $r['edit_link'] = './?object=someobject&action=someaction';
        $this->assertEquals('<a name="test" href="./?object=someobject&action=someaction" class="btn btn-default btn-mini btn-xs ajax_edit"><i class="icon-edit fa fa-edit"></i> test</a>', trim(self::form_no_chain($r)->tbl_link_edit('test')));
    }
    public function test_tbl_link_delete()
    {
        $this->assertEquals('<a name="Delete" class="btn btn-default btn-mini btn-xs ajax_delete btn-danger"><i class="icon-trash fa fa-trash"></i> Delete</a>', trim(self::form_no_chain($r)->tbl_link_delete()));
        $this->assertEquals('<a name="test" class="btn btn-default btn-mini btn-xs ajax_delete btn-danger"><i class="icon-trash fa fa-trash"></i> test</a>', trim(self::form_no_chain($r)->tbl_link_delete('test')));
        $r['delete_link'] = './?object=someobject&action=someaction';
        $this->assertEquals('<a name="test" href="./?object=someobject&action=someaction" class="btn btn-default btn-mini btn-xs ajax_delete btn-danger"><i class="icon-trash fa fa-trash"></i> test</a>', trim(self::form_no_chain($r)->tbl_link_delete('test')));
    }
    public function test_tbl_link_clone()
    {
        $this->assertEquals('<a name="Clone" class="btn btn-default btn-mini btn-xs ajax_clone"><i class="icon-code-fork fa fa-code-fork"></i> Clone</a>', trim(self::form_no_chain($r)->tbl_link_clone()));
        $this->assertEquals('<a name="test" class="btn btn-default btn-mini btn-xs ajax_clone"><i class="icon-code-fork fa fa-code-fork"></i> test</a>', trim(self::form_no_chain($r)->tbl_link_clone('test')));
        $r['clone_link'] = './?object=someobject&action=someaction';
        $this->assertEquals('<a name="test" href="./?object=someobject&action=someaction" class="btn btn-default btn-mini btn-xs ajax_clone"><i class="icon-code-fork fa fa-code-fork"></i> test</a>', trim(self::form_no_chain($r)->tbl_link_clone('test')));
    }
    public function test_tbl_link_view()
    {
        $this->assertEquals('<a name="View" class="btn btn-default btn-mini btn-xs ajax_view"><i class="icon-eye-open fa fa-eye"></i> View</a>', trim(self::form_no_chain($r)->tbl_link_view()));
        $this->assertEquals('<a name="test" class="btn btn-default btn-mini btn-xs ajax_view"><i class="icon-eye-open fa fa-eye"></i> test</a>', trim(self::form_no_chain($r)->tbl_link_view('test')));
        $r['view_link'] = './?object=someobject&action=someaction';
        $this->assertEquals('<a name="test" href="./?object=someobject&action=someaction" class="btn btn-default btn-mini btn-xs ajax_view"><i class="icon-eye-open fa fa-eye"></i> test</a>', trim(self::form_no_chain($r)->tbl_link_view('test')));
    }
    public function test_tbl_link_active()
    {
        //		$this->assertEquals('<a href="active_link" class="change_active"><button class="btn btn-default btn-mini btn-xs btn-warning"><i class="icon-ban-circle fa fa-ban"></i> Disabled</button></a>'
        //			, trim(self::form_no_chain($r)->tbl_link_active()) );
        //		$this->assertEquals('<a href="active_link" class="change_active"><button class="btn btn-default btn-mini btn-xs btn-warning"><i class="icon-ban-circle fa fa-ban"></i> Disabled</button></a>'
        //			, trim(self::form_no_chain($r)->tbl_link_active('test')) );
        $r['active_link'] = './?object=someobject&action=someaction';
        $this->assertEquals('<a href="./?object=someobject&action=someaction" class="change_active"><button class="btn btn-default btn-mini btn-xs btn-warning">' .
            '<i class="icon-ban-circle fa fa-ban"></i> Disabled</button></a>', trim(self::form_no_chain($r)->tbl_link_active('test')));
    }
    public function test_form_clone()
    {
        $form1 = form(['k' => 'v'])->text('k');
        $form2 = form(['k' => 'v'])->text('k');
        $this->assertNotSame($form1, $form2);
    }
    public function test_stacked()
    {
        $desc = 'Desc';
        $r['name'] = 'value1';

        $this->assertEquals('<span class="stacked-item"><input name="name" type="text" id="name" class="form-control" placeholder="' . $desc . '" value="' . $r['name'] . '">' . PHP_EOL . '</span>', trim(form($r, ['no_form' => 1])->text('name', ['desc' => $desc, 'stacked' => 1])));

        $this->assertEquals('<span class="my_stacked_class"><input name="name" type="text" id="name" class="form-control" placeholder="' . $desc . '" value="' . $r['name'] . '">' . PHP_EOL . '</span>', trim(form($r, ['no_form' => 1])->text('name', ['desc' => $desc, 'stacked' => ['class' => 'my_stacked_class']])));

        $this->assertEquals('<span class="stacked-item my_stacked_class"><input name="name" type="text" id="name" class="form-control" placeholder="' . $desc . '" value="' . $r['name'] . '">' . PHP_EOL . '</span>', trim(form($r, ['no_form' => 1])->text('name', ['desc' => $desc, 'stacked' => ['class_add' => 'my_stacked_class']])));

        $this->assertEquals('<span class="stacked-item my_stacked_class" style="float:none;"><input name="name" type="text" id="name" class="form-control" placeholder="' . $desc . '" value="' . $r['name'] . '">' . PHP_EOL . '</span>', trim(form($r, ['no_form' => 1])->text('name', ['desc' => $desc, 'stacked' => ['class_add' => 'my_stacked_class', 'style' => 'float:none;']])));

        $this->assertEquals('<span class="stacked-item my_stacked_class" style="float:none;"><button type="submit" name="name" id="name" class="btn btn-default btn-primary" value="Save">' . $desc . '</button>' . PHP_EOL . '</span>', trim(form($r, ['no_form' => 1])->submit('name', ['desc' => $desc, 'stacked' => ['class_add' => 'my_stacked_class', 'style' => 'float:none;']])));
    }
    private function form_no_chain($r = [])
    {
        return form($r, ['no_form' => 1, 'only_content' => 1, 'no_chained_mode' => 1]);
    }
}
