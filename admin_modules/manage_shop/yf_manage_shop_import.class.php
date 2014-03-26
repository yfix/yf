<?php

class yf_manage_shop_import {
	
	function _init() {
		$this->_types = array(
			'epicentr' => 'epicentr',
			'talisman' => 'talisman',
			'fortuna' => 'fortuna',
			'zakaz_ua' => 'zakaz_ua [update only]',
		);
		$this->_modes = array(
			'validate' => 'validate',
			'process' => 'process',
		);		
	}

	function _show_form() {
		$form_import_xls = form('',array('enctype' => 'multipart/form-data'))
				->select_box('type', module('manage_shop_import')->_types)
				->select_box('mode', module('manage_shop_import')->_modes)
				->file("file")
				->save('', "Upload");
		
		$form_export_xls = form('',array('action' => "./?object=manage_shop&action=products_xls_export"))
				->select_box('supplier_id', _class('manage_shop')->_suppliers_for_select, array('desc' => 'Supplier', 'no_translate' => 1, 'hide_empty' => 1))
				->save('', "Export");
		$form_export_zakaz = form('',array('action' => "./?object=manage_shop&action=export_zakaz_start"))	
				->save('', "Execute zakaz.ua export script");
		
		return tpl()->parse("manage_shop/import_xls", array(
			'form_import_xls' => $form_import_xls,
			'form_export_xls' => $form_export_xls,
			'form_export_zakaz' => $form_export_zakaz,
		));
	}
	
	function export_zakaz_start() {
		$path = realpath("../../scripts/import/zakaz_ua/");
		if (file_exists($path."/out/lock") && (file_get_contents($path."/out/lock") == 1)) {
			return "Script is already executing.";
		} else {
			$cmd = $path . "/import.php";
			exec("cd {$path}/ && php import.php > /dev/null &");
			return "Script is running now. You will receive an e-mail with xls file when it will be done.";
		}
	}
	
	/**
	*/
	function import_xls() {
		set_time_limit(0);
		$SUPPLIER_ID = module('shop_supplier_panel')->SUPPLIER_ID;
		$cat_aliases = db()->get_2d("SELECT name, cat_id FROM `".db('shop_suppliers_cat_aliases')."` WHERE supplier_id=".intval($SUPPLIER_ID));
		
		if (empty($_FILES['file'])) {
			
			return $this->_show_form();
		}
		if (file_exists(YF_PATH."libs/phpexcel/PHPExcel.php")) {
			require_once(YF_PATH."libs/phpexcel/PHPExcel.php");
		} else {
			require_once(INCLUDE_PATH."libs/phpexcel/PHPExcel.php");
		}
		$ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
		
		$objReader = PHPExcel_IOFactory::createReader($ext == 'xls' ? 'Excel5' : 'Excel2007');
		$objPHPExcel = $objReader->load($_FILES['file']['tmp_name']);
		
		$items = array();
		
		foreach ($objPHPExcel->getWorksheetIterator() as $worksheet) {
			$header = array();
			$title = $worksheet->getTitle();
			$header_items = array();
			foreach ($worksheet->getRowIterator() as $row) {
				$row_number = $row->getRowIndex();

				$cellIterator = $row->getCellIterator();
				$cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
				$i = 0;
				$item = array();
				foreach ($cellIterator as $cell) {
					$value = $cell->getCalculatedValue();
					$item[] = $value;
					$i++;
				}
					
				$items[] = $item;
			}
		
		}
		if (count($items) != 0) {
			$func_name = 'process_items_'.$_POST['type'];
			return $this->$func_name($items,$_POST['mode']);
		} else {
			return 'no rows to process';
		}
	}
	
	function process_items_fortuna($items, $mode = 'process') { // process/validate
		$supplier_id = 99;
		$products = $this->get_products_by_supplier($supplier_id);
		$result = array();
		foreach ($items as $item) {
			if (intval($item[0]) == 0 || intval($item[4]) == 0) continue;
			$v = $this->format_data($item[1],$item[0],$item[4],$supplier_id);
			if (empty($products[$v['articul']])) {
				$v['is_new'] = 'new';
				if ($mode == 'process') db()->insert(db('shop_products'), _es($v));
			} else {
				$v['is_new'] = 'upd';									
				if ($mode == 'process') db()->update(db('shop_products'), _es($v),"`supplier_id`='".$supplier_id."' AND `articul`='".$v['articul']."'");
			}
			$result[] = $this->add_result($v);			
		}
		return $this->table_format($result);
	}
	
	function process_items_epicentr($items, $mode = 'process') { // process/validate
		$supplier_id = 101;
		$products = $this->get_products_by_supplier($supplier_id);
		$result = array();
		foreach ($items as $item) {
			if (intval($item[0]) == 0  || number_format((double)$item[3], 2, '.', '') == 0) continue;
			if ($item[2]!='') {
				$v = $this->format_data($item[1],$item[2],$item[3], $supplier_id);
				if (empty($products[$v['articul']])) {
					$v['is_new'] = 'new';
					if ($mode == 'process') db()->insert(db('shop_products'), _es($v));
				} else {
					$v['is_new'] = 'upd';									
					if ($mode == 'process') db()->update(db('shop_products'), _es($v),"`supplier_id`='".$supplier_id."' AND `articul`='".$v['articul']."'");
				}
				$result[] = $this->add_result($v);
			}
		} 
		return $this->table_format($result);
	}
	
	function process_items_talisman($items, $mode = 'process') { // process/validate
		$supplier_id = 100;
		$products = $this->get_products_by_supplier($supplier_id);
		foreach ($items as $item) {
			if (trim($item[3])=='' || $item[3] == 'Артикул') continue;
			$v = $this->format_data($item[0],$item[3],$item[4],$supplier_id);
			if (empty($products[$v['articul']])) {
				$v['is_new'] = 'new';
				if ($mode == 'process') db()->insert(db('shop_products'), _es($v));
			} else {
				$v['is_new'] = 'upd';									
				if ($mode == 'process') db()->update(db('shop_products'), _es($v),"`supplier_id`='".$supplier_id."' AND `articul`='".$v['articul']."'");
			}
			$result[] = $this->add_result($v);
		}
		return $this->table_format($result);
	}
	
	function process_items_zakaz_ua($items, $mode = 'process') { // process/validate
		$supplier_id = "0,104,105";
		$products = $this->get_products_by_supplier($supplier_id,"`id`,`articul`");
		foreach ($items as $item) {
			if (trim($item[0])=='' || intval($item[0]) == 0) continue;
			$v = $this->format_data($item[1],$item[0],$item[3],$supplier_id);
			if (!isset($products[$v['articul']])) {
				$v['is_new'] = 'new';
				// note: no new items must be passed here(!)
			} else {
				$v['is_new'] = 'upd';									
				if ($mode == 'process') db()->update(db('shop_products'), _es($v),"`supplier_id`='".$supplier_id."' AND `id`='".$v['articul']."'");
			}
			$result[] = $this->add_result($v);
		}
		return $this->table_format($result);
	}
	
	
	function table_format($result) {
		return table($result, array(
            'table_class'       => 'table-condensed',
            'auto_no_buttons'   => 1,
            'pager_records_on_page' => 100000,
			'tr' => function($row,$id) {
				if ($row['is_new'] == 'new') {
					return ' class="success"';
				} else {
					return ' class="warning"';					
				}
			}
		))->auto();		
	}
	
	function get_products_by_supplier($supplier_id, $rows = "`articul`,`id`") {
		return db()->get_2d("SELECT {$rows} FROM `".db('shop_products')."` WHERE `supplier_id` IN ({$supplier_id})");
	}
	
	function add_result($v) {
		return array(
			'articul' => $v['articul'],
			'product' => $v['name'],
			'price' => $v['price'],
			'is_new' => $v['is_new'],
		);
	}
	
	function format_data($name,$articul,$price,$supplier_id) {
		return array(
			'name' => trim($name),
			'articul' => trim($articul),
			'price' => number_format((double)$price, 2, '.', ''),
			'price_raw' => number_format((double)$price, 2, '.', ''),
			'url' => common()->_propose_url_from_name(trim($name)),
			'supplier_id' => $supplier_id,
			'status' => 1,
			'active' => 0,
		);		
	}
}

