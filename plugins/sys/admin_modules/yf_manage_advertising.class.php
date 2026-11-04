<?php

class yf_manage_advertising
{
    public function _init()
    {
        module('advertising', 'modules/');
    }


    public function exit_advertising()
    {
        setcookie('advertise', '', time() - 3600, '/');
        return js_redirect('/');
    }


    public function show()
    {
        return  $this->listing();
    }


    public function edit()
    {
        $_GET['id'] = (int) (($_GET['id'] ?? null));
        // Do save data
        if ( ! empty($_POST)) {
            // Position could not be empty
            if (empty($_POST['ad'])) {
                _re('Place is empty');
            }
            // Content html could not be empty
            if (empty($_POST['html'])) {
                _re('Html is empty');
            }
            if ( ! _ee()) {
                return $this->save();
            }
        }
        $info = db()->query_fetch('SELECT * FROM ' . db('advertising') . ' WHERE id=' . ($_GET['id'] ?? null));
        $editor = db()->query_fetch('SELECT * FROM ' . db('sys_admin') . ' WHERE id=' . $info['edit_user_id']);
        $replace = [
            'form_action' => './?object=' . ($_GET['object'] ?? null) . '&action=' . ($_GET['action'] ?? null) . '&id=' . ($_GET['id'] ?? null),
            'ad' => $info['ad'],
            'editor' => $editor['first_name'] . ' ' . $editor['last_name'],
            'edit_date' => date('d/m/Y', $info['edit_date']),
            'customer' => $info['customer'],
            'date_start' => $info['date_start'] ? $info['date_start'] : time(),
            'date_end' => $info['date_end'] ? $info['date_end'] : time(),
            'cur_date' => time(),
            'html' => stripslashes($info['html']),
            'active' => $info['active'],
            'error_message' => _e(),
            'back_link' => './?object=' . ($_GET['object'] ?? null) . '&action=listing',
        ];
        return form2($replace)
            ->info('ad', 'Placeholder')
            ->info('editor', 'Last editor')
            ->info('edit_date', 'Edit date')
            ->text('customer', 'Customer')
            ->text('ad', 'Placeholder')
            ->textarea('html', 'Content')
            ->date_box('date_start', '', ['desc' => 'Date start'])
            ->date_box('date_end', '', ['desc' => 'Date end'])
            ->active_box()
            ->save_and_back();
    }


    public function delete()
    {
        $_GET['id'] = (int) (($_GET['id'] ?? null));
        // Do delete records
        if ( ! empty($_GET['id'])) {
            db()->query('DELETE FROM `' . db('advertising') . '` WHERE `id`=' . ($_GET['id'] ?? null) . ' LIMIT 1');
            common()->admin_wall_add(['advertising deleted: ' . ($_GET['id'] ?? null), ($_GET['id'] ?? null)]);
        }
        $log = [
            'ads_id' => ($_GET['id'] ?? null),
            'author_id' => ($_SESSION['admin_id'] ?? null),
            'action' => 'delete',
            'date' => time(),
        ];
        db()->INSERT('log_ads_changes', $log);
        return js_redirect('./?object=' . ($_GET['object'] ?? null) . '&action=listing');
    }


    public function listing()
    {
        if (($_GET['ad'] ?? null)) {
            $sql = 'SELECT * FROM ' . db('advertising') . ' WHERE ad="' . _es(($_GET['ad'] ?? null)) . '"';
        } else {
            $sql = 'SELECT * FROM ' . db('advertising');
        }
        return table2($sql)
            ->text('id')
            ->text('ad')
            ->func('html', function ($field, $params) {
                return _prepare_html($field);
            }, ['desc' => 'Content'])
            ->date('date_end')
            ->text('customer')
            ->func('edit_user_id', function ($field, $params) {
                $author = db()->query_fetch('SELECT first_name, last_name FROM ' . db('sys_admin') . ' WHERE id =' . $field);
                return $author['first_name'] . ' ' . $author['last_name'];
            }, ['desc' => 'Editor'])
            ->btn_active()
            ->btn_edit()
            ->btn_delete()
            ->footer_link('Exit visual debug mode', './?object=manage_advertising&action=exit_advertising')
            ->footer_link('Add new', './?object=' . ($_GET['object'] ?? null) . '&action=edit')
            ->footer_link('Show all', './?object=' . ($_GET['object'] ?? null) . '&action=listing');
    }


    public function save()
    {
        $_GET['id'] = (int) (($_GET['id'] ?? null));
        $update = [
            'ad' => _es(($_POST['ad'] ?? null)),
            'customer' => _es(($_POST['customer'] ?? null)),
            'date_start' => strtotime(($_POST['date_start']['month'] ?? null) . '/' . ($_POST['date_start']['day'] ?? null) . '/' . ($_POST['date_start']['year'] ?? null)),
            'date_end' => strtotime(($_POST['date_end']['month'] ?? null) . '/' . ($_POST['date_end']['day'] ?? null) . '/' . ($_POST['date_end']['year'] ?? null)),
            'html' => ( ! empty($_POST['html'])) ? _es(($_POST['html'] ?? null)) : '',
            'edit_user_id' => ($_SESSION['admin_id'] ?? null),
            'edit_date' => time(),
            'active' => (int) (($_POST['active'] ?? null)),
        ];
        //Write update data into DB
        if (($_GET['id'] ?? null)) {
            db()->UPDATE('advertising', $update, 'id=' . (int) (($_GET['id'] ?? null)));
        } else {
            $update['add_date'] = time();
            db()->INSERT('advertising', $update);
            $max_id = db()->query_fetch_row('SELECT MAX(id) FROM ' . db('advertising'));
        }
        $log = [
            'ads_id' => ($_GET['id'] ?? null) ? ($_GET['id'] ?? null) : $max_id[0],
            'author_id' => ($_SESSION['admin_id'] ?? null),
            'date' => time(),
            'action' => ($_GET['id'] ?? null) ? 'edit' : 'add',
        ];
        db()->INSERT('log_ads_changes', $log);
        common()->admin_wall_add(['advertising updated: ' . ($_GET['id'] ?? null), ($_GET['id'] ?? null)]);
        // Return user back
        return js_redirect('./?object=' . ($_GET['object'] ?? null) . '&action=listing&ad=' . ($_POST['ad'] ?? null));
    }
}
