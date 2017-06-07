<?php

/**
* Cleanup variables by different criterias
*/
class yf_locale_editor_cleanup {

	/**
	*/
	function _init () {
		$this->_parent = module('locale_editor');
	}

	/**
	*/
	function cleanup () {
		$deleted = 0;
		// translations without parents
		db()->query('DELETE FROM '.db('locale_translate').' WHERE var_id NOT IN( 
			SELECT id FROM '.db('locale_vars').' 
		)');
		$deleted += db()->affected_rows();
		// parents without translations
		db()->query('DELETE FROM '.db('locale_vars').' WHERE id NOT IN(
			SELECT var_id FROM '.db('locale_translate').'
		)');
		$deleted += db()->affected_rows();
		// empty translations
		db()->query('DELETE FROM '.db('locale_translate').' WHERE value = ""');
		$deleted += db()->affected_rows();
		// same or empty translations
		$sql = '
			DELETE p1 
			FROM '.db('locale_translate').' AS p1
			INNER JOIN (
				SELECT t.var_id, t.locale 
				FROM '.db('locale_translate').' AS t
				INNER JOIN '.db('locale_vars').' AS v ON t.var_id = v.id
				WHERE t.value = v.value OR t.value = ""
			) AS p2
			ON p1.locale = p2.locale AND p1.var_id = p2.var_id
		';
		db()->query($sql);
		$deleted += db()->affected_rows();
		// Special for the ignore case case
		if ($this->_parent->VARS_IGNORE_CASE) {
			// Delete non-changed translations
			$sql = '
				DELETE p1 
				FROM '.db('locale_translate').' AS p1
				INNER JOIN (
					SELECT t.var_id, t.locale 
					FROM '.db('locale_translate').' AS t
					INNER JOIN '.db('locale_vars').' AS v ON t.var_id = v.id
					WHERE LOWER(REPLACE(CONVERT(t.value USING utf8), " ", "_")) = LOWER(REPLACE(CONVERT(v.value USING utf8), " ", "_"))
				) AS p2
				ON p1.locale = p2.locale AND p1.var_id = p2.var_id
			';
			db()->query($sql);
			$deleted += db()->affected_rows();
			// Delete duplicated records
			$sql = '
				DELETE p1 
				FROM '.db('locale_vars').' AS p1
				INNER JOIN (
					SELECT id FROM '.db('locale_vars').'
					GROUP BY LOWER(REPLACE(CONVERT(value USING utf8), " ", "_")) 
					HAVING COUNT(*) > 1
				) AS p2
				USING (id)
			';
			db()->query($sql);
			$deleted += db()->affected_rows();
		}
		// translations without parents
		db()->query('DELETE FROM '.db('locale_translate').' WHERE var_id NOT IN( 
			SELECT id FROM '.db('locale_vars').' 
		)');
		$deleted += db()->affected_rows();
		// parents without translations
		db()->query('DELETE FROM '.db('locale_vars').' WHERE id NOT IN(
			SELECT var_id FROM '.db('locale_translate').'
		)');
		$deleted += db()->affected_rows();
		common()->message_success('Deleted records: '.(int)$deleted);
		return js_redirect('/@object/vars');
	}
}
