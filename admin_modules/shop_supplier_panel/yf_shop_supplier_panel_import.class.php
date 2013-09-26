<?php

class yf_shop_supplier_panel_import {

	/**
	*/
	function import_xls() {
		$SUPPLIER_ID = module('shop_supplier_panel')->SUPPLIER_ID;
		$cat_aliases = db()->get_2d("SELECT name, cat_id FROM `".db('shop_suppliers_cat_aliases')."` WHERE supplier_id=".intval($SUPPLIER_ID));
		
		if (empty($_FILES)) {
			return form('',array('enctype' => 'multipart/form-data'))
				->file("file")
				->save('', "Upload");
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
			$header_items = array();
			foreach ($worksheet->getRowIterator() as $row) {
				$row_number = $row->getRowIndex();
				$i = 0;
				if ($row_number == 1) {
					$cellIterator = $row->getCellIterator();
					$cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
					foreach ($cellIterator as $cell) {
						$value =  $cell->getCalculatedValue();
						if ($value != '') $header[$i] = $value;
						$header_items[$value] = $value;
						$i++;							
					}
				} else {
					if (empty($header)) return 'wrong columns format';
					if ($header_items['name'] == '' || $header_items['articul'] == '') {
						return 'one of required fields are missing (name,articul)';
					}
					$cellIterator = $row->getCellIterator();
					$cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
					$i = 0;
					foreach ($cellIterator as $cell) {
						$value = $cell->getCalculatedValue();
						if ($value != '') $item[$header[$i]] = $value;
						$i++;
					}
					$items[] = $item;
				}
			}
			
			$i = 0;
			$u = 0;
			$result = "";				
			foreach ($items as $v) {
				unset($v['id']);
				unset($v['image']);
				unset($v['update_date']);					
				unset($v['last_viewed_date']);
				unset($v['featured']);
				unset($v['active']);
				unset($v['viewed']);
				unset($v['sold']);
				unset($v['status']);
				$v['add_date'] = time();
				if ($v['category'] != '') {
					// category aliases support
					$v['cat_id'] = $cat_aliases[$v['category']];
					unset($v['category']);
				}
				$v['supplier_id'] = $SUPPLIER_ID;
				$result .= "articul: ".$v['articul']."; product: ".$v['name']." - ";
				if (db()->query_num_rows("SELECT * FROM `".db('shop_products')."` WHERE `articul`='".$v['articul']."' AND `supplier_id`='".$v['supplier_id']."'") != 0) {
					$result .= "update - ";
					$error = false;
					db()->update(db('shop_products'), $v, "`articul`='".$v['articul']."' AND `supplier_id`='".$v['supplier_id']."'") or $error = true;
					if ($error) {
						$result .= 'ERROR';
					} else {
						$result .= 'OK';
						$u++;						
					}
					$result .= '<br />';
				} else {
					$result .= 'new - ';
					$error = false;
					db()->insert(db('shop_products'), $v) or $error = true;
					if ($error) {
						$result .= 'ERROR';
					} else {
						$result .= 'OK';
						$i++;						
					}
					$result .= '<br />';
				}
			}
			$result .= "inserted: ".$i."; updated: ".$u." from ".count($items)."<br />";
			return $result;
		}
		return 'no rows to process';
	}
}
