<?php
class yf_manage_shop_products{

	function products () {
		return common()->table2("SELECT * FROM ".db('shop_products'), array(
				'filter' => $_SESSION['manage_shop'],
				'filter_params' => array(
					'name'	=> 'like',
				),
			))
			->image("uploads/shop/products/{subdir2}/{subdir3}/product_%d_1_small.jpg", WEB_PATH."uploads/shop/products/{subdir2}/{subdir3}/product_%d_1_full.jpg")
			->text("name")
			->link("cat_id", "./?object=category_editor&action=show_items&&id=%d", _class('cats')->_get_items_names("shop_cats"))
			->text("price")
			->text("quantity")
			->date("add_date")
			->btn_edit("", "./?object=manage_shop&action=product_edit&id=%d")
			->btn_delete("", "./?object=manage_shop&action=product_delete&id=%d")
			->btn_clone("", "./?object=manage_shop&action=product_clone&id=%d")
			->btn_active("", "./?object=manage_shop&action=product_activate&id=%d")
			->footer_add("Add product", "./?object=manage_shop&action=product_add")
			->footer_link("Attributes", "./?object=manage_shop&action=attributes")
			->footer_link("Categories", "./?object=category_editor&action=show_items&id=shop_cats")
			->footer_link("Orders", "./?object=manage_shop&action=show_orders")
			->render();
	}

}