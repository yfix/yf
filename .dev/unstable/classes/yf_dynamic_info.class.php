<?php
class yf_dynamic_info {
	
	/**
	*
	*/
	function _edit ($user_id, $default_values = "") {

		$sql = "SELECT * FROM ".db('user_data_info_fields')." WHERE active=1 ORDER BY `order`, name";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$this->_dynamic_fields[$A["name"]] = $A;
		}
		
		if(!empty($user_id)){
			$_dynamic_info_values = user($user_id, "dynamic");
		}
		
		foreach ((array)$this->_dynamic_fields as $_field_name => $_field_info) {
			$type	= $_field_info["type"];
			// Set  default values
			$val	= $_dynamic_info_values[$_field_name];
			if (!strlen($_dynamic_info_values[$_field_name])) {
				$val = $_field_info["default_value"];
			}
			
			if ((!strlen($_dynamic_info_values[$_field_name])) && (!empty($default_values))) {
				$val = $default_values[$_field_name];
			}
			
			if ($type == "varchar") {
				$replace2 = array(
					"name" 		=> $_field_name,
					"value"		=> $val,
				);
				$replace_dynamic[$_field_name] = array(
					"field" 	=> tpl()->parse(__CLASS__ ."/dynamic_item_varchar", $replace2),
					"name"		=> $_field_name,
					"comment"	=> $_field_info["comment"],
				);
			} elseif ($type == "text") {
				$replace2 = array(
					"name" 	=> $_field_name,
					"value"	=> $val,
				);
				$replace_dynamic[$_field_name] = array(
					"field" 	=> tpl()->parse(__CLASS__ ."/dynamic_item_text", $replace2),
					"name"		=> $_field_name,
					"comment"	=> $_field_info["comment"],
				);
			} elseif ($type == "select") {
				$select_array = explode(";", $_field_info["value_list"]);

				$replace_dynamic[$_field_name] = array(
					"field" 	=> common()->select_box($_field_name, $select_array, $val, false, 2, "", false),
					"name"		=> $_field_name,
					"comment"	=> $_field_info["comment"],
				);
			} elseif ($type == "check") {
				$check_array = explode(";", $_field_info["value_list"]);
				
				if(!is_array($val)){
					$val = unserialize($val);
				}
				
				foreach ((array)$check_array as $item_key => $item_val){
					$checkbox .=  common()->check_box($_field_name."[".$item_key."]", $item_val, isset($val[$item_key])?"1":"0")."<br>";
				}
				
				$replace_dynamic[$_field_name] = array(
					"field" 	=> $checkbox,
					"name"		=> $_field_name,
					"comment"	=> $_field_info["comment"],
				);
			} elseif ($type == "radio") {
				$radio_array = explode(";", $_field_info["value_list"]);
				
				$replace_dynamic[$_field_name] = array(
					"field" 	=> common()->radio_box($_field_name, $radio_array, $val, true, 2, "", false),
					"name"		=> $_field_name,
					"comment"	=> $_field_info["comment"],
				);
			}
		}
		
		return $replace_dynamic;

	}
	

	/**
	*
	*/
	function _save ($user_id, $values_array = "") {
	
		if(empty($user_id)){
			return;
		}
		
		if(!empty($values_array)){
			$_POST = $values_array;
		}
		
		$sql = "SELECT * FROM ".db('user_data_info_fields')." WHERE active=1 ORDER BY `order`, name";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$this->_dynamic_fields[$A["name"]] = $A;
		}

		
		foreach ((array)$this->_dynamic_fields as $val) {
		
			if(is_array($_POST[$val["name"]])){
				$_POST[$val["name"]] = serialize($_POST[$val["name"]]);
			}
		
			$dynamic_sql_array[$val["name"]] = $_POST[$val["name"]];
		}
		db()->update('user', $dynamic_sql_array, $user_id);
	}

	/**
	*
	*/
	function _view ($user_id) {
	
		if(empty($user_id)){
			return;
		}
	
		$sql = "SELECT * FROM ".db('user_data_info_fields')." WHERE active=1 ORDER BY `order`, name";
		$Q = db()->query($sql);
		while ($A = db()->fetch_assoc($Q)) {
			$this->_dynamic_fields[$A["name"]] = $A;
		}
				
		$_dynamic_info_values = user(intval($user_id), "dynamic");
		foreach ((array)$this->_dynamic_fields as $_field_name => $_field_info) {
			if (!isset($_dynamic_info_values[$_field_name])) {
				continue;
			}
			
			$val = $_dynamic_info_values[$_field_name];
			
			if(($_field_info["type"] == "select") || ($_field_info["type"] == "check") || ($_field_info["type"] == "radio")){

				$value_list = explode(";", $_field_info["value_list"]);
				
				if($_field_info["type"] == "check"){
					$val = unserialize($val);
					
					foreach ((array)$val as $key => $val){
						$temp_val .= "- ".$value_list[$key]."\n";
					}
					
					$val = $temp_val;
				}else{
					$val = $value_list[$val];
				}
			}
			
			$replace_dynamic[$_field_name] = array(
				"value"		=> nl2br(_prepare_html($val)),
				"name"		=> _prepare_html($_field_name),
				"type"		=> _prepare_html($_field_info["type"]),
				"comment"	=> _prepare_html($_field_info["comment"]),
			);
		}

		return $replace_dynamic;
	}
	
}