<?php

class db_shard_hooks_test {
	function show(){
		module_conf('installer_db', 'create_table_post_callbacks', array(
			'^b_bets_(?P<shard>[0-9]{4}_[0-9]{2}_[0-9]{2})$' => function($table, $struct, $db, $m) {
				// From b_bets_2014_01_01 Will produce 140101000000000
				$auto_inc = substr(str_replace("_", "", $m['shard']), 2)."000000000";
				return $db->query("ALTER TABLE ".$table." AUTO_INCREMENT = ".$auto_inc);
			},
			'^b_contracts_(?P<shard>[0-9]{4}_[0-9]{2})$' => function($table, $struct, $db, $m) {
				// From b_contracts_2014_01 Will produce 14010000000
				$auto_inc = substr(str_replace("_", "", $m['shard']), 2)."0000000";
				return $db->query("ALTER TABLE ".$table." AUTO_INCREMENT = ".$auto_inc);
			},
		));
		db()->query('DROP TABLE IF EXISTS b_bets_2020_01_01');
		db()->query('SELECT * FROM b_bets_2020_01_01');
		db()->query('DROP TABLE IF EXISTS b_contracts_2020_01');
		db()->query('SELECT * FROM b_contracts_2020_01');
	}
}