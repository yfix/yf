<?php

/**
* Gallery integration
* 
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_gallery_integration {

	/**
	* Constructor
	*/
	function _init () {
		// Reference to the parent object
		$this->GALLERY_OBJ	= module(GALLERY_CLASS_NAME);
	}
	
	/**
	* Home page integration
	*/
	function _for_home_page($num = 5) {
		return $this->GALLERY_OBJ->_show_stats(GALLERY_CLASS_NAME ."/for_home_page_main", "", $num);
	}
	
	/**
	* 
	*/
	function _for_user_profile($user_info, $MAX_SHOW_GALLERY_PHOTO){
		return $this->GALLERY_OBJ->_show_stats(GALLERY_CLASS_NAME ."/for_home_page_main", "", $MAX_SHOW_GALLERY_PHOTO);
	}
	
	/**
	* 
	*/
	function _widget_last_photo(){
		return $this->GALLERY_OBJ->_show_stats(GALLERY_CLASS_NAME ."/widget_photo_main", GALLERY_CLASS_NAME."/widget_photo_item", 1);

	}
	
	function _rss_general(){

		$Q = db()->query("SELECT `id`,`name`,`desc`,`user_id`,`add_date` FROM `".db('gallery_photos')."` WHERE `active`='1' AND `is_public`='1' LIMIT ".$this->GALLERY_OBJ->NUM_RSS);
		while ($A = db()->fetch_assoc($Q)) {
			$photo_info[$A["id"]] = $A;
			$users_ids[$A["user_id"]] = $A["user_id"];
		}
		
		$user_names = user($users_ids, array("nick"), array("WHERE" => array("active" => "1")));
		
		if(!empty($photo_info)){
			foreach ((array)$photo_info as $photo){
			
				$img_url = $this->GALLERY_OBJ->_photo_web_path($photo);
				$title = $photo["name"] !== ""?$photo["name"]:t("no title");
				
				$data[] = array(
					"title"			=> _prepare_html(t("Gallery")." - ".$title),
					"link"			=> process_url("./?object=gallery&action=show_medium_size&id=".$photo["id"]),
					"description"	=> nl2br(_prepare_html(strip_tags($photo["desc"]))."<br> <img src='".$img_url."' alt='".$title."' />"),
					"date"			=> $photo["add_date"],
					"author"		=> $user_names[$photo["user_id"]]["nick"],
					"source"		=> "",
				);
			}
		}
		return $data;
		
	}
}
