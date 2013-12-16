<?php

class yf_manage_shop_import {

	/**
	*/
/*	function import_xml() {
		$supplier_id = 102;
		
		$file_name = "/home/sergey/kupi_import/price.xml";
		$xml = file_get_contents($file_name);
		$p = xml_parser_create();
		xml_parse_into_struct($p, $xml, $vals, $index);
		xml_parser_free($p);
		$cnt = 0;
		foreach ($vals as $v) {
			if (!($v['tag'] == 'PRODUCT' && $v['type'] == 'open')) continue;
			$name = $v['attributes']['NAME'];
			$name = trim(substr($name,strpos($name,' ')));
			if ($v['attributes']['URL']!='') {
				$file = file_get_contents($v['attributes']['URL']);
				if ($file == '') {
					echo "[".$cnt."] ".$v['attributes']['ID']." ".$v['attributes']['URL']." - ERROR\n";
				}
				preg_match_all('~ href="/img/products/([^"]+)~ims', $file, $m);
				if (count($m[1]) == 0) {
					echo "[".$cnt."] ".$v['attributes']['ID']." ".$v['attributes']['URL']." - NOIMGS\n";
				}
				$i = 1;
				foreach($m[1] as $url) {
					$dst  = '/home/sergey/kupi_import/imgs/'.$v['attributes']['ID']."_".$i.".jpg";
					if (!file_exists($dst)) {
						echo "[".$cnt."] http://yugcontract.ua/img/products/".$url." => ".$dst."\n";
						copy("http://yugcontract.ua/img/products/".$url,$dst);					
					} else {
						echo "[".$cnt."] ".$v['attributes']['ID']." ".$i."\n";
					}
					$i++;
				}
				$cnt++;
			}
		}
		die();
	} */
	
	/**
	*/
	function import_xml() {
		set_time_limit(0);
/*		if (empty($_FILES)) {
			return form('',array('enctype' => 'multipart/form-data'))
				->file("file")
				->save('', "Upload");
		}  */
		$supplier_id = 102;
//		db()->query("DELETE FROM `".db('category_items')."` WHERE `cat_id`=1 AND `id`>63713");
		$file_name = "/home/sergey/kupi_import/price.xml";
//		$xml = file_get_contents($_FILES['file']['tmp_name']);
		$xml = file_get_contents($file_name);
		$p = xml_parser_create();
		xml_parse_into_struct($p, $xml, $vals, $index);
		xml_parser_free($p);
		$cnt = 0;
		$product_art = 0;
		foreach ($vals as $v) {
			if ($v['tag'] == 'DESCR') {
				echo "[".$cnt."] ".$product_art."\n";
				db()->update(db('shop_products'), array('description' => nl2br($v['value'])), "`supplier_id`=".$supplier_id." AND `articul`='".$product_art."'");
				

				$cnt++;
				continue;
			}
			if (!($v['tag'] == 'PRODUCT' && $v['type'] == 'open')) continue;
			$name = $v['attributes']['NAME'];
			$name = trim(substr($name,strpos($name,' ')));
/*			if ($v['attributes']['URL']=='') {			
				echo "[".$cnt."] ".$v['attributes']['ID']." - no_url\n";			
				db()->query("DELETE FROM `".db('shop_products')." WHERE `supplier_id`=".$supplier_id." AND `articul`='".$v['attributes']['ID']."'");
				$cnt++;
			} */
/*
			if ($v['attributes']['URL']!='') {
				$file = file_get_contents($v['attributes']['URL']);
				if ($file == '') {
					echo "[".$cnt."] ".$v['attributes']['ID']." ".$v['attributes']['URL']." - ERROR\n";
				}
				preg_match_all('~<nav class=\'crumbs\' xmlns:v="http://rdf.data-vocabulary.org/#">(.*)</nav>~ims', $file, $m);
				$cats_toprocess = explode("\n",strip_tags($m[1][0]));
				$cats = array();
				foreach ($cats_toprocess as $v1) {
					$cat = trim($v1);
					if ($cat != '') $cats[] = $cat;
				}
				unset($cats[count($cats)-1]);
				unset($cats[0]);unset($cats[1]);
				$cat_id = $this->yugcontract_get_cat_id($cats);				
				db()->update(db('shop_products'), array('cat_id' => $cat_id), "`supplier_id`=".$supplier_id." AND `articul`='".$v['attributes']['ID']."'");
				echo "[".$cnt."] ".$v['attributes']['ID']." - ".$v['attributes']['NAME']."-> ".implode('/',$cats)." ".$cat_id."\n";
				
				$cnt++;
//				if ($cnt == 100) die();
			}
*/	
			$product_art = $v['attributes']['ID'];
/*			$price = $v['attributes']['PRICE']*8.32;
			db()->update(db('shop_products'), array('price' => number_format($price, 2, '.', '')),"`supplier_id`=".$supplier_id." AND `articul`='".$v['attributes']['ID']."'");*/
		}
		die();
	}	
	
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
		$objReader->setReadDataOnly(false);
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
					$item['worksheet_title'] = $title;
					$i++;
				}
					
//				$item['background_color'] = $objPHPExcel->getActiveSheet()->getStyle('A'.$row_number)->getFill()->getStartColor()->getRGB();
//				$item['coordinate'] = 'A'.$row_number;
				$items[] = $item;
			}
		
		}
		if (count($items) != 0) {
			return $this->process_items_ambar_update($items);
//			return $this->process_items_epicentr_update($items);
	//		return $this->process_items_fortuna($items);
	//		return $this->process_items_talisman($items);
//			return $this->process_items_talisman_update($items);	
	//		return $this->process_items_talisman_import($items);
//			return $this->process_items_epicentr_import($items);			
//			return $this->process_items_yugcontract_xls($items);
		} else {
			return 'no rows to process';
		}
	}
	
	function process_items_yugcontract($items) {
		$supplier_id = 102;
		
//		db()->query("DELETE FROM `".db('shop_products')."` WHERE `supplier_id`=".$supplier_id);
//		db()->query("DELETE FROM `".db('category_items')."` WHERE `id`>62897");

		$cats = array();
		foreach ($items as $item) {
			$cat_id = 9;
			if(intval($item[0]) == 0) continue;
			print_r($item);
			die();
		}

		return $result;
	}
	
	function yugcontract_get_cat_id($cats) {
		$parent_id = 9;
		foreach ($cats as $cat) {
			$A = db()->get("SELECT * FROM `".db('category_items')."` WHERE `parent_id`='".$parent_id."' AND `name`='".$cat."'");
			if (empty($A)) {
				db()->insert(db('category_items'),array(
					'parent_id' => $parent_id,
					'name' => _es($cat),
					'cat_id' => 1,
					'active' => 1,
					'url' => common()->_propose_url_from_name($cat),
				));
				$parent_id = db()->insert_id();
			} else {
				$parent_id = $A['id'];
			}
		}
		return $parent_id;
	}

	function process_items_yugcontract_xls($items) {
		$supplier_id = 102;
		
//		db()->query("DELETE FROM `".db('shop_products')."` WHERE `supplier_id`=".$supplier_id);
//		db()->query("DELETE FROM `".db('category_items')."` WHERE `id`>62897");

		$cats = array();
		foreach ($items as $item) {
			$cats[0] = mb_convert_case($item['worksheet_title'], MB_CASE_TITLE, "UTF-8"); 
/*			
			if ($item[0]!='' && $item['background_color'] == '666699') {
				$cats[1] = $item[0];
				unset($cats[2]);unset($cats[3]);				
				echo implode("/",$cats)." - ".$item['background_color']." - ".$item['coordinate']."<br />";
				continue;
			}
			if ($item[0]!='' && $item['background_color'] == '99CCFF') {
				$cats[2] = $item[0];
				unset($cats[3]);				
				echo implode("/",$cats)." - ".$item['background_color']." - ".$item['coordinate']."<br />";
				continue;
			}
			if ($item[0]!='' && $item['background_color'] == 'CCFFFF') {
				$cats[3] = $item[0];
				echo implode("/",$cats)." - ".$item['background_color']." - ".$item['coordinate']."<br />";
				continue;
			} */
//			echo implode(",",$item)."<br />";
			if (intval($item[0])!=0 && $item[1] != '' && $item[2]!='' && intval($item[9])!=0) {
				
		//		$cat_id = $this->yugcontract_get_cat_id($cats);
				$name = trim($item[2]);
				$name = substr($item[2],strpos($item[2],' '));
				$v = array(
					'name' => $name,
					'articul' => trim($item[1]),
					'cat_id' => $cat_id,
					'price' => number_format($item[9], 2, '.', ''),
					'supplier_id' => $supplier_id, 
					'url' => common()->_propose_url_from_name($name),
					'active' => 0,
				);	
				$result .= "articul: ".$v['articul']."; product: ".$v['name']." - ";				
//				db()->insert(db('shop_products'), _es($v)) or $error = true;
				$price = $item[7]*8.32;
				db()->update(db('shop_products'), array('price' => number_format($price, 2, '.', '')),"`supplier_id`=".$supplier_id." AND `articul`='".trim($item[0])."'");
		

				if ($error) {
					$result .= 'ERROR';
				} else {
					$result .= 'OK';				
				}
				$result .= ' - '.$price.'<br />';				
			} 
		}
		return $result;
	}
	
	function process_items_fortuna($items) {
		return false;
		
		$supplier_id = 99;
		
		db()->query("DELETE FROM `".db('shop_products')."` WHERE `supplier_id`=".$supplier_id);
		
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
	
	function process_items_ambar_update($items) {
		$supplier_id = 103;
		
		$products = array();
		$R = db()->query("SELECT * FROM `".db('shop_products')."` WHERE `supplier_id`=".$supplier_id);
		while ($A = db()->fetch_assoc($R)) {
			$products[$A['name']] = $A['id'];
		}
		
		$i = 0;
		$cats_list = array();
		$result = array();
		foreach ($items as $item) {
			if (intval($item[0]) == 0) continue;
			echo $item[2]." - ".$products[$item[2]]."\n";
		} 
		
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
	
	function process_items_epicentr_update($items) {
		$supplier_id = 101;
		
		$products = array();
		$R = db()->query("SELECT * FROM `".db('shop_products')."` WHERE `supplier_id`=".$supplier_id);
		while ($A = db()->fetch_assoc($R)) {
			$products[$A['articul']] = $A['id'];
		}
		
		$i = 0;
		$cats_list = array();
		$result = array();
		foreach ($items as $item) {
			if (intval($item[0]) == 0) continue;
			
			if ($item[2]!='') {
				$v = array(
					'name' => trim($item[1]),
					'articul' => $item[2],
					'price' => number_format($item[3], 2, '.', ''),
					'supplier_id' => $supplier_id, 
					'url' => common()->_propose_url_from_name(trim($item[1])),
					'active' => 1,
				);

				if (empty($products[$v['articul']])) {
					if ($v['price']!=0) {
						$result[] = array(
							'articul' => $v['articul'],
							'product' => $v['name'],
							'price' => $v['price'],
							'cat' => $item[5]." ",
							'is_new' => 'new',
						);
					}
				} else {
				/*	$result[] = array(
						'articul' => $v['articul'],
						'product' => $v['name'],
						'price' => $v['price'],
						'is_new' => 'upd',
					); */
				}
				$error = false;
	//			db()->insert(db('shop_products'), $v) or $error = true;
	/*			if ($error) {
					$result .= 'ERROR';
				} else {
					$result .= 'OK';				
				} */
			}
			$i++;
		} 
		
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
	
	
	
	function process_items_epicentr_import($items) {
		
		$supplier_id = 101;
		
		

		$remap = array (
			63710 => 'средства для дерева',
			63711 => 'малярный инструмент',
			63712 => 'краска',
			63713 => 'подготовка поверхности',
		);
		
		db()->query("DELETE FROM `".db('shop_products')."` WHERE `cat_id` IN (".implode(",",array_keys($remap)).")");
		
		foreach ($items as $item) {
			if (intval($item[0])== 0 || $item[1]== '' || $item[2] == '') continue;
			
			$cat_id = 0;
			foreach ($remap as $k=>$v) {
				if ($v == $item[5])
					$cat_id = $k;
			}
			
			$v = array(
				'name' => trim($item[1]),
				'articul' => $item[2],
				'cat_id' => $cat_id,
				'price' => number_format($item[3], 2, '.', ''),
				'supplier_id' => $supplier_id, 
				'url' => common()->_propose_url_from_name(trim($item[1])),
				'active' => 1,
			);
				
			$result .= "articul: ".$v['articul']."; product: ".$v['name']." - ";
			
			$result .= 'new - ';
			$error = false;
			db()->insert(db('shop_products'), _es($v)) or $error = true;
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

	function process_items_talisman_update($items) {
		$supplier_id = 100;

		$remap = array (
			62517 => 'абсент', 
			62505 => 'бальзам', 
			62509 => 'вино', 
			62510 => 'вермут', 
			62495 => 'виски', 
			62496 => 'водка', 
			62497 => 'джин', 
			62501 => 'коньяк', 
			62502 => 'ликер', 
			62504 => 'текила', 
			62512 => 'пиво', 
			62514 => 'слабоалкогольные напитки',
		);
		
		$products = array();
		$R = db()->query("SELECT * FROM `".db('shop_products')."` WHERE `supplier_id`=".$supplier_id);
		while ($A = db()->fetch_assoc($R)) {
			$products[$A['articul']] = $A['id'];
		}

		$i =0 ;
		foreach ($items as $item) {
			if ($item[3]=='' || $item[3] == 'Ед. изм.') continue;
			
			$cat_id = 0;
			foreach ($remap as $k=>$v) {
				if ($v == $item[4])
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
			
			if (empty($products[$v['articul']])) {
				$result[] = array(
					'articul' => $v['articul'],
					'product' => $v['name'],
					'price' => $v['price'],
					'is_new' => 'new',
				);
			} else {
				if ($v['articul']!='') {
					$result[] = array(
						'articul' => $v['articul'],
						'product' => $v['name'],
						'price' => $v['price'],
						'is_new' => 'upd',
					);

					db()->query("UPDATE `".db('shop_products')."` SET `name`='"._es($v['name'])."' WHERE `articul`='"._es($v['articul'])."' AND `supplier_id`=".$supplier_id);
				}
			}				
			$i++;
		}
 
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
	
	function process_items_talisman_import($items) {
		return false;
		$supplier_id = 100;

		db()->query("DELETE FROM `".db('shop_products')."` WHERE `supplier_id`=".$supplier_id);
				
		$remap = array (
			62517 => 'абсент', 
			62505 => 'бальзам', 
			62509 => 'вино', 
			62510 => 'вермут', 
			62495 => 'виски', 
			62496 => 'водка', 
			62497 => 'джин', 
			62501 => 'коньяк', 
			62502 => 'ликер', 
			62504 => 'текила', 
			62512 => 'пиво', 
			62514 => 'слабоалкогольные напитки',
		);
		

		$i =0 ;
		foreach ($items as $item) {
			if ($i==0) {$i++;continue;}
			
			$cat_id = 0;
			foreach ($remap as $k=>$v) {
				if ($v == $item[4])
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
