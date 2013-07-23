<?php
class yf_manage_shop_suppliers{

	function suppliers () {
		return common()->table2("SELECT * FROM ".db('shop_suppliers'))
#			->image("name")
			->text("name")
			->text("url")
			->text("meta_keywords")
			->text("meta_desc")
			->btn_edit("", "./?object=manage_shop&action=supplier_edit&id=%d")
			->btn_delete("", "./?object=manage_shop&action=supplier_delete&id=%d")
			->footer_link("Add", "./?object=manage_shop&action=supplier_add")
			->render();
	}	
}