<?php

class sample_html
{
    /***/
    public function _init()
    {
        _class('core_api')->add_syntax_highlighter();
    }

    /***/
    public function _hook_side_column($only_data = false)
    {
        $items = [];
        $url = url('/@object');
        $methods = get_class_methods(_class('html'));
        $sample_methods = get_class_methods($this);
        sort($methods);
        foreach ((array) $sample_methods as $name) {
            if (in_array($name, $methods)) {
                continue;
            }
            $methods[] = $name;
        }
        foreach ((array) $methods as $name) {
            if ($name == 'show' || substr($name, 0, 1) == '_') {
                continue;
            }
            $items[] = [
                'name' => $name . ( ! in_array($name, $sample_methods) ? ' <sup class="text-error text-danger"><small>TODO</small></sup>' : ''),
                'link' => url('/@object/@action/' . $name), // '#head_'.$name,
            ];
        }
        return $only_data ? $items : _class('html')->navlist($items);
    }

    /***/
    public function show()
    {
        return _class('docs')->_show_for($this);
    }

    /***/
    public function complex_test()
    {
        return _class('html')->tabs([
            'thumbnails' => $this->thumbnails(),
            'media_objects' => $this->media_objects(),
            'carousel' => $this->carousel(),
            'menu' => '<div style="min-height:200px;">' . $this->menu() . '</div>',
            '2trees' => '<div class="span4">' . $this->tree() . '</div>' . '<div class="span4">' . $this->tree() . '</div>',
            'dd_table' => $this->dd_table(),
            'accordion' => _class('html')->accordion([
                'modal' => $this->modal(),
                'navbar' => $this->navbar(),
                'breadcrumbs' => $this->breadcrumbs(),
                'pagination' => $this->pagination(),
            ], ['class_head' => 'alert-error']),
//			'table'			=> _class('form2_stacked_sample', YF_PATH.'.dev/tests/form2/')->show(),
//			'form'			=> _class('table2_new_controls', YF_PATH.'.dev/tests/table2/')->show(),
        ]);
    }

    /***/
    public function dd_table()
    {
        $data = [
            'first' => 'first text',
            'second' => 'second text',
            'third' => 'Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. 
				Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. 
				Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. 
				Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably havent heard of them accusamus labore sustainable VHS.',
            'fourth' => '44444',
        ];
        return _class('html')->dd_table($data, []);
    }

    /***/
    public function simple_table()
    {
        $data = [
            'first key' => 'first text',
            'second key' => 'second text',
            'third key' => 'Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. 
				Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. 
				Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. 
				Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably havent heard of them accusamus labore sustainable VHS.',
            'fourth key' => '44444',
        ];
        return _class('html')->simple_table($data, [
            'key' => [
                'func' => function ($in) {
                    return '<b>' . $in . '</b>';
                },
                'extra' => [
                    'width' => '20%',
                ],
            ],
            'tr' => function ($row, $id) {
                return $id === 2 ? ['class' => 'success'] : '';
            },
            'td' => function ($row, $name, $row_id) {
                return $row_id === 1 && $name == 'key' ? ['class' => 'info'] : '';
            },
        ]);
    }

    /***/
    public function accordion()
    {
        $data = [
            'first' => [
                'body' => 'first accordion item body<br />' . PHP_EOL . 'first accordion item body',
                'class_group' => 'panel-info',
                'class_head' => 'alert-info',
            ],
            'second' => [
                'body' => 'second accordion item body<br />' . PHP_EOL . 'second accordion item body',
                'class_group' => 'panel-danger',
                'class_head' => 'alert-error',
            ],
            'third' => 'Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. 
				Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. 
				Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. 
				Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably havent heard of them accusamus labore sustainable VHS.',
            'fourth' => '44444 accordion item body<br />' . PHP_EOL . 'second accordion item body',
        ];
        return _class('html')->accordion($data, ['selected' => 'third', 'class' => 'span4 col-md-4']);
    }

    /***/
    public function tabs()
    {
        $data = [
            'first' => 'first text',
            'second' => 'second text',
            'third' => 'Anim pariatur cliche reprehenderit, enim eiusmod high life accusamus terry richardson ad squid. 3 wolf moon officia aute, non cupidatat skateboard dolor brunch. 
				Food truck quinoa nesciunt laborum eiusmod. Brunch 3 wolf moon tempor, sunt aliqua put a bird on it squid single-origin coffee nulla assumenda shoreditch et. 
				Nihil anim keffiyeh helvetica, craft beer labore wes anderson cred nesciunt sapiente ea proident. Ad vegan excepteur butcher vice lomo. 
				Leggings occaecat craft beer farm-to-table, raw denim aesthetic synth nesciunt you probably havent heard of them accusamus labore sustainable VHS.',
            'fourth' => '44444',
        ];
        return _class('html')->tabs($data, ['selected' => 'third']);
    }

    /***/
    public function modal()
    {
        return _class('html')->modal([
            'inline' => 1,
            'show_close' => 1,
            'header' => 'Modal header',
            'body' => '<p>Some body</p>',
            'footer' => form_item()->save(),
        ]);
    }

    /***/
    public function carousel()
    {
        css('.carousel { max-width: 870px; }');
        return _class('html')->carousel([
            [
                'img' => '//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-01.jpg',
                'desc' => '<h4>First Thumbnail label</h4><p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>',
            ],
            [
                'img' => '//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-02.jpg',
                'desc' => '<h4>Second Thumbnail label</h4><p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>',
            ],
            [
                'img' => '//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-03.jpg',
                'desc' => '<h4>Third Thumbnail label</h4><p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>',
            ],
            '//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-01.jpg',
            '//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-02.jpg',
            '//twbs.github.io/bootstrap/2.3.2/assets/img/bootstrap-mdo-sfmoma-03.jpg',
        ]);
    }

    /***/
    public function navbar()
    {
        return _class('html')->navbar([
            'brand' => [
                'link' => url('/'),
                'name' => 'Title',
            ],
            [
                'link' => url('/home'),
                'name' => 'Home',
            ],
            [
                'link' => url('/link1'),
                'name' => 'Link1',
            ],
            [
                'link' => url('/link2'),
                'name' => 'Link2',
            ],
        ]);
    }

    /***/
    public function navlist()
    {
        return _class('html')->navlist([
            'brand' => [
                'link' => url('/'),
                'name' => 'Title',
            ],
            [
                'link' => url('/home'),
                'name' => 'Home',
            ],
            [
                'link' => url('/link1'),
                'name' => 'Link1',
            ],
            [
                'link' => url('/link2'),
                'name' => 'Link2',
            ],
        ]);
    }

    /***/
    public function breadcrumbs()
    {
        return _class('html')->breadcrumbs([
            [
                'link' => url('/home'),
                'name' => 'Home',
            ],
            [
                'link' => url('/library'),
                'name' => 'Library',
            ],
            [
                'name' => 'Data',
            ],
        ]);
    }

    /***/
    public function alert()
    {
        return _class('html')->alert([
            'head' => 'Oh snap! You got an error!',
            'body' => '<p>Change this and that and try again. Duis mollis, est non commodo luctus, nisi erat porttitor ligula, eget lacinia odio sem nec elit. Cras mattis consectetur purus sit amet fermentum.</p>
				<p><a class="btn btn-danger btn-default" href="#">Take this action</a> <a class="btn btn-default" href="#">Or do this</a></p>',
        ])
        . _class('html')->alert([
            'alert' => 'info',
            'head' => 'Oh snap! You got an error!',
            'body' => '<p>Change this and that and try again. Duis mollis, est non commodo luctus, nisi erat porttitor ligula, eget lacinia odio sem nec elit. Cras mattis consectetur purus sit amet fermentum.</p>
				<p><a class="btn btn-danger btn-default" href="#">Take this action</a> <a class="btn btn-default" href="#">Or do this</a></p>',
        ]);
    }

    /***/
    public function thumbnails()
    {
        //		$img = module('dynamic')->placeholder_img(array('width' => 300, 'height' => 200));
        $img = url('/dynamic/placeholder/300x200');

        return _class('html')->thumbnails([
            [
                'img' => $img,
            ],
            [
                'img' => $img,
                'head' => 'Thumbnail label 4',
            ],
            [
                'img' => $img,
                'alt' => '300x200',
                'head' => 'Thumbnail label 1',
                'body' => '<p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>
					<p><a href="#" class="btn btn-primary">Action</a> <a href="#" class="btn">Action</a></p>',
            ],
            $img,
            [
                'img' => $img,
                'alt' => '300x200',
                'head' => 'Thumbnail label 2',
                'body' => '<p>Cras justo odio, dapibus ac facilisis in, egestas eget quam. Donec id elit non mi porta gravida at eget metus. Nullam id dolor id nibh ultricies vehicula ut id elit.</p>',
            ],
            $img,
        ], ['columns' => 3]);
    }

    /***/
    public function progress_bar()
    {
        return _class('html')->progress_bar([
            '35',
            [
                'val' => '20',
                'type' => 'warning',
            ],
            [
                'val' => '10',
                'type' => 'info',
            ],
        ], ['type' => 'success']);
    }

    /***/
    public function pagination()
    {
        return _class('html')->pagination([
            '1' => url('/some/action/1'),
            '2' => url('/some/action/2'),
            'prev' => url('/some/action/1'),
            'next' => url('/some/action/2'),
            '3' => url('/some/action/3'),
            '4' => url('/some/action/4'),
            '5' => url('/some/action/5'),
        ]);
    }

    /***/
    public function panel()
    {
        return _class('html')->panel(['title' => 'Panel title', 'body' => 'Panel content']);
    }

    /***/
    public function jumbotron()
    {
        return _class('html')->jumbotron([
            'head' => 'My big header',
            'body' => '<p>This is a simple hero unit, a simple jumbotron-style component for calling extra attention to featured content or information.</p>
				<p><a class="btn btn-primary btn-lg" role="button">Learn more</a></p>',
        ]);
    }

    /***/
    public function well()
    {
        return _class('html')->well('Large well content');
    }

    /***/
    public function list_group()
    {
        return _class('html')->list_group([
            'First line',
            [
                'body' => 'Cras justo odio',
                'badge' => '14',
            ],
            [
                'body' => 'Dapibus ac facilisis in',
                'badge' => '2',
                'class_item' => 'active',
            ],
            [
                'body' => 'Morbi leo risus',
                'badge' => '1',
                'class_item' => 'list-group-item-warning',
            ],
        ]);
    }

    /***/
    public function media_objects()
    {
        //		$img = module('dynamic')->placeholder_img(array('width' => 300, 'height' => 200));
        $img = url('/dynamic/placeholder/64x64');
        $body = 'Cras sit amet nibh libero, in gravida nulla. Nulla vel metus scelerisque ante sollicitudin commodo. Cras purus odio, vestibulum in vulputate at, tempus viverra turpis. Fusce condimentum nunc ac nisi vulputate fringilla. Donec lacinia congue felis in faucibus.';
        $date = date('Y-m-d H:i:s');

        return _class('html')->media_objects([
            11 => [
                'link' => url('/comments/view/11'),
                'img' => $img,
                'alt' => '64x64',
                'head' => 'Comment 1',
                'body' => $body,
                'date' => $date,
            ],
            22 => [
                'link' => url('/comments/view/22'),
                'img' => $img,
                'alt' => '64x64',
                'head' => 'Comment 2',
                'body' => $body,
                'date' => $date,
            ],
            33 => [
                'link' => url('/comments/view/33'),
                'img' => $img,
                'alt' => '64x64',
                'head' => 'Comment 3',
                'body' => $body,
                'parent_id' => 22,
            ],
            44 => [
                'link' => url('/comments/view/44'),
                'img' => $img,
                'body' => $body,
                'date' => $date,
                'parent_id' => 33,
            ],
            55 => [
                'img' => $img,
                'body' => $body,
                'parent_id' => 44,
            ],
            66 => [
                'link' => url('/comments/view/66'),
                'img' => $img,
                'alt' => '64x64',
                'head' => 'Comment 6',
                'body' => $body,
                'date' => $date,
            ],
        ]);
    }

    /***/
    public function menu()
    {
        return _class('html')->menu([
            11 => [
                'name' => 'Tools',
            ],
            22 => [
                'link' => url('/blocks'),
                'name' => 'Blocks editor',
                'parent_id' => 11,
            ],
            33 => [
                'link' => url('/file_manager'),
                'name' => 'File manager',
                'parent_id' => 11,
            ],
            44 => [
                'name' => 'Administration',
            ],
            55 => [
                'link' => url('/admin'),
                'name' => 'Admin accounts',
                'parent_id' => 44,
            ],
            66 => [
                'link' => url('/admin_groups'),
                'name' => 'Admin groups',
                'parent_id' => 44,
            ],
            77 => [
                'link' => url('/admin_modules'),
                'name' => 'Admin modules',
                'parent_id' => 44,
            ],
            88 => [
                'name' => 'Users',
                'parent_id' => 44,
            ],
            99 => [
                'link' => url('/manage_users'),
                'name' => 'User accounts',
                'parent_id' => 88,
            ],
            101 => [
                'link' => url('/user_groups'),
                'name' => 'User groups',
                'parent_id' => 88,
            ],
            102 => [
                'link' => url('/user_modules'),
                'name' => 'User modules',
                'parent_id' => 88,
            ],
            103 => [
                'name' => 'Content',
            ],
            104 => [
                'link' => url('/static_pages'),
                'name' => 'Static pages',
                'parent_id' => 103,
            ],
            105 => [
                'link' => url('/manage_news'),
                'name' => 'News',
                'parent_id' => 103,
            ],
            106 => [
                'link' => url('/manage_comments'),
                'name' => 'Comments',
                'parent_id' => 103,
            ],
        ]);
    }

    /***/
    public function tree()
    {
        return _class('html')->tree([
            11 => [
                'name' => 'Tools',
            ],
            22 => [
                'link' => url('/blocks'),
                'name' => 'Blocks editor',
                'parent_id' => 11,
            ],
            33 => [
                'link' => url('/file_manager'),
                'name' => 'File manager',
                'parent_id' => 11,
            ],
            44 => [
                'name' => 'Administration',
            ],
            55 => [
                'link' => url('/admin'),
                'name' => 'Admin accounts',
                'parent_id' => 44,
            ],
            66 => [
                'link' => url('/admin_groups'),
                'name' => 'Admin groups',
                'parent_id' => 44,
            ],
            77 => [
                'link' => url('/admin_modules'),
                'name' => 'Admin modules',
                'parent_id' => 44,
            ],
            88 => [
                'name' => 'Users',
                'parent_id' => 44,
            ],
            99 => [
                'link' => url('/manage_users'),
                'name' => 'User accounts',
                'parent_id' => 88,
            ],
            101 => [
                'link' => url('/user_groups'),
                'name' => 'User groups',
                'parent_id' => 88,
            ],
            102 => [
                'link' => url('/user_modules'),
                'name' => 'User modules',
                'parent_id' => 88,
            ],
            103 => [
                'name' => 'Content',
            ],
            104 => [
                'link' => url('/static_pages'),
                'name' => 'Static pages',
                'parent_id' => 103,
            ],
            105 => [
                'link' => url('/manage_news'),
                'name' => 'News',
                'parent_id' => 103,
            ],
            106 => [
                'link' => url('/manage_comments'),
                'name' => 'Comments',
                'parent_id' => 103,
            ],
        ]);
    }

    /***/
    public function grid()
    {
        return _class('html')->grid([
            [
                ['s1'],
                ['s1'],
                ['s1'],
                ['s1'],
                ['s1'],
                ['s1'],
                ['s1'],
                ['s1', 'class' => 'btn btn-warning'],
                ['s1'],
                ['s1', 'class' => 'btn btn-primary'],
                ['s1'],
                ['s1'],
            ],
            [
                ['s4'],
                ['s4'],
                ['s4'],
            ],
            [
                ['s4', 'col' => 4, 'class' => 'alert alert-error'],
                ['s8', 'col' => 8, 'class' => 'alert alert-info'],
            ],
            [
                ['s6'],
                ['s6'],
            ],
            [
                ['s12'],
            ],
        ]);
    }

    /***/
    public function a()
    {
        return _class('html')->a('/docs/html', 'Block me', 'fa fa-lock');
    }

    /***/
    public function icon()
    {
        return _class('html')->icon('fa fa-lock');
    }

    /***/
    public function ip()
    {
        return _class('html')->ip('8.8.8.8');
    }

    /***/
    public function tooltip()
    {
        return _class('html')->tooltip('This is custom text to be displayed inside tooltip, also you can use tip short names, editable from admin panel');
    }

    /***/
    public function select_box()
    {
        return _class('html')->select_box('input_name', ['k1' => 'key1', 'k2' => 'key2']);
    }

    /***/
    public function multi_select()
    {
        return _class('html')->multi_select_box('input_name', ['k1' => 'key1', 'k2' => 'key2']);
    }

    /***/
    public function multi_select_box()
    {
        return _class('html')->multi_select_box('input_name', ['k1' => 'key1', 'k2' => 'key2']);
    }

    /***/
    public function select2_box()
    {
        return _class('html')->select2_box('input_name', ['k1' => 'key1', 'k2' => 'key2']);
    }

    /***/
    public function chosen_box()
    {
        return _class('html')->chosen_box('input_name', ['k1' => 'key1', 'k2' => 'key2']);
    }

    /***/
    public function button_box()
    {
        return _class('html')->button_box('input_name', ['k1' => 'key1', 'k2' => 'key2']);
    }

    /***/
    public function button_split_box()
    {
        return _class('html')->button_split_box('input_name', ['k1' => 'key1', 'k2' => 'key2']);
    }

    /***/
    public function button_radio_box()
    {
        return _class('html')->button_radio_box('input_name', ['k1' => 'key1', 'k2' => 'key2']);
    }

    /***/
    public function button_yes_no_box()
    {
        return _class('html')->button_yes_no_box('input_name', ['k1' => 'key1', 'k2' => 'key2']);
    }

    /***/
    public function button_check_box()
    {
        return _class('html')->button_check_box('input_name', ['k1' => 'key1', 'k2' => 'key2']);
    }

    /***/
    public function radio_box()
    {
        return _class('html')->radio_box('input_name', ['k1' => 'key1', 'k2' => 'key2']);
    }

    /***/
    public function check_box()
    {
        return _class('html')->check_box('input_name', ['k1' => 'key1', 'k2' => 'key2']);
    }

    /***/
    public function multi_check_box()
    {
        return _class('html')->multi_check_box('input_name', ['k1' => 'key1', 'k2' => 'key2']);
    }

    /***/
    public function list_box()
    {
        return _class('html')->list_box('input_name', ['k1' => 'key1', 'k2' => 'key2']);
    }

    /***/
    public function div_box()
    {
        return _class('html')->div_box('input_name', ['k1' => 'key1', 'k2' => 'key2']);
    }

    /***/
    public function date_box()
    {
        return _class('html')->date_box('input_name');
    }

    /***/
    public function date_box2()
    {
        return _class('html')->date_box2('input_name');
    }

    /***/
    public function time_box()
    {
        return _class('html')->time_box('input_name');
    }

    /***/
    public function time_box2()
    {
        return _class('html')->time_box2('input_name');
    }

    /***/
    public function datetime_box2()
    {
        return _class('html')->datetime_box2('input_name');
    }

    /***/
    public function date_picker()
    {
        return _class('html')->date_picker('input_name', '2015-02-02');
    }

    /***/
    public function input()
    {
        return _class('html')->input('input_name', 'some value');
    }

    /***/
    public function textarea()
    {
        return _class('html')->textarea('input_name', 'some value');
    }

    /***/
    public function li()
    {
        return _class('html')->li([
            'name 1',
            [
                'name' => 'My name 2',
            ],
            [
                'name' => 'My name 3',
                'link' => url('/@object/@action/@id/3'),
            ],
        ]);
    }
}
