<?php

/***
    todo:
    map, geocode
    src: http://novaposhta.ua/map/index/ru
    city: Авдеевка (654)
    map page: /map/citymap?id=654
    map iframe: http://maps.novaposhta.ua/index.php?r=site/map_city&id=654
*/

class yf_manage_shop_novaposhta_ua
{
    private $_class_price = false;

    public function _init()
    {
        $this->_class_price = _class('_shop_price', 'modules/shop/');
        $this->is_post = main()->is_post();
        $this->is_init = (int) main()->_get('init');
    }

    public function novaposhta_ua()
    {
        $result = '';
        if ($this->is_post) {
            $is_import = $_POST['import'] && $_POST['confirm'];
            if ($is_import) {
                $result .= $this->novaposhta_ua__import();
            }
        }
        $form = $this->_form($result);
        return  $form;
    }

    public function _form($data)
    {
        $data_desc = $data ? 'Результат' : '';
        $replace = [];
        $_form = form($replace)
            ->row_start(['desc' => ''])
                ->submit('import', 'Импорт списка отделений')
                ->check_box('confirm', false, ['desc' => 'подтверждение', 'no_label' => true])
            ->row_end()
            ->container($data, $data_desc);
        return  $_form;
    }

    public function _cleanup_city($data)
    {
        if (empty($data)) {
            return  $data;
        }
        $result = preg_replace(['#([^\s])\(#'], ['\1 ('], $data);
        return  $result;
    }

    public function _cleanup_tel($data)
    {
        if (empty($data)) {
            return  $data;
        }
        $filter = [
            '#(\d+)\-(\d+\-\d+\-\d+)#' => '(\1) \2',
            '#\)(\d)#' => ') \1',
            '#\)\-#' => ') ',
            '#^(\d+\))#' => '(\1',
            '#[^\d]+$#' => '',
        ];
        $result = preg_replace(array_keys($filter), array_values($filter), $data);
        return  $result;
    }

    public function _cleanup_address($data)
    {
        $address = null;
        $branch_no = 1;
        $info = null;
        if (empty($data)) {
            return  [$address, $branch_no, $info];
        }
        // info
        if (preg_match_all('#([^\(\)]*)\(([^\)]+)\)([^\(\)]*)#', $data, $match)) {
            $info = implode('; ', array_reverse($match[2]));
            $data = implode('', $match[1]) . implode('', $match[3]);
        }
        // branch_no
        if (preg_match('#^[^\d]+(\d*)\s*:(.*)$#', $data, $match)
            || preg_match('#^[^\d]+N\s*(\d*)\,*(.*)$#', $data, $match)
            || preg_match('#^[^\d]+№\s*(\d*)\s*\,*(.*)$#', $data, $match)) {
            $branch_no = (int) $match[1];
            $branch_no = $branch_no ?: 1;
            $data = $match[2];
        }
        // cleanup
        $data = preg_replace(['#Відділення#'], '', $data);
        $filter = [
            '#\s*Відділення\s*#' => '',
            '#Отделение,\s*#' => '',
            '#Отделение[^\,]\,\s*#' => '',
            '#^\s*:\s*#' => '',
        ];
        // add info
        if (preg_match('#^\s*[:,]\s*([^:]+)\s*[:]\s*(.+)$#', $data, $match)) {
            $info = $match[1] . (empty($info) ? '' : '; ' . $info);
            $data = $match[2];
        }
        $data = preg_replace(array_keys($filter), array_values($filter), $data);
        $address = trim($data);
        $result = [$address, $branch_no, $info];
        return  $result;
    }

    public function novaposhta_ua__import()
    {
        // get data
        // *** local
        // $file = '/tmp/JsonWarehouseList.json';
        // $content = file_get_contents( $file );
        // *** remote
        $url = 'http://novaposhta.ua/shop/office/getJsonWarehouseList/';
        $content = common()->get_remote_page($url);
        $data = json_decode($content, true);
        if (empty($data['response'])) {
            return  'Не найдено данных по адресу: ' . $url;
        }
        $count = 0;
        $sql_data = [];
        foreach ($data['response'] as $i => $item) {
            $count++;
            $city_raw = $item['cityRu'] ?: '';
            $tel_raw = $item['phone'] ?: '';
            $address_raw = $item['addressRu'] ?: '';
            $city = $this->_cleanup_city($city_raw);
            $tel = $this->_cleanup_tel($tel_raw);
            list($address, $branch_no, $info) = $this->_cleanup_address($address_raw);
            $address = trim($address);
            $branch_no = (int) $branch_no;
            $info = trim($info);
            // add city
            $location = empty($city) ? '' : $city . ', ';
            // add branch
            $location .= 'Отделение' . ($branch_no > 0 ? ' №' . $branch_no : '');
            // add ':'
            $location .= empty($address) && empty($info) ? '' : ': ';
            // add address
            $location .= empty($address) ? '' : $address;
            // add info
            $location .= empty($info) ? '' : " ($info)";
            $sql_data[] = _es([
                'city_raw' => $city_raw,
                'address_raw' => $address_raw,
                'tel_raw' => $tel_raw,
                'city' => $city,
                'branch_no' => $branch_no,
                'address' => $address,
                'info' => $info,
                'location' => $location,
                'tel' => $tel,
                'options' => json_encode($item, JSON_NUMERIC_CHECK),
            ]);
        }
        $table_name = db('shop_novaposhta_ua');
        $count_in_db = (int) db()->get_one("SELECT COUNT(*) FROM $table_name");
        $sql_result = db()->insert_on_duplicate_key_update($table_name, $sql_data);
        $table_result = [
            ['title' => 'Обработано: ', 'count' => $count],
            ['title' => 'Добавлено: ', 'count' => $count - $count_in_db],
        ];
        $result = table($table_result, ['no_total' => true])
        ->text('title', 'Операция')
        ->text('count', 'Количество');
        return  $result;
    }

    public function novaposhta_ua__import_old()
    {
        // get data
        // $file = '/tmp/warenhouses_ru.xls';
        // $content = file_get_contents( $file );
        $url = 'http://novaposhta.ua/public/files/xls/warenhouses_ru.xls';
        $content = common()->get_remote_page($url);
        // save to temp file
        $file_path = sys_get_temp_dir() ?: '/tmp';
        $file_name = tempnam($file_path, 'import');
        $file = fopen($file_name, 'w+');
        fwrite($file, $content);
        fclose($file);

        require_php_lib('phpexcel');
        // parse file
        $reader = PHPExcel_IOFactory::createReader('Excel5');
        $reader->setReadDataOnly(true);
        try {
            $excel = $reader->load($file_name);
            // $nullValue = null, $calculateFormulas = true, $formatData = true, $returnCellRef = false
            $data = $excel->getActiveSheet()->toArray(null, false, false, false);
        } catch (Exception $e) {
            $data = null;
        }
        // free memory
        unset($excel, $reader);
        unlink($file_name);
        // prepare data
        if (empty($data)) {
            return  'Не найдено данных по адресу: ' . $url;
        }
        $count = 0;
        $sql_data = [];
        foreach ($data as $r) {
            if ((empty($r[0]) && empty($r[1])) || $r[0] == 'Мiсто') {
                continue;
            }
            $count++;

            $city = $this->_cleanup_city($r[0]);
            $tel = $this->_cleanup_tel($r[2]);
            list($address, $branch_no, $info) = $this->_cleanup_address($r[1]);
            // add city
            $location = empty($city) ? '' : $city . ', ';
            // add branch
            $location .= 'Отделение' . ($branch_no > 0 ? ' №' . $branch_no : '');
            // add ':'
            $location .= empty($address) && empty($info) ? '' : ': ';
            // add address
            $location .= empty($address) ? '' : $address;
            // add info
            $location .= empty($info) ? '' : " ($info)";
            $sql_data[] = [
                'city_raw' => $r[0],
                'address_raw' => $r[1],
                'tel_raw' => $r[2],
                'city' => $city,
                'branch_no' => $branch_no,
                'address' => $address,
                'info' => $info,
                'location' => $location,
                'tel' => $tel,
                'time_in_1' => $r[3],
                'time_in_2' => $r[4],
                'time_out_1' => $r[5],
                'time_out_2' => $r[6],
            ];
        }
        $table_name = db('shop_novaposhta_ua');
        $count_in_db = (int) db()->get_one("SELECT COUNT(*) FROM $table_name");
        db()->insert_on_duplicate_key_update($table_name, _es($sql_data));
        $table_result = [
            ['title' => 'Обработано: ', 'count' => $count],
            ['title' => 'Добавлено: ', 'count' => $count - $count_in_db],
        ];
        $result = table($table_result, ['no_total' => true])
        ->text('title', 'Операция')
        ->text('count', 'Количество');
        return  $result;
    }
}
