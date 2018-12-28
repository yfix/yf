<?php


class yf_locale_editor_cleanup
{
    public function _init()
    {
        $this->_parent = module('locale_editor');
    }

    /**
     * Automatic translator via Google translate.
     */
    public function cleanup()
    {
        $a['back_link'] = url('/@object/vars');
        $a['redirect_link'] = $a['back_link'];
        ! $a['lang_from'] && $a['lang_from'] = 'en';
        //		!isset($a['keep_existing']) && $a['keep_existing'] = 1;
        $display_func = function () {
            return ! is_post();
        };
        return $this->_parent->_header_links() . '<div class="col-md-12"><br>' .
            form($a + (array) $_POST)
            ->validate()
            ->on_validate_ok(function ($data, $e, $vr, $form) {
                return $this->_on_validate_ok($data, $form);
            })
//			->yes_no_box('keep_existing', ['display_func' => $display_func])
            ->save_and_back('', ['desc' => 'Cleanup', 'display_func' => $display_func])
        . '</div>';
    }

    /**
     * @param mixed $params
     * @param null|mixed $form
     */
    public function _on_validate_ok($params = [], $form = null)
    {
        $p = $params ?: $_POST;

        // TODO: named cleanups and separate stats by them
        $deleted = 0;
        // translations without parents
        db()->query('DELETE FROM ' . db('locale_translate') . ' WHERE var_id NOT IN( 
			SELECT id FROM ' . db('locale_vars') . ' 
		)');
        $deleted += db()->affected_rows();
        // parents without translations
        db()->query('DELETE FROM ' . db('locale_vars') . ' WHERE id NOT IN(
			SELECT var_id FROM ' . db('locale_translate') . '
		)');
        $deleted += db()->affected_rows();
        // empty translations
        db()->query('DELETE FROM ' . db('locale_translate') . ' WHERE value = ""');
        $deleted += db()->affected_rows();
        // same or empty translations
        $sql = '
			DELETE p1 
			FROM ' . db('locale_translate') . ' AS p1
			INNER JOIN (
				SELECT t.var_id, t.locale 
				FROM ' . db('locale_translate') . ' AS t
				INNER JOIN ' . db('locale_vars') . ' AS v ON t.var_id = v.id
				WHERE t.value = v.value OR t.value = ""
			) AS p2
			ON p1.locale = p2.locale AND p1.var_id = p2.var_id
		';
        db()->query($sql);
        $deleted += db()->affected_rows();
        // Special for the ignore case case
        if ($this->VARS_IGNORE_CASE) {
            // Delete non-changed translations
            $sql = '
				DELETE p1 
				FROM ' . db('locale_translate') . ' AS p1
				INNER JOIN (
					SELECT t.var_id, t.locale 
					FROM ' . db('locale_translate') . ' AS t
					INNER JOIN ' . db('locale_vars') . ' AS v ON t.var_id = v.id
					WHERE LOWER(REPLACE(CONVERT(t.value USING utf8), " ", "_")) = LOWER(REPLACE(CONVERT(v.value USING utf8), " ", "_"))
				) AS p2
				ON p1.locale = p2.locale AND p1.var_id = p2.var_id
			';
            db()->query($sql);
            $deleted += db()->affected_rows();
            // Delete duplicated records
            $sql = '
				DELETE p1 
				FROM ' . db('locale_vars') . ' AS p1
				INNER JOIN (
					SELECT id FROM ' . db('locale_vars') . '
					GROUP BY LOWER(REPLACE(CONVERT(value USING utf8), " ", "_")) 
					HAVING COUNT(*) > 1
				) AS p2
				USING (id)
			';
            db()->query($sql);
            $deleted += db()->affected_rows();
        }
        // translations without parents
        db()->query('DELETE FROM ' . db('locale_translate') . ' WHERE var_id NOT IN( 
			SELECT id FROM ' . db('locale_vars') . ' 
		)');
        $deleted += db()->affected_rows();
        // parents without translations
        db()->query('DELETE FROM ' . db('locale_vars') . ' WHERE id NOT IN(
			SELECT var_id FROM ' . db('locale_translate') . '
		)');
        $deleted += db()->affected_rows();

        //		$stats['failed']	&& common()->message_error($stats['failed'].' variable(s) failed to translate');
        //		$stats['updated']	&& common()->message_success($stats['updated'].' variable(s) successfully translated');
        //		!$stats	&& common()->message_info('Translate done, nothing changed');
        common()->message_success('Deleted records: ' . (int) $deleted);

        cache_del('locale_translate_' . $lang);

        $form->container(a(['href' => '/@object/@action', 'title' => 'Back', 'icon' => 'fa fa-arrow-left', 'class' => 'btn btn-primary btn-small', 'target' => '']), ['wide' => true]);
        //		$form->container($this->_parent->_pre_text(_var_export(_prepare_html($to_update), 1)), ['wide' => true]);
    }
}
