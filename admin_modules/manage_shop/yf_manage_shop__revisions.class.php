<?php
class yf_manage_shop__revisions {

	function _add_revision($object, $action, $table_name, $item_id) {
		$data = array();
		if ($action != 'delete') {
			$data = db()->get("SELECT * FROM `{$table_name}` WHERE `id`='{$item_id}'");
		}
		db()->INSERT(db('admin_revisions'),array(
			'user_id' => intval(main()->ADMIN_ID),
			'add_date' => $_SERVER['REQUEST_TIME'],
			'object' => $object,
			'action' => $action,
			'table_name' => $table_name,
			'item_id' => $item_id,
			'data' => json_encode($data),
		));
	}
	
}
