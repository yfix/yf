<?php


class yf_locale_editor_collect
{
    public function _init()
    {
        $this->_parent = module('locale_editor');
    }

    /**
     * Collect variables from app and framework source files.
     */
    public function collect()
    {
        $defaults = [
            'back_link' => url('/@object/vars'),
            'redirect_link' => url('/@object/vars'),
            'include_app' => 1,
            'include_framework' => 0,
            'find_php' => 1,
            'find_stpl' => 1,
            'find_angular' => 1,
            'include_admin' => 0,
            'min_length' => 3,
        ];
        foreach ((array) $defaults as $k => $v) {
            ! isset($a[$k]) && $a[$k] = $v;
        }
        $display_func = function () {
            return ! is_post();
        };
        return $this->_parent->_header_links() . '<div class="col-md-12"><br>' .
            form($a + (array) $_POST)
            ->validate(['min_length' => 'required'])
            ->on_validate_ok(function ($data, $e, $vr, $form) {
                return $this->_on_validate_ok($data, $form);
            })
            ->yes_no_box('include_app', ['display_func' => $display_func])
            ->yes_no_box('include_framework', ['display_func' => $display_func])
            ->yes_no_box('find_php', ['display_func' => $display_func])
            ->yes_no_box('find_stpl', ['display_func' => $display_func])
            ->yes_no_box('find_angular', ['display_func' => $display_func])
            ->yes_no_box('include_admin', ['display_func' => $display_func])
            ->number('min_length', ['class_add' => 'input-small', 'display_func' => $display_func])
            ->save_and_back('', ['desc' => 'Collect', 'display_func' => $display_func])
        . '</div>';
    }

    /**
     * @param mixed $params
     * @param mixed $form
     */
    public function _on_validate_ok($params = [], $form)
    {
        $vars_db = $this->_parent->_get_all_vars_from_db();
        $found_vars = $this->_parse_sources($params);

        $data_new = [];
        $data_existing = [];
        foreach ((array) $found_vars as $var => $files) {
            $locations = [];
            foreach ((array) $files as $file => $lines) {
                $locations[] = $file . ':' . $lines;
            }
            $var = trim($var);
            if (_strlen($var) < $params['min_length']) {
                continue;
            }
            $var_lower = _strtolower($var);
            $var_in_db = $vars_db[$var_lower];
            if (isset($var_in_db['var_id'])) {
                $data_existing[$var] = [
                    'value' => $var_lower,
                    'location' => implode('; ', $locations),
                    'id' => $var_in_db['var_id'],
                ];
                $stats['updated']++;
            } else {
                $data_new[$var_lower] = [
                    'value' => $var_lower,
                    'location' => implode('; ', $locations),
                ];
                $stats['inserted']++;
            }
        }
        // replace with insert/update to not change var ids
        if ($data_new) {
            db()->insert_safe('locale_vars', $data_new, ['ignore' => true]);
        }
        if ($data_existing) {
            db()->update_batch_safe('locale_vars', $data_existing);
        }
        $stats['updated'] && common()->message_success($stats['updated'] . ' existing variable(s) successfully updated');
        $stats['inserted'] && common()->message_success($stats['inserted'] . ' new variable(s) successfully inserted');
        ! $stats && common()->message_info('Collect done, nothing changed');

        $form->container(a(['href' => '/@object/@action', 'title' => 'Back', 'icon' => 'fa fa-arrow-left', 'class' => 'btn btn-primary btn-small', 'target' => '']), ['wide' => true]);
        $form->container($this->_parent->_pre_text(_prepare_html(_var_export($found_vars), 1)), ['wide' => true]);
    }

    /**
     * @param mixed $params
     */
    public function _parse_sources($params = [])
    {
        $files = [];
        if ($params['include_framework']) {
            $params['find_php'] && $files['framework']['php'] = $this->_scan_files('framework', 'php', $params);
            $params['find_stpl'] && $files['framework']['stpl'] = $this->_scan_files('framework', 'stpl', $params);
            $params['find_angular'] && $files['framework']['ng'] = $this->_scan_files('framework', 'ng', $params);
        }
        if ($params['include_app']) {
            $params['find_php'] && $files['app']['php'] = $this->_scan_files('app', 'php', $params);
            $params['find_stpl'] && $files['app']['stpl'] = $this->_scan_files('app', 'stpl', $params);
            $params['find_angular'] && $files['app']['ng'] = $this->_scan_files('app', 'ng', $params);
        }
        $remove_prefixes = [
            'framework' => $params['YF_PATH'] ?: YF_PATH,
            'app' => $params['APP_PATH'] ?: APP_PATH,
        ];
        $vars = [];
        foreach ((array) $files as $top => $types) {
            foreach ((array) $types as $type => $paths) {
                foreach ((array) $paths as $path) {
                    if ( ! $path) {
                        continue;
                    }
                    $short_path = str_replace($remove_prefixes, '', $path);
                    foreach ((array) $this->_collect_vars_in_file($path, $type, $params) as $var => $lines) {
                        $vars[$var][$short_path] = $lines;
                    }
                }
            }
        }
        $vars && ksort($vars);
        return $vars;
    }

    /**
     * @param mixed $top
     * @param mixed $type
     * @param mixed $params
     */
    public function _scan_files($top, $type, $params = [])
    {
        $dirs_map = [
            'framework' => $params['YF_PATH'] ?: YF_PATH,
            'app' => $params['APP_PATH'] ?: APP_PATH,
        ];
        $globs = [
            'php' => '{,plugins/*/}{classes,modules}/{*,*/*,*/*/*}.php',
            'stpl' => '{,plugins/*/,www/}{templates}/*/{*,*/*,*/*/*}.stpl',
            'ng' => '{,plugins/*/,www/}{templates}/*/{*,*/*,*/*/*}.{stpl,html}',
        ];
        $files = glob($dirs_map[$top] . '' . $globs[$type], GLOB_BRACE);
        foreach ((array) $files as $k => $file) {
            if (false !== strpos($file, '/test/')) {
                unset($files[$k]);
            } elseif ( ! $params['include_admin'] && false !== strpos($file, '/templates/admin/')) {
                unset($files[$k]);
            }
        }
        return $files;
    }

    /**
     * @param mixed $file
     * @param mixed $type
     * @param mixed $params
     */
    public function _collect_vars_in_file($file, $type, $params = [])
    {
        if ( ! $file) {
            return [];
        }
        $pspaces = '\s' . "\t";
        $pquotes = '"\'';
        $patterns_translate = [
            'php' => '~[\(\{\.,=' . $pspaces . ']+?' . 't' . '[' . $pspaces . ']*?\([' . $pspaces . ']*?(?P<var>\'[^\'$]+?\'|"[^"$]+?")~ims',
            'stpl' => '~\{t\(\s*["\']{0,1}(?P<var>.+?)["\']{0,1}\s*\)\}~ims',
            'ng' => '~\{\{[' . $pspaces . $pquotes . ']*(?P<var>[^\|\}]+?)[' . $pspaces . $pquotes . ']*\|[' . $pspaces . ']*' . 'translate' . '[' . $pspaces . ']*\}\}~is',
        ];
        // Quick tests
        $raw = file_get_contents($file);
        if ($type == 'stpl' && false === strpos($raw, '{t(')) {
            return [];
        } elseif ($type == 'ng' && false === strpos($raw, '{{')) {
            return [];
        } elseif ($type == 'php') {
            return $this->_php_vars($file, $params);
        }
        $vars = [];
        $farray = file($file);
        $matched = preg_match_all($patterns_translate[$type], implode(PHP_EOL, $farray), $m);
        if ( ! $matched || ! isset($m[0])) {
            return [];
        }
        foreach ((array) $m['var'] as $mnum => $var) {
            $lines = [];
            $var = trim(trim(trim($var), $pquotes));
            foreach ((array) $farray as $line_num => $line_text) {
                if (false === strpos($line_text, $m[0][$mnum])) {
                    continue;
                }
                $lines[] = $line_num + 1;
            }
            if ( ! $lines || ! strlen($var)) {
                continue;
            }
            if ($type == 'stpl') {
                $var = $this->_stpl_var_cleanup($var);
                if ( ! strlen($var)) {
                    continue;
                }
            }
            $var = _strtolower($var);
            $vars[$var] = implode(',', $lines);
        }
        return $vars;
    }

    /**
     * @param mixed $in
     */
    public function _stpl_var_cleanup($in)
    {
        $input = stripslashes(trim($in, '"\''));
        $args = [];
        // Complex case with substitutions
        if (preg_match('~(?P<text>.+?)["\']{1},[\s\t]*%(?P<args>[a-z]+.+)$~ims', $in, $m)) {
            foreach (explode(';%', $m['args']) as $arg) {
                $attr_name = $attr_val = '';
                if (false !== strpos($arg, '=')) {
                    list($attr_name, $attr_val) = explode('=', trim($arg));
                }
                $attr_name = trim(str_replace(["'", '"'], '', $attr_name));
                $attr_val = trim(str_replace(["'", '"'], '', $attr_val));
                $args['%' . $attr_name] = $attr_val;
            }
            $out = $m['text'];
        } else {
            $out = $in;
        }
        return $out;
    }

    /**
     * try tokenizer for php.
     * @param mixed $file
     * @param mixed $params
     */
    public function _php_vars($file, $params = [])
    {
        $code = file_get_contents($file);

        $GLOBALS['_locale_editor_collect_vars'] = [];

        require_php_lib('php_parser');
        $parser = (new PhpParser\ParserFactory())->create(PhpParser\ParserFactory::PREFER_PHP7);
        $stmts = $parser->parse($code);

        $traverser = new PhpParser\NodeTraverser();
        $traverser->addVisitor(
            new class() extends PhpParser\NodeVisitorAbstract {
                public function leaveNode(PhpParser\Node $node)
                {
                    if ( ! ($node instanceof PhpParser\Node\Expr\FuncCall)) {
                        return;
                    }
                    if ($node->name->parts[0] !== 't') {
                        return;
                    }
                    $arg = $node->args[0]->value;
                    if ( ! ($arg instanceof PhpParser\Node\Scalar\String_)) {
                        return;
                    }
                    $var = $arg->value;
                    $line = $arg->getAttribute('startLine');
                    $GLOBALS['_locale_editor_collect_vars'][$var][$line] = $line;
                }
            }
        );
        $stmts = $traverser->traverse($stmts);

        foreach ((array) $GLOBALS['_locale_editor_collect_vars'] as $var => $lines) {
            $var = _strtolower($var);
            $vars[$var] = implode(',', $lines);
        }
        unset($GLOBALS['_locale_editor_collect_vars']);

        return $vars;
    }
}
