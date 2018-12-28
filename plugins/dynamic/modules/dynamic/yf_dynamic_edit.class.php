<?php


class yf_dynamic_edit
{
    public function __construct()
    {
        $this->_parent = module('dynamic');
    }

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $arguments
     */
    public function __call($name, $arguments)
    {
        trigger_error(__CLASS__ . ': No method ' . $name, E_USER_WARNING);
        return false;
    }

    /**
     * AJAX-based method save current locale variable.
     */
    public function save_locale_var()
    {
        no_graphics(true);
        if ( ! DEBUG_MODE && ! $_SESSION['locale_vars_edit']) {
            return print 'Access denied';
        }
        $SOURCE_VAR_NAME = str_replace('%20', ' ', trim($_POST['source_var']));
        $EDITED_VALUE = str_replace('%20', ' ', trim($_POST['edited_value']));
        $CUR_LOCALE = conf('language');
        // First we need to check if such var exists
        if ( ! strlen($SOURCE_VAR_NAME)) {
            return print 'Empty source var';
        }
        if ( ! strlen($EDITED_VALUE)) {
            return print 'Empty edited value';
        }
        if ($this->_parent->VARS_IGNORE_CASE) {
            $SOURCE_VAR_NAME = str_replace(' ', '_', _strtolower($SOURCE_VAR_NAME));
            $sql = 'SELECT * FROM ' . db('locale_vars') . " WHERE REPLACE(CONVERT(value USING utf8), ' ', '_') = '" . _es($SOURCE_VAR_NAME) . "'";
        } else {
            $sql = 'SELECT * FROM ' . db('locale_vars') . " WHERE value='" . _es($SOURCE_VAR_NAME) . "'";
        }
        $var_info = db()->query_fetch($sql);
        // Create variable record if not found
        if (empty($var_info['id'])) {
            $sql = ['value' => _es($SOURCE_VAR_NAME)];
            db()->INSERT('locale_vars', $sql);
            $var_info['id'] = db()->INSERT_ID();
        }
        $sql_data = [
            'var_id' => (int) ($var_info['id']),
            'value' => _es($EDITED_VALUE),
            'locale' => _es($CUR_LOCALE),
        ];
        // Check if record is already exists
        $Q = db()->query('SELECT * FROM ' . db('locale_translate') . ' WHERE var_id=' . (int) ($var_info['id']));
        while ($A = db()->fetch_assoc($Q)) {
            $var_tr[$A['locale']] = $A['value'];
        }
        if (isset($var_tr[$CUR_LOCALE])) {
            db()->UPDATE('locale_translate', $sql_data, 'var_id=' . (int) ($var_info['id']) . " AND locale='" . _es($CUR_LOCALE) . "'");
        } else {
            db()->INSERT('locale_translate', $sql_data);
        }
        $sql = db()->UPDATE('locale_translate', $sql_data, 'var_id=' . (int) ($var_info['id']) . " AND locale='" . _es($CUR_LOCALE) . "'", true);
        db()->INSERT('revisions', [
            'user_id' => (int) (MAIN_TYPE_USER ? main()->USER_ID : main()->ADMIN_ID),
            'object_name' => _es('locale_var'),
            'object_id' => _es($var_info['id']),
            'old_text' => _es($var_tr[$CUR_LOCALE]),
            'new_text' => _es($EDITED_VALUE),
            'date' => time(),
            'ip' => common()->get_ip(),
            'comment' => _es('locale: ' . $CUR_LOCALE),
        ]);
        cache_del('locale_translate_' . $CUR_LOCALE);
        return print 'Save OK';
    }

    /**
     * AJAX-based method edit selected template for the current locale.
     */
    public function edit_locale_stpl()
    {
        no_graphics(true);
        if ( ! DEBUG_MODE || ! tpl()->ALLOW_INLINE_DEBUG) {
            return print 'Access denied';
        }
        // Prepare template name to get
        $STPL_NAME = trim($_GET['id']);
        // Some security checks
        $STPL_NAME = preg_replace('/[^a-z0-9_\-\/]/i', '', $STPL_NAME);
        $STPL_NAME = trim($STPL_NAME, '/');
        $STPL_NAME = preg_replace('#[\/]{2,}#', '/', $STPL_NAME);
        if (empty($STPL_NAME)) {
            return print 'STPL name required!';
        }
        // Path to the lang-based theme
        $_lang_theme_path = INCLUDE_PATH . tpl()->_THEMES_PATH . conf('theme') . '.' . conf('language') . '/';
        // Try to get template
        $text = tpl()->_get_template_file($STPL_NAME . tpl()->_STPL_EXT);
        $text = str_replace("\r", '', $text);
        // Determine used source
        $_source = tpl()->CACHE[$STPL_NAME]['storage'];
        // Try to save template
        if (isset($_POST['text'])) {
            // Compare source and result
            $result = 'Nothing changed';
            if ($_POST['text'] != $text) {
                $locale_stpl_path = $_lang_theme_path . $STPL_NAME . tpl()->_STPL_EXT;
                // First try to create subdir
                if ( ! file_exists(dirname($locale_stpl_path))) {
                    _mkdir_m(dirname($locale_stpl_path));
                }
                // Save file
                file_put_contents($locale_stpl_path, $_POST['text']);
                // Save revision
                db()->INSERT('revisions', [
                    'user_id' => (int) (MAIN_TYPE_USER ? main()->USER_ID : main()->ADMIN_ID),
                    'object_name' => _es('locale_stpl'),
                    'object_id' => _es($STPL_NAME),
                    'old_text' => _es($text),
                    'new_text' => _es($_POST['text']),
                    'date' => time(),
                    'ip' => common()->get_ip(),
                    'comment' => _es('saved into file: ' . $locale_stpl_path),
                ]);
                // Success output
                $result = 'Saved successfully';
            }
            return print $result;
        }
        // Show template contents by default
        return print $text;
    }

    /**
     * AJAX-based method edit selected tooltip.
     */
    public function edit_tip()
    {
        no_graphics(true);
        if ( ! DEBUG_MODE || ! tpl()->ALLOW_INLINE_DEBUG) {
            return print 'Access denied';
        }
        $CUR_LOCALE = conf('language');

        if (isset($_POST['text']) && isset($_POST['name'])) {
            $A = db()->query_fetch('SELECT * FROM ' . db('tips') . " WHERE name='" . $_POST['name'] . "' AND locale='" . $CUR_LOCALE . "'");
            if ( ! $A) {
                db()->INSERT('tips', [
                    'name' => _es($_POST['name']),
                    'locale' => _es($CUR_LOCALE),
                    'text' => _es($_POST['text']),
                    'type' => 1,
                    'active' => 1,
                ]);
            } else {
                db()->UPDATE('tips', [
                    'text' => _es($_POST['text']),
                ], "name='" . $_POST['name'] . "' AND locale='" . $CUR_LOCALE . "'");
            }
        }
        cache_del('tips');
        echo 'Saved successfully';
    }
}
