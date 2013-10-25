<?php

class form2_sql_auto {
	function show() {
		return form('SELECT name,id FROM '.db('icons').' LIMIT 10')
			->auto();
	}
}
