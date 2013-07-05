<?php
class yf_friends_groups{
	
/*		
			echo "<b>".$key."</b><br>";			
					$v1 = 2; // 2^1
					$v2 = 4;
					$v3 = 8;
					$v4 = 16;
					$mask = $value - 1;

					echo intval((bool)($mask & $v1))."<br />";
					echo intval((bool)($mask & $v2))."<br />";
					echo intval((bool)($mask & $v3))."<br />";
					echo intval((bool)($mask & $v4))."<br />";
*/				
	
	function _init () {
		// Reference to parent object
		$this->PARENT_OBJ	= module(FRIENDS_CLASS_NAME);
	}


	function friends_groups (){
		if (empty($this->PARENT_OBJ->USER_ID)) {
			return _error_need_login();
		}

		$groups_info = $this->_get_friends_groups($this->PARENT_OBJ->USER_ID);
		
		if(isset($_POST["go"])){
			// save mask
			foreach ((array)$_POST["editfriend_groupmask_"] as $key => $value){
					$friends[$key] = $value - 1;
			}	
			if(!empty($friends)){
				$this->_save_mask($this->PARENT_OBJ->USER_ID, $friends);
			}
			
			// save POST setting to array $group_new_info
			for($i = 0; $i < $this->PARENT_OBJ->NUMBER_FRIENDS_GROUP; $i++){
				$group_new_info[$i+1]["title"] = $_POST["efg_set_".($i+1)."_name"];
				$group_new_info[$i+1]["order"] = $_POST["efg_set_".($i+1)."_sort"];
				$group_new_info[$i+1]["delete"] = $_POST["efg_delete_".($i+1)];
			}
			
			foreach ((array)$group_new_info as $key => $group_new){
				// delete group
				if($group_new_info[$key]["delete"] == "1"){
					$group_delete[$key] = $key;
				}

				// rename group
				if(($group_new_info[$key]["title"] !== $groups_info[$key]["title"]) AND ($group_new_info[$key]["title"] !== "") AND ($group_new_info[$key]["delete"] !== "1")){
					$group_rename[$key] = $group_new_info[$key]["title"];
				}
				
				// reorder group
				if(($group_new_info[$key]["order"] !== $groups_info[$key]["order"]) AND ($group_new_info[$key]["order"] !== "0") ){
					$group_order[$key] = $group_new_info[$key]["order"];
				}
			}
			
			if(!empty($group_delete)){
				$this->_delete_group($group_delete);
			}
			if(!empty($group_rename)){
				$this->_rename_group($group_rename);
			}
			if(!empty($group_order)){
				$this->_reorder_group($group_order);
			}
		}
		
		$groups_info = array();
		$groups_info = $this->_get_friends_groups($this->PARENT_OBJ->USER_ID);
		
		foreach ((array)$groups_info as $key => $group){
			$groups .= "<option value='".($group["id2"])."'>".$group["title"];
		}

		for($i = 1; $i < $this->PARENT_OBJ->NUMBER_FRIENDS_GROUP + 1; $i++){
			if(isset($groups_info[$i])){
				$groups_hidden .= "<input type='hidden' name='efg_set_".$groups_info[$i]["id2"]."_name' value='".$groups_info[$i]["title"]."' />";
				$groups_hidden .= "<input type='hidden' name='efg_set_".$groups_info[$i]["id2"]."_sort' value='".$groups_info[$i]["order"]."' />";
				$groups_hidden .= "<input type='hidden' name='efg_delete_".$groups_info[$i]["id2"]."' value='0' />";
				//$groups_hidden .= "<input type='hidden' name='efg_set_".$groups_info[$i]["id2"]."_public' value='0
			}else{
				$groups_hidden .= "<input type='hidden' name='efg_set_".$i."_name' value='' />";
				$groups_hidden .= "<input type='hidden' name='efg_set_".$i."_sort' value='0' />";
				$groups_hidden .= "<input type='hidden' name='efg_delete_".$i."' value='0' />";
				//$groups_hidden .= "<input type='hidden' name='efg_set_".$i."_public' value='0
			}
		}
			
		$friends = "";
		$friends_info = user($this->PARENT_OBJ->_get_user_friends_ids($this->PARENT_OBJ->USER_ID), "short");
		
		// delete community
		foreach ((array)$friends_info as $key => $value){
			if($value["group"] == "99"){
				unset($friends_info[$key]);
			}
		}
		
		if(!empty($friends_info)){
			foreach ((array)$friends_info as $value){
				$friends_ids[$value["id"]] = $value["id"];
			}
		
			$Q = db()->query("SELECT * FROM ".db('friends_users')." WHERE friend_id IN(".implode(",", $friends_ids).")");
			while ($A = db()->fetch_assoc($Q)) {
				$friends_mask[$A["friend_id"]] = $A["mask"];
			}
		
			foreach ((array)$friends_info as $value){
				if(!empty($friends_mask[$value["id"]])){
					$mask =	($friends_mask[$value["id"]]+1);
				}else{
					$mask = "1";
				}
				$friends .= "<input type='hidden' name='editfriend_groupmask_[".$value["id"]."]' value='".$mask."' />";
				$friends_map .= "<input type='hidden' name='nameremap[".$value["id"]."]' value='"._prepare_html(_display_name($value))."' id=\"nameremap_[".$value["id"]."]\" />";
			}
		}
		
		$replace = array(
			"form_action"			=> "./?object=".$_GET["object"]."&action=".$_GET["action"],
			"manage_groups"			=> "./?object=".FRIENDS_CLASS_NAME."&action=manage_groups",
			"groups_hidden"			=> $groups_hidden,
			"groups"				=> $groups,
			"friends"				=> $friends,
			"friends_map"			=> $friends_map,
			"number_friends_group"	=> $this->PARENT_OBJ->NUMBER_FRIENDS_GROUP,
		);
		return tpl()->parse($_GET["object"]."/friends_groups_main", $replace);
	}
	
	function _get_friends_groups($user_id){
		$Q = db()->query("SELECT * FROM ".db('friends_groups')." WHERE user_id=".$user_id." ORDER BY order ASC");
		while ($A = db()->fetch_assoc($Q)) {
			$groups_info[$A["id2"]] = $A;
		}
		
		if(empty($groups_info)){
			foreach ((array)$this->PARENT_OBJ->_friends_group as $key => $title){
				db()->INSERT("friends_groups", array(
					"id2"		=> $key,
					"user_id"	=> $user_id,
					"title"		=> $title,
					"order"		=> $key
				));
			}
			$groups_info = $this->_get_friends_groups($user_id);
		}
		return $groups_info;
	}

	function _delete_group($group){
		if (empty($this->PARENT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		if(empty($group)){
			return;
		}
		db()->query("DELETE FROM ".db('friends_groups')." WHERE user_id= ".$this->PARENT_OBJ->USER_ID." AND id2 IN(".implode(",", $group).")");
	}

	function _reorder_group($group){
		if (empty($this->PARENT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		if(empty($group)){
			return;
		}
		foreach ((array)$group as $id2 => $order){
			db()->UPDATE("friends_groups", array(
				"order" => $order,
			), "id2=".intval($id2)." AND user_id=".$this->PARENT_OBJ->USER_ID);
		}
	}
	
	function _rename_group($group){
		if (empty($this->PARENT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		if(empty($group)){
			return;
		}
		
		foreach ((array)$group as $id2 => $title){
		
			$exist = db()->query_fetch("SELECT id FROM ".db('friends_groups')." WHERE id2=".intval($id2)." AND user_id=".$this->PARENT_OBJ->USER_ID);

			if(!empty($exist)){
				db()->UPDATE("friends_groups", array(
					"title" => $title,
				), "id2=".intval($id2)." AND user_id=".$this->PARENT_OBJ->USER_ID);
			}else{
				db()->INSERT("friends_groups", array(
					"id2"		=> $id2,
					"user_id"	=> $this->PARENT_OBJ->USER_ID,
					"title"		=> $title,
					"order"		=> "0",
				));
			}
		}
	}
	
	function _save_mask($user_id, $friends_ids){
		if (empty($this->PARENT_OBJ->USER_ID)) {
			return _error_need_login();
		}
		if(empty($friends_ids)){
			return;
		}

		db()->query("DELETE FROM ".db('friends_users')." WHERE user_id=".$user_id);
		
		foreach ((array)$friends_ids as $friend => $mask){
			db()->INSERT("friends_users", array(
				"user_id"	=> $user_id,
				"friend_id"	=> $friend,
				"mask"		=> $mask,
			));
		}
	}
	
	function _ids_to_mask($group_ids){
	
		foreach ((array)$group_ids as $value){
			$mask += pow(2, $value);
		}
		return $mask;
	}
	
	function _mask_to_ids($mask){
		
		$mask = intval($mask);
		
		for($i = 1; $i < 32; $i++){
			if(pow(2, $i) & $mask) {
				$ids[$i] = $i;
			}
		}

		return $ids;
	}
	
	function check_mask_permissions($user_mask, $post_mask){
		
		$user_allowed_group_ids = $this->_mask_to_ids($user_mask);
		$post_allowed_group_ids = $this->_mask_to_ids($post_mask);
		
		foreach ((array)$user_allowed_group_ids as $group){
			if(in_array($group, $post_allowed_group_ids)){
				return true;
			}
		}

		return false;
	}
}
