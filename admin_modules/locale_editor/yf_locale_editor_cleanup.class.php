<?php

/**
*/
class yf_locale_editor_cleanup {

	/**
	* Cleanup variables (Delete not translated or missed vars)
	*/
	function cleanup_vars () {
// TODO: move out into submodule
		// Find empty translations
		db()->query(
			"DELETE FROM ".db('locale_translate')." WHERE value=''"
		);
		// Delete non-changed translations
		$Q = db()->query(
			"SELECT * FROM ".db('locale_vars')." AS v
				, ".db('locale_translate')." AS t 
			WHERE t.var_id=v.id 
				AND (t.value=v.value OR t.value = '')"
		);
		while ($A = db()->fetch_assoc($Q)) {
			// Do delete found records
			db()->query(
				"DELETE FROM ".db('locale_translate')." 
				WHERE var_id=".intval($A["id"])." 
					AND locale='"._es($A["locale"])."'"
			);
		}
		// Special for the ignore case case
		if ($this->VARS_IGNORE_CASE) {
			// Delete non-changed translations
			$Q = db()->query(
				"SELECT * FROM ".db('locale_vars')." AS v
					, ".db('locale_translate')." AS t 
				WHERE t.var_id=v.id 
					AND LOWER(REPLACE(CONVERT(t.value USING utf8), ' ', '_')) 
						= LOWER(REPLACE(CONVERT(v.value USING utf8), ' ', '_'))"
			);
			// Delete non-changed translations
			while ($A = db()->fetch_assoc($Q)) {
				db()->query(
					"DELETE FROM ".db('locale_translate')." 
					WHERE var_id=".intval($A["id"])." 
						AND locale='"._es($A["locale"])."'"
				);
			}
			// Delete duplicated records
			$Q = db()->query(
				"SELECT id FROM ".db('locale_vars')."
				GROUP BY LOWER(REPLACE(CONVERT(value USING utf8), ' ', '_')) 
				HAVING COUNT(*) > 1"
			);
			while ($A = db()->fetch_assoc($Q)) {
				db()->query(
					"DELETE FROM ".db('locale_vars')." WHERE id=".intval($A["id"])
				);
			}
		}
		// Delete translations without parents
		db()->query(
			"DELETE FROM ".db('locale_translate')." 
			WHERE var_id NOT IN( 
				SELECT id FROM ".db('locale_vars')." 
			)"
		);
		// Delete parents without translations
		db()->query(
			"DELETE FROM ".db('locale_vars')." 
			WHERE id NOT IN( 
				SELECT var_id FROM ".db('locale_translate')." 
			)"
		);
		// Return user back
		return js_redirect("./?object=".$_GET["object"]."&action=show_vars");
	}
}
