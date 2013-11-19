<?php

class yf_manage_shop_import {

	/**
	*/
	function import_xls() {
		set_time_limit(0);
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

				$cellIterator = $row->getCellIterator();
				$cellIterator->setIterateOnlyExistingCells(false); // Loop all cells, even if it is not set
				$i = 0;
				$item = array();
				foreach ($cellIterator as $cell) {
					$value = $cell->getCalculatedValue();
					if ($value != '') $item[] = $value;
					$i++;
				}
				$items[] = $item;
			}
			
//			return $this->process_items_epicentr($items); // DONE
//			return $this->process_items_fortuna($items); // DONE
			
			return $this->process_items_talisman($items);
		}
		return 'no rows to process';
	}
	
	
	function process_items_fortuna($items) {
		
		$supplier_id = 99;
		
		db()->query("DELETE FROM `".db('shop_products')."` WHERE `supplier_id`=99");
		
		$remap = array (
			62486 => 'все для самокруток',
			62487 => 'аксессуары для курения',
			62488 => 'сигарные аксессуары',
			62489 => 'трубки и аксессуары',
			62490 => 'кальяны и все для них',
			62491 => 'сигары',
			62492 => 'нюхательные табаки',
			62493 => '',
		);
		
		$start_index = 4;
		$i = 0;
		$cats_list = array();
		
		foreach ($items as $item) {
			if ($i<=$start_index) { $i++;continue; }
			
			$cat_id = 0;
			foreach ($remap as $k=>$v)
				if ($v == $item[5])
					$cat_id = $k;

			$v = array(
				'name' => trim($item[1]),
				'articul' => $item[0],
				'cat_id' => $cat_id,
				'price' => number_format($item[4], 2, '.', ''),
				'price_raw' => number_format($item[3], 2, '.', ''),
				'supplier_id' => $supplier_id, 
				'url' => common()->_propose_url_from_name(trim($item[1])),				
				'active' => 1,
			);
				
			$result .= "articul: ".$v['articul']."; product: ".$v['name']." - ";
			
			$result .= 'new - ';
			$error = false;
			db()->insert(db('shop_products'), $v) or $error = true;
			if ($error) {
				$result .= 'ERROR';
			} else {
				$result .= 'OK';				
			}
			$result .= '<br />';
			$i++;
		}
		return $result;
	}
	
	
	function process_items_epicentr($items) {
		
		$supplier_id = 101;
		
		db()->query("DELETE FROM `".db('shop_products')."` WHERE `supplier_id`=".$supplier_id);
		
		$start_index = 3;
		$i = 0;
		$cats_list = array();
		
		foreach ($items as $item) {
			
			if ($i<=$start_index) { $i++;continue; }
			
			$v = array(
				'name' => trim($item[1]),
				'articul' => $item[2],
				'cat_id' => 1,
				'price' => number_format($item[3], 2, '.', ''),
				'supplier_id' => $supplier_id, 
				'url' => common()->_propose_url_from_name(trim($item[1])),
				'active' => 1,
			);
				
			$result .= "articul: ".$v['articul']."; product: ".$v['name']." - ";
			
			$result .= 'new - ';
			$error = false;
			db()->insert(db('shop_products'), $v) or $error = true;
			if ($error) {
				$result .= 'ERROR';
			} else {
				$result .= 'OK';				
			}
			$result .= '<br />';
			$i++;
		} 
		return $result;
	}
	
	function process_items_talisman($items) {
		
		$supplier_id = 100;

		db()->query("DELETE FROM `".db('shop_products')."` WHERE `supplier_id`=".$supplier_id);
				
		$remap = array (
			62494 => 'Вермут',
			62495 => 'Віскі ; Виски ; Наб.Віскі',
			62496 => 'Горілка ; Водка',
			62497 => 'Джин',
			62498 => 'Ігристе ; Шампанское;  Шам.брют ; Шамп ; Шампан ; Шампанське',
			62499 => 'Кашаца',
			62501 => 'Коньяк ; Наб.Коньяк ; Коньяк',
			62502 => 'Ликер ; Лікер ; Крем-Лікер ; Крем-лікер',
			62503 => 'Ром',
			62504 => 'Текіла ; Текила',
			62505 => 'Бальзам',
			62506 => 'Метаха',
			62507 => 'Арманьяк',
			62508 => 'Бренді ; Бренди',
			62509 => 'Вино ; Винокріп.лікерне',
			62510 => 'Граппа',
			62511 => 'Кальвадоc ; Кальвадос',
			62512 => 'Пиво',
			62513 => 'Сидр',
			62514 => 'Саке',
			62515 => 'Настойка',
			62517 => 'Абсент',
			62518 => 'Бурбон',
			62519 => 'Самбука',
			
			62520 => 'Другое',
		);

		
		foreach ($items as $item) {
			if (count($item)!=4) continue;
			
			$cat_id = 62520;
			foreach ($remap as $k=>$v) {
				$data = explode(" ; ",$v);
				foreach ($data as $v1) {
					if (substr(trim($item[0]),0,strlen($v1)) == $v1) $cat_id = $k;
				}
				if ($v == $item[5])
					$cat_id = $k;
			}

			$v = array(
				'name' => trim($item[0]),
				'articul' => $item[1],
				'cat_id' => $cat_id,
				'price' => number_format($item[2], 2, '.', ''),
				'url' => common()->_propose_url_from_name(trim($item[0])),				
				'supplier_id' => $supplier_id, 
				'active' => 1,
			);
				
			$result .= "articul: ".$v['articul']."; product: ".$v['name']." - ";
			
			$result .= 'new - ';
			$error = false;
			db()->insert(db('shop_products'), $v) or $error = true;
			if ($error) {
				$result .= 'ERROR';
			} else {
				$result .= 'OK';				
			}
			$result .= '<br />';
			$i++;
		}
 
		return $result;
	}
	
}
