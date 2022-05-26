<?php

class yf_manage_redis
{
    public $types = [
        Redis::REDIS_STRING => 'STRING',
        Redis::REDIS_SET => 'SET',
        Redis::REDIS_LIST => 'LIST',
        Redis::REDIS_ZSET => 'ZSET',
        Redis::REDIS_HASH => 'HASH',
        Redis::REDIS_NOT_FOUND => '?',
    ];
    public $auto_skip_count = 10;
    public $CONFIG_SKIP_EMPTY = true;
    public $INFO_SKIP_EMPTY = true;

    public function _init()
    {
        $i_default = redis();
        $i_cache = strpos(strtolower(cache()->DRIVER), 'redis') !== false ? cache()->_driver->_connection : null;
        if (ini_get('session.save_handler') == 'redis' && preg_match('~tcp://(?P<host>[a-z0-9_-]+):(?P<port>[0-9]+)~ims', ini_get('session.save_path'), $m)) {
            $i_sessions = redis()->factory([
                'REDIS_HOST' => $m['host'],
                'REDIS_PORT' => $m['port'],
                'REDIS_PREFIX' => 'PHPREDIS_SESSION',
            ]);
        }
        if ($this->_get_conf('REDIS_LOG_HOST')) {
            $i_log = redis()->factory([
                'REDIS_HOST' => $this->_get_conf('REDIS_LOG_HOST'),
                'REDIS_PORT' => $this->_get_conf('REDIS_LOG_PORT'),
                'REDIS_PREFIX' => $this->_get_conf('REDIS_LOG_PREFIX'),
            ]);
        }
        if ($this->_get_conf('REDIS_QUEUE_HOST')) {
            $i_queue = redis()->factory([
                'REDIS_HOST' => $this->_get_conf('REDIS_QUEUE_HOST'),
                'REDIS_PORT' => $this->_get_conf('REDIS_QUEUE_PORT'),
                'REDIS_PREFIX' => $this->_get_conf('REDIS_QUEUE_PREFIX'),
            ]);
        }
        if ($this->_get_conf('REDIS_PUBSUB_HOST')) {
            $i_pubsub = redis()->factory([
                'REDIS_HOST' => $this->_get_conf('REDIS_PUBSUB_HOST'),
                'REDIS_PORT' => $this->_get_conf('REDIS_PUBSUB_PORT'),
                'REDIS_PREFIX' => $this->_get_conf('REDIS_PUBSUB_PREFIX'),
            ]);
        }
        if ($this->_get_conf('REDIS_CONF_HOST')) {
            $i_conf = redis()->factory([
                'REDIS_HOST' => $this->_get_conf('REDIS_CONF_HOST'),
                'REDIS_PORT' => $this->_get_conf('REDIS_CONF_PORT'),
                'REDIS_PREFIX' => $this->_get_conf('REDIS_CONF_PREFIX'),
            ]);
        }
        $are_same = function ($i1, $i2) {
            if ( ! is_object($i1) || ! is_object($i2)) {
                return false;
            }
            foreach (['driver', 'host', 'port', 'prefix'] as $param) {
                if ($i1->config[$param] !== $i2->config[$param]) {
                    return false;
                }
            }
            return true;
        };
        $this->instances = array_filter([
            'redis_default' => $i_default,
            'redis_cache' => $are_same($i_default, $i_cache) ? null : $i_cache,
            'redis_sessions' => $are_same($i_default, $i_sessions) ? null : $i_sessions,
            'redis_log' => $are_same($i_default, $i_log) ? null : $i_log,
            'redis_queue' => $are_same($i_default, $i_queue) ? null : $i_queue,
            'redis_pubsub' => $are_same($i_default, $i_pubsub) ? null : $i_pubsub,
            'redis_conf' => $are_same($i_default, $i_conf) ? null : $i_conf,
        ]);
    }

    /**
     * @param mixed $name
     * @param null|mixed $default
     */
    public function _get_conf($name, $default = null, array $params = [])
    {
        global $CONF;
        if (isset($params[$name])) {
            return $params[$name];
        }
        $from_env = getenv($name);
        if ($from_env !== false) {
            return $from_env;
        }
        if (isset($CONF[$name])) {
            $from_conf = $CONF[$name];
            return $from_conf;
        }
        if (defined($name) && ($val = constant($name)) != $name) {
            return $val;
        }
        return $default;
    }

    public function show()
    {
        $i = $this->_cleanup($_GET['i']); // instance
        $g = $this->_cleanup($_GET['g']); // group
        $t = $this->_cleanup($_GET['t']); // type
        $page = (int) $_GET['page'] ?: 1; // page
        $per_page = 2000;
        if ( ! $i || ! isset($this->instances[$i])) {
            if (count((array) $this->instances) == 1) {
                return js_redirect('/@object/?i=' . key($this->instances));
            }
            return implode(PHP_EOL, array_map(function ($in) {
                return a('/@object/?i=' . $in, $in, 'fa fa-cog', $in, '', '');
            }, array_keys($this->instances)));
        }
        $r = &$this->instances[$i];

        $data = [];

        $prefix = $r->prefix;
        $plen = strlen($prefix);

        $keys = $r->keys('*');
        // TODO:
        //		$r->_connection->setOption(Redis::OPT_SCAN, Redis::SCAN_RETRY);
        //		$keys = $r->_connection->scan($it, '*', $per_page);

        $pager = '';
        $total_keys = count((array) $keys);
        if ($total_keys >= $per_page) {
            $keys = array_slice($keys, $per_page * ($page - 1), $per_page * $page, true);
            list(, $pager) = common()->divide_pages('', '', '', $per_page, $total_keys);
        }

        $groups = [];
        foreach ((array) $keys as $key) {
            if ($plen && strpos($key, $prefix) === 0) {
                $key = substr($key, $plen);
            }
            if (false !== strpos($key, ':')) {
                $gname = strstr($key, ':', true);
                $groups[$gname]++;
            }
        }
        arsort($groups);
        $filters = [];
        $skip = [];
        if ($g || $t) {
            $filters[] = a('/@object/?i=' . $i, 'Clear filter', 'fa fa-close', '', 'btn-primary', '');
        }
        foreach ((array) $groups as $name => $count) {
            if ($count > 1) {
                $is_selected = $g && strtolower($g) == strtolower($name);
                $filters[] = a('/@object/?i=' . $i . '&g=' . urlencode($name), '', 'fa fa-filter', $name . '&nbsp;(' . $count . ')', $is_selected ? 'btn-warning' : '', '');
            } else {
                unset($groups[$name]);
            }
            if ($count > $this->auto_skip_count) {
                $skip[] = $name . ':*';
            }
        }
        $get_display_keys = function ($keys, $skip) use ($r, $g, $t, $plen) {
            $display_keys = [];
            $prefix = $r->prefix;
            foreach ((array) $keys as $key) {
                if ($plen && strpos($key, $prefix) === 0) {
                    $key = substr($key, $plen);
                }
                if ($g) {
                    if (strpos($key, $g . ':') !== 0) {
                        continue;
                    }
                } elseif ($skip && wildcard_compare($skip, $key)) {
                    continue;
                }
                $type = $this->types[$r->type($key)];
                if ($t && strtoupper($type) != strtoupper($t)) {
                    continue;
                }
                $display_keys[$key] = $type;
            }
            return $display_keys;
        };
        $display_keys = $get_display_keys($keys, $skip);
        if ($keys && ! $display_keys && $skip) {
            $skip = [];
            $display_keys = $get_display_keys($keys, $skip);
        }
        $avail_types = [];
        foreach ((array) $display_keys as $key => $type) {
            $avail_types[$type]++;
            $data[$key]['id'] = $key;
            $data[$key]['type'] = $type;
            $len = '?';
            if ($data[$key]['type'] == 'STRING') {
                $len = $r->strlen($key);
            } elseif ($data[$key]['type'] == 'HASH') {
                $len = $r->hlen($key);
            } elseif ($data[$key]['type'] == 'SET') {
                $len = $r->scard($key);
            } elseif ($data[$key]['type'] == 'LIST') {
                $len = $r->llen($key);
            }
            $data[$key]['len'] = $len;
            $data[$key]['ttl'] = $r->ttl($key);
            $data[$key]['ttl'] == -1 && $data[$key]['ttl'] = '';
        }
        ksort($data);
        arsort($avail_types);
        foreach ($avail_types as $type => $count) {
            $is_selected = $t && strtolower($t) == strtolower($type);
            $filters[] = a('/@object/?i=' . $i . '&t=' . strtolower($type), '', 'fa fa-cog', $type . '&nbsp;(' . $count . ')', $is_selected ? 'btn-warning' : 'btn-info', '');
        }

        $table = table($data, ['condensed' => true, 'hide_empty' => true, 'pager_records_on_page' => 10000])
            ->form(url('/@object/edit/?i=' . $i))
            ->check_box('id')
            ->text('id', ['desc' => 'key', 'link' => url('/@object/edit/?id=%id&i=' . $i)])
            ->text('type')
            ->text('len')
            ->text('ttl')
            ->btn_delete(['btn_no_text' => 1, 'no_ajax' => 1, 'class_add' => 'btn-danger', 'link' => url('/@object/delete/?&i=' . $i . '&num=%id')])
            ->footer_submit('mass_delete', ['class' => 'btn btn-xs btn-danger', 'icon' => 'fa fa-trash']);

        $info = $r->info();
        ksort($info);

        $config = $r->config('get', '*');
        ksort($config);
        if ($this->CONFIG_SKIP_EMPTY) {
            $config = array_filter($config, function ($v) {
                return ! empty($v) && $v !== -1;
            });
        }
        if ($this->INFO_SKIP_EMPTY) {
            $info = array_filter($info, function ($v) {
                return ! empty($v) && $v !== -1;
            });
        }

        $i_select = array_combine(array_keys($this->instances), array_keys($this->instances));
        $filters[] = '<div class="i_select col-md-1 pull-right">' . html()->chosen_box('i_select', $i_select, $i) . '</div>';
        jquery('
			var base_url = "' . url('/@object') . '";
			$("div.i_select select[name=i_select]").on("change", function(){
				window.location.href = base_url + "&i=" + $(this).val();
			})
		');

        return ($filters ? '<div class="col-md-12">' . implode(' ', $filters) . '</div>' : '')
            . '<div class="col-md-6"><h2>' . $i . ' <small>(' . ($pager ? count((array) $keys) . ' from total ' : '') . $total_keys . ')</small></h2>' . $pager . $table . '</div>'
            . '<div class="col-md-3"><h2>Config</h2>' . html()->simple_table($config, ['val' => ['extra' => ['width' => '40%']], 'very_condensed' => 1]) . '</div>'
            . '<div class="col-md-3"><h2>Info</h2>' . html()->simple_table($info, ['val' => ['extra' => ['width' => '40%']], 'very_condensed' => 1]) . '</div>';
    }


    public function edit()
    {
        if (is_post() && isset($_POST['mass_delete']) && $_POST['id']) {
            $_GET['action'] = 'delete';
            return $this->delete();
        }
        $i = $this->_cleanup($_GET['i']);
        if ( ! $i || ! isset($this->instances[$i])) {
            return js_redirect('/@object');
        }
        $r = &$this->instances[$i];
        $prefix = $r->prefix ?: (defined('REDIS_PREFIX') ? REDIS_PREFIX : '');
        $id = trim($_GET['id']);
        if (strpos($id, $prefix) === 0) {
            $id = substr($id, strlen($prefix) + 1);
        }
        if ( ! $r->exists($id)) {
            return _e('No such key');
        }
        $type = $this->types[$r->type($id)];
        $len = '?';
        $data = '';
        if ($type == 'STRING') {
            $len = $r->strlen($id);
            $data = $r->get($id);
            $like_json = in_array(substr($data, 0, 1), ['{', '[']);
            if ($like_json) {
                // $php_exported = _var_export(json_decode($data, true), true);
                $data = json_encode(json_decode($data), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            }
            $cur_url = url('/@object/edit/?i=' . $i . '&id=' . $id);
            $data = ($php_exported ? '<a href="' . $cur_url . '#json_pretty" class="btn btn-xs btn-info">RAW</a>&nbsp;&nbsp;<a href="' . $cur_url . '#php_export" class="btn btn-xs btn-info">PHP ARRAY</a><br><br>' : '')
                . '<pre id="json_pretty" style="background:black; color:white; font-weight:bold;">' . _prepare_html($data) . '</pre>'
                . ($php_exported ? 'JSON PHP VAR EXPORTED:<pre id="php_export" style="background:black; color:white; font-weight:bold;">' . $php_exported . '</pre>' : '');
        } elseif ($type == 'HASH') {
            $len = $r->hlen($id);
            $data = $r->hgetall($id);
        } elseif ($type == 'SET') {
            $len = $r->scard($id);
            $data = $r->smembers($id);
        } elseif ($type == 'LIST') {
            $len = $r->llen($id);
            $data = $r->lrange($id, 0, 10000);
        }
        // TODO: save
        if (is_array($data)) {
            $tmp = [];
            foreach ($data as $k => $v) {
                $auto_date = (is_numeric($v) && substr($v, 0, 2) == '14' && in_array(strlen($v), [10, 12, 13, 14]) ? '&nbsp;<i class="text-info">(' . date('Y-m-d H:i:s', substr($v, 0, 10)) . ')</i>' : '');
                $tmp[] = [
                    'id' => $k,
                    'val' => _prepare_html($v) . $auto_date,
                ];
            }
            $data = $tmp;
            unset($tmp);
            $data = table($data, ['condensed' => true, 'hide_empty' => true, 'pager_records_on_page' => 10000])
                ->form(url('/@object/edit/?i=' . $i . '&id=' . $id))
                ->check_box('id')
                ->text('id', ['desc' => 'key', 'link' => url('/@object/edit/?&i=' . $i . '&id=' . $_GET['id'] . '&num=%id')])
                ->text('val')
                ->btn_delete(['btn_no_text' => 1, 'no_ajax' => 1, 'class_add' => 'btn-danger', 'link' => url('/@object/delete/?&i=' . $i . '&id=' . $_GET['id'] . '&num=%id')])
                ->footer_submit('mass_delete', ['class' => 'btn btn-xs btn-danger', 'icon' => 'fa fa-trash']);
        }
        $ttl = $r->ttl($id);
        return html()->simple_table([
            'key' => $id,
            'type' => $type,
            'len' => $len,
            'ttl' => $ttl,
        ]) . '<br>' . $data;
    }


    public function delete()
    {
        $i = $this->_cleanup($_GET['i']);
        if ( ! $i || ! isset($this->instances[$i])) {
            return js_redirect('/@object');
        }
        $r = &$this->instances[$i];
        $prefix = $r->prefix;
        $plen = strlen($prefix);
        $id = trim($_GET['id']);
        if (strpos($id, $prefix) === 0) {
            $id = substr($id, $plen);
        }
        $keys_to_del = [];
        if ($id) {
            // Submit mass_delete from edit
            if (is_post()) {
                foreach ((array) $_POST['id'] as $k => $tmp) {
                    $k = trim($k);
                    if (strpos($k, $prefix) === 0) {
                        $k = substr($k, $plen);
                    }
                    if (strlen($k)) {
                        $keys_to_del[$k] = $k;
                    }
                }
                // Click on delete link from edit
            } elseif (isset($_GET['num'])) {
                $k = trim($_GET['num']);
                if (strpos($k, $prefix) === 0) {
                    $k = substr($k, $plen);
                }
                if (strlen($k)) {
                    $keys_to_del[$k] = $k;
                }
            }
            $this->_do_delete($r, $id, $keys_to_del);
            return js_redirect('/@object/edit/?i=' . $i . '&id=' . $id);
        }
        // Submit mass_delete from keys listing
        if (is_post()) {
            foreach ((array) $_POST['id'] as $k => $tmp) {
                $k = trim($k);
                if (strpos($k, $prefix) === 0) {
                    $k = substr($k, $plen);
                }
                if (strlen($k)) {
                    $keys_to_del[$k] = $k;
                }
            }
            $this->_do_delete($r, $id, $keys_to_del);
        // Click on delete link from keys listing
        } elseif (isset($_GET['num'])) {
            $k = trim($_GET['num']);
            if (strpos($k, $prefix) === 0) {
                $k = substr($k, $plen);
            }
            if (strlen($k)) {
                $r->del($k);
            }
        }
        return js_redirect('/@object/?i=' . $i);
    }

    /**
     * @param mixed $r
     * @param mixed $id
     * @param mixed $keys_to_del
     */
    public function _do_delete($r, $id, $keys_to_del = [])
    {
        if ( ! $keys_to_del) {
            return false;
        }
        if ($id) {
            $type = $this->types[$r->type($id)];
            if ($type == 'STRING') {
                $r->del($keys_to_del);
            } elseif ($type == 'HASH') {
                foreach ((array) $keys_to_del as $k) {
                    $r->hdel($id, $k);
                }
            } elseif ($type == 'SET') {
                foreach ((array) $keys_to_del as $k) {
                    $r->srem($id, $k);
                }
            } elseif ($type == 'LIST') {
                // https://groups.google.com/forum/#!topic/redis-db/c-IpJ0YWa9I
                foreach ((array) $keys_to_del as $k) {
                    $r->lset($id, $k, '__deleted__');
                }
                $r->lrem($id, '__deleted__');
            }
        } else {
            $r->del($keys_to_del);
        }
        return true;
    }


    public function ttl()
    {
        // TODO
    }

    private function _cleanup($in)
    {
        return preg_replace('~[^a-z0-9_-]+~ims', '', trim($in));
    }
}
