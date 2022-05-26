<?php


require_once __DIR__ . '/yf_unit_tests_setup.php';

class class_html_test extends yf\tests\wrapper
{
    public function test_select_box()
    {
        $html = html();
        $def_class = $html->CLASS_SELECT_BOX;
        $def_opt_class = $html->CLASS_SELECT_OPTION_DEFAULT;
        $html->_ids = [];

        $this->assertEmpty($html->select_box('', []));

        $data = [
            1 => 'red',
            2 => 'green',
        ];

        $html->_ids = [];
        $html->AUTO_ASSIGN_IDS = false;

        $str = $html->select_box('', $data);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select class="' . $def_class . '"><option value="1">red</option><option value="2">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->select_box('myselect', $data);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect" class="' . $def_class . '"><option value="1">red</option><option value="2">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->select_box('myselect2', $data);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect2" class="' . $def_class . '"><option value="1">red</option><option value="2">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );

        $html->_ids = [];
        $html->AUTO_ASSIGN_IDS = true;

        $str = $html->select_box('myselect', $data);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect" id="select_box_1" class="' . $def_class . '"><option value="1">red</option><option value="2">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->select_box('myselect2', $data);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect2" id="select_box_2" class="' . $def_class . '"><option value="1">red</option><option value="2">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->select_box(['name' => 'myselect3', 'data-unittest' => 'testval'], $data);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect3" id="select_box_3" class="' . $def_class . '" data-unittest="testval"><option value="1">red</option><option value="2">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->select_box([
            'name' => 'myselect3',
            'data-unittest' => 'testval',
            'values' => $data,
            'disabled' => 1,
        ]);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect3" id="select_box_4" class="' . $def_class . '" disabled="disabled" data-unittest="testval"><option value="1">red</option><option value="2">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->select_box([
            'name' => 'myselect3',
            'data-unittest' => 'testval',
            'values' => $data,
            'disabled' => 1,
            'selected' => 2,
        ]);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect3" id="select_box_5" class="' . $def_class . '" disabled="disabled" data-unittest="testval"><option value="1">red</option><option value="2" selected="selected">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->select_box([
            'name' => 'myselect3',
            'data-unittest' => 'testval',
            'values' => $data,
            'disabled' => 1,
            'selected' => 2,
            'style' => 'color:red;',
            'class' => 'myclass',
            'add_str' => 'onclick="alert(\'Hello\')"',
            'show_text' => 1,
        ]);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect3" id="select_box_6" class="myclass" style="color:red;" disabled="disabled" data-unittest="testval" onclick="alert(\'Hello\')">' .
            '<option value="" class="' . $def_opt_class . '">- Select myselect3 -</option><option value="1">red</option><option value="2" selected="selected">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->select_box([
            'name' => 'myselect3',
            'data-unittest' => 'testval',
            'values' => ['sub1' => $data],
            'disabled' => 1,
            'selected' => 2,
            'style' => 'color:red;',
            'class_add' => 'myclass',
            'add_str' => 'onclick="alert(\'Hello\')"',
            'show_text' => 1,
        ]);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect3" id="select_box_7" class="' . $def_class . ' myclass" style="color:red;" disabled="disabled" data-unittest="testval" onclick="alert(\'Hello\')">' .
            '<option value="" class="' . $def_opt_class . '">- Select myselect3 -</option><optgroup label="sub1" title="sub1"><option value="1">red</option><option value="2" selected="selected">green</option></optgroup></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->select_box([
            'name' => 'myselect3',
            'values' => $data,
            'show_text' => 'my default text',
        ]);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect3" id="select_box_8" class="' . $def_class . '"><option value="" class="' . $def_opt_class . '">my default text</option><option value="1">red</option><option value="2">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
    }
    public function test_multi_select()
    {
        $html = html();
        $def_class = $html->CLASS_SELECT_BOX;
        $def_opt_class = $html->CLASS_SELECT_OPTION_DEFAULT;
        $html->_ids = [];

        $this->assertEmpty($html->multi_select('', []));

        $data = [
            1 => 'red',
            2 => 'green',
        ];

        $html->_ids = [];
        $html->AUTO_ASSIGN_IDS = false;

        $str = $html->multi_select('', $data);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select class="' . $def_class . '" multiple="multiple"><option value="1">red</option><option value="2">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->multi_select('myselect', $data);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect[]" class="' . $def_class . '" multiple="multiple"><option value="1">red</option><option value="2">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->multi_select('myselect2', $data);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect2[]" class="' . $def_class . '" multiple="multiple"><option value="1">red</option><option value="2">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );

        $html->_ids = [];
        $html->AUTO_ASSIGN_IDS = true;

        $str = $html->multi_select('myselect', $data);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect[]" id="multi_select_1" class="' . $def_class . '" multiple="multiple"><option value="1">red</option><option value="2">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->multi_select('myselect2', $data);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect2[]" id="multi_select_2" class="' . $def_class . '" multiple="multiple"><option value="1">red</option><option value="2">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->multi_select(['name' => 'myselect3', 'data-unittest' => 'testval'], $data);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect3[]" id="multi_select_3" class="' . $def_class . '" multiple="multiple" data-unittest="testval"><option value="1">red</option><option value="2">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->multi_select(['name' => 'myselect3', 'data-unittest' => 'testval', 'values' => $data]);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect3[]" id="multi_select_4" class="' . $def_class . '" multiple="multiple" data-unittest="testval"><option value="1">red</option><option value="2">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->multi_select(['name' => 'myselect3', 'data-unittest' => 'testval', 'values' => $data, 'disabled' => 1]);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect3[]" id="multi_select_5" class="' . $def_class . '" multiple="multiple" disabled="disabled" data-unittest="testval"><option value="1">red</option>' .
            '<option value="2">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->multi_select(['name' => 'myselect3', 'data-unittest' => 'testval', 'values' => $data, 'disabled' => 1, 'selected' => 2]);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect3[]" id="multi_select_6" class="' . $def_class . '" multiple="multiple" disabled="disabled" data-unittest="testval"><option value="1">red</option>' .
            '<option value="2" selected="selected">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->multi_select(['name' => 'myselect3', 'data-unittest' => 'testval', 'values' => $data, 'disabled' => 1, 'selected' => [1 => 1, 2 => 2]]);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect3[]" id="multi_select_7" class="' . $def_class . '" multiple="multiple" disabled="disabled" data-unittest="testval"><option value="1" selected="selected">red</option>' .
            '<option value="2" selected="selected">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->multi_select([
            'name' => 'myselect3',
            'data-unittest' => 'testval',
            'values' => ['sub1' => $data],
            'disabled' => 1,
            'selected' => 2,
            'style' => 'color:red;',
            'class' => 'myclass',
            'add_str' => 'onclick="alert(\'Hello\')"',
            'show_text' => 1,
        ]);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect3[]" id="multi_select_8" class="myclass" style="color:red;" multiple="multiple" disabled="disabled" data-unittest="testval" onclick="alert(\'Hello\')">' .
            '<option value="" class="' . $def_opt_class . '">- Select myselect3 -</option><optgroup label="sub1" title="sub1"><option value="1">red</option><option value="2" selected="selected">green</option></optgroup></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->multi_select([
            'name' => 'myselect3',
            'values' => ['sub1' => $data],
            'selected' => 2,
            'class_add' => 'myclass',
            'show_text' => 1,
        ]);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect3[]" id="multi_select_9" class="' . $def_class . ' myclass" multiple="multiple">' .
            '<option value="" class="' . $def_opt_class . '">- Select myselect3 -</option><optgroup label="sub1" title="sub1"><option value="1">red</option><option value="2" selected="selected">green</option></optgroup></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
        $str = $html->multi_select([
            'name' => 'myselect3',
            'values' => $data,
            'show_text' => 'my default text',
        ]);
        $this->assertEquals(
            str_replace(
            PHP_EOL,
            '',
            '<select name="myselect3[]" id="multi_select_10" class="' . $def_class . '" multiple="multiple"><option value="" class="' . $def_opt_class . '">my default text</option><option value="1">red</option><option value="2">green</option></select>'
            ),
            str_replace(PHP_EOL, '', trim($str))
        );
    }
    public function test_check_box()
    {
        $html = html();
        $def_class = $html->CLASS_LABEL_CHECKBOX . ' ' . $html->CLASS_LABEL_CHECKBOX_INLINE;

        $html->_ids = [];
        $html->AUTO_ASSIGN_IDS = false;

        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="checkbox" value="1"> &nbsp;<span>Checkbox</span></label>', $html->check_box());
        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="checkbox" value="1"> &nbsp;<span>Checkbox</span></label>', $html->check_box('', ''));
        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="test" value="1"> &nbsp;<span>Test</span></label>', $html->check_box('test'));
        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="test" value="true"> &nbsp;<span>Test</span></label>', $html->check_box('test', 'true'));
        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="checkbox" value="true"> &nbsp;<span>Checkbox</span></label>', $html->check_box('', 'true'));
        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="test" value="1"> &nbsp;<span>Test</span></label>', $html->check_box([
            'name' => 'test',
        ]));
        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="test" value="true"> &nbsp;<span>Test</span></label>', $html->check_box([
            'name' => 'test',
            'value' => 'true',
        ]));
        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="test" id="myid" value="true"> &nbsp;<span>Test</span></label>', $html->check_box([
            'name' => 'test',
            'value' => 'true',
            'id' => 'myid',
        ]));
        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="test" id="myid" value="true"> &nbsp;<span></span></label>', $html->check_box([
            'name' => 'test',
            'value' => 'true',
            'id' => 'myid',
            'desc' => '',
        ]));
        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="test" id="myid" value="true"> &nbsp;<span>My desc</span></label>', $html->check_box([
            'name' => 'test',
            'value' => 'true',
            'id' => 'myid',
            'desc' => 'My desc',
        ]));
        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="test" id="myid" value="true"> &nbsp;<span></span></label>', $html->check_box([
            'name' => 'test',
            'value' => 'true',
            'id' => 'myid',
            'desc' => '',
        ]));
        $this->assertEquals('<label><input type="checkbox" name="test" id="myid" value="true"> &nbsp;<span>My desc</span></label>', $html->check_box([
            'name' => 'test',
            'value' => 'true',
            'id' => 'myid',
            'desc' => 'My desc',
            'class_label_checkbox' => '',
        ]));
        $this->assertEquals('<label class="testme"><input type="checkbox" name="test" id="myid" value="true"> &nbsp;<span>My desc</span></label>', $html->check_box([
            'name' => 'test',
            'value' => 'true',
            'id' => 'myid',
            'desc' => 'My desc',
            'class_label_checkbox' => 'testme',
        ]));
        $this->assertEquals('<label class="testme"><input type="checkbox" name="test" id="myid" value="true"> &nbsp;<span>My desc</span></label>', $html->check_box([
            'name' => 'test',
            'value' => 'true',
            'id' => 'myid',
            'desc' => 'My desc',
            'label_extra' => ['class' => 'testme'],
        ]));
        $this->assertEquals('<label class="' . $def_class . ' testme"><input type="checkbox" name="test" id="myid" value="true"> &nbsp;<span>My desc</span></label>', $html->check_box([
            'name' => 'test',
            'value' => 'true',
            'id' => 'myid',
            'desc' => 'My desc',
            'class_add_label_checkbox' => 'testme',
        ]));
        $this->assertEquals('<label class="' . $def_class . ' active"><input type="checkbox" name="checkbox" value="1" checked="checked"> &nbsp;<span>Checkbox</span></label>', $html->check_box([
            'selected' => true,
        ]));
        $this->assertEquals('<label class="' . $def_class . ' active"><input type="checkbox" name="checkbox" value="1" checked="checked"> &nbsp;<span>Checkbox</span></label>', $html->check_box([
            'checked' => true,
        ]));
        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="checkbox" value="1"> &nbsp;<span>Checkbox</span></label>', $html->check_box([
            'selected' => false,
        ]));
        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="checkbox" value="1"> &nbsp;<span>Checkbox</span></label>', $html->check_box([
            'checked' => false,
        ]));
        $this->assertEquals(
            '<label class="' . $def_class . ' testme active"><input type="checkbox" name="test" id="myid" value="true" checked="checked"> &nbsp;<span>My desc</span></label>',
            $html->check_box([
            'name' => 'test',
            'value' => 'true',
            'id' => 'myid',
            'desc' => 'My desc',
            'class_add_label_checkbox' => 'testme',
            'selected' => true,
        ])
        );
        $this->assertEquals(
            '<label class="' . $def_class . ' testme active"><input type="checkbox" name="test" id="myid" value="true" checked="checked" style="color:red;"> &nbsp;<span>My desc</span></label>',
            $html->check_box([
            'name' => 'test',
            'value' => 'true',
            'id' => 'myid',
            'desc' => 'My desc',
            'class_add_label_checkbox' => 'testme',
            'selected' => true,
            'style' => 'color:red;',
        ])
        );

        $html->_ids = [];
        $html->AUTO_ASSIGN_IDS = true;

        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="checkbox" id="check_box_1" value="1"> &nbsp;<span>Checkbox</span></label>', $html->check_box());
        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="checkbox" id="check_box_2" value="1"> &nbsp;<span>Checkbox</span></label>', $html->check_box('', ''));
        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="test" id="check_box_3" value="1"> &nbsp;<span>Test</span></label>', $html->check_box('test'));
        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="test" id="check_box_4" value="true"> &nbsp;<span>Test</span></label>', $html->check_box('test', 'true'));
        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="checkbox" id="check_box_5" value="true"> &nbsp;<span>Checkbox</span></label>', $html->check_box('', 'true'));
        $this->assertEquals('<label class="' . $def_class . '"><input type="checkbox" name="test" id="check_box_6" value="1"> &nbsp;<span>Test</span></label>', $html->check_box([
            'name' => 'test',
        ]));
        $this->assertEquals(
            '<label class="' . $def_class . ' testme active"><input type="checkbox" name="test" id="myid" value="true" checked="checked" style="color:red;"> &nbsp;<span>My desc</span></label>',
            $html->check_box([
            'name' => 'test',
            'value' => 'true',
            'id' => 'myid',
            'desc' => 'My desc',
            'class_add_label_checkbox' => 'testme',
            'selected' => true,
            'style' => 'color:red;',
        ])
        );
    }
    public function test_multi_check_box()
    {
        $html = html();
        $def_class = $html->CLASS_LABEL_CHECKBOX . ' ' . $html->CLASS_LABEL_CHECKBOX_INLINE;

        $html->_ids = [];
        $html->AUTO_ASSIGN_IDS = false;
        $data = [
            1 => 'red',
            2 => 'green',
        ];

        $this->assertEmpty($html->multi_check_box(''));
        $this->assertEmpty($html->multi_check_box('test'));
        $this->assertEquals(str_replace(
            PHP_EOL,
            '',
            '<label class="' . $def_class . '"><input type="checkbox" name="test_1" id="multi_check_box_1" value="1"> &nbsp;<span>red</span></label>' .
            '<label class="' . $def_class . '"><input type="checkbox" name="test_2" id="multi_check_box_2" value="2"> &nbsp;<span>green</span></label>'
            ), str_replace(PHP_EOL, '', trim($html->multi_check_box('test', $data))));
    }
    public function test_radio_box()
    {
        $html = html();
        $def_class = $html->CLASS_LABEL_RADIO . ' ' . $html->CLASS_LABEL_RADIO_INLINE;

        $html->_ids = [];
        $html->AUTO_ASSIGN_IDS = false;
        $data = [
            1 => 'red',
            2 => 'green',
        ];

        $this->assertEmpty($html->radio_box(''));
        $this->assertEmpty($html->radio_box('test'));
        $this->assertEquals(str_replace(
            PHP_EOL,
            '',
            '<label class="' . $def_class . '"><input type="radio" name="test" id="radio_box_1_1" value="1"><span>red</span></label>' .
            '<label class="' . $def_class . '"><input type="radio" name="test" id="radio_box_1_2" value="2"><span>green</span></label>'
            ), str_replace(PHP_EOL, '', trim($html->radio_box('test', $data))));
    }
}
