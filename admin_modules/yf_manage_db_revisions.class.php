<?php

/**
*/
class yf_manage_db_revisions {

	/**
	*/
	function show() {
// TODO
		return table('SELECT * FROM '.db('db_revisions'))->auto();
	}
}