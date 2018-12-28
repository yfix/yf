<?php

class yf_manage_conf
{
    const table = 'conf';


    public function _init()
    {
        $this->_table = [
            'table' => self::table,
            'fields' => [
                'name',
                'value',
                'desc',
            ],
        ];
    }


    public function show()
    {
        return table('SELECT * FROM ' . db(self::table) . ' ORDER BY `name` ASC')
            ->text('name', '', ['badge' => 'info'])
            ->text('value')
            ->text('desc', 'Description')
            ->btn_edit()
            ->btn_delete()
            ->btn_active()
            ->footer_add();
    }


    public function add()
    {
        $replace = _class('admin_methods')->add($this->_table);
        return form($replace)
            ->text('name')
            ->text('value')
            ->textarea('desc')
            ->text('linked_table')
            ->text('linked_data')
            ->text('linked_method')
            ->save_and_back();
    }


    public function edit()
    {
        $replace = _class('admin_methods')->edit($this->_table);
        $data = [];
        if ($replace['linked_data']) {
            $data = main()->get_data($replace['linked_data']);
        } elseif ($replace['linked_table']) {
            $q = db()->query('SELECT id, name FROM `' . db($replace['linked_table']) . '` ORDER BY name ASC');
            while ($a = db()->fetch_assoc($q)) {
                $data[$a['id']] = $a['name'];
            }
        } elseif ($replace['linked_method']) {
            list($module, $method) = explode('.', trim($replace['linked_method']));
            $module_obj = module($module);
            if (method_exists($module_obj, $method)) {
                $data = $module_obj->$method();
            }
        }
        $form = form($replace);
        $form->info('name');
        if ($data) {
            $form->select_box('value', $data);
        } else {
            $form->text('value');
        }
        $form->textarea('desc');
        $form->save_and_back();
        return $form;
    }


    public function delete()
    {
        return _class('admin_methods')->delete($this->_table);
    }


    public function active()
    {
        return _class('admin_methods')->active($this->_table);
    }


    public function clone_item()
    {
        return _class('admin_methods')->clone_item($this->_table);
    }


    public function sortable()
    {
        return _class('admin_methods')->sortable($this->_table);
    }
}
