<?php
class yf_manage_shop_attributes{

	function attributes () {
		return common()->table2("SELECT * FROM ".db('shop_product_attributes_info')." ORDER BY `order` ASC")
			->link("category_id", "./?object=category_editor&action=show_items&&id=%d", _class('cats')->_get_items_names("shop_cats"))
			->text("name")
			->text("value_list")
			->text("default_value")
			->btn_edit("", "./?object=manage_shop&action=attribute_edit&id=%d")
			->btn_delete("", "./?object=manage_shop&action=attribute_delete&id=%d")
			->btn_active("", "./?object=manage_shop&action=attribute_activate&id=%d")
			->footer_link("Add","./?object=manage_shop&action=attribute_add")
			->render();
	}	
	
}