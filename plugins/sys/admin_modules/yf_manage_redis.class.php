<?php

class yf_manage_redis {

	public $types = [
		Redis::REDIS_STRING => 'STRING',
		Redis::REDIS_SET => 'SET',
		Redis::REDIS_LIST => 'LIST',
		Redis::REDIS_ZSET => 'ZSET',
		Redis::REDIS_HASH => 'HASH',
		Redis::REDIS_NOT_FOUND => '?',
	];
	public $auto_skip_count = 10;

	/**
	*/
	function _init() {
		$i_default = redis();
		$i_cache = strpos(strtolower(cache()->DRIVER), 'redis') !== false ? cache()->_driver->_connection : null;
		$are_same = function($i1, $i2) {
			if (!is_object($i1) || !is_object($i2)) {
				return false;
			}
			foreach (['driver','host','port','prefix'] as $param) {
				if ($i1->$param !== $i2->$param) {
					return false;
				}
			}
			return true;
		};
		$this->instances = array_filter([
			'redis_default' => $i_default,
			'redis_cache' => $are_same($i_default, $i_cache) ? null : $i_cache,
		]);
	}

	/**
	*/
	function show() {
		$i = preg_replace('~[^a-z0-9_-]+~ims', '', trim($_GET['i'])); // instance
		$g = preg_replace('~[^a-z0-9_-]+~ims', '', trim($_GET['g'])); // group
		$t = preg_replace('~[^a-z0-9_-]+~ims', '', trim($_GET['t'])); // type
		if (!$i || !isset($this->instances[$i])) {
			if (count($this->instances) == 1) {
				return js_redirect('/@object/?i='.key($this->instances));
			} else {
				return implode(PHP_EOL, array_map(function($in){ return a('/@object/?i='.$in, $in, 'fa fa-cog', $in, '', ''); }, array_keys($this->instances)));
			}
		}
		$r = &$this->instances[$i];

		$data = [];

		$plen = strlen(REDIS_PREFIX);
		$keys = $r->keys('*');
		$groups = [];
		foreach((array)$keys as $key) {
			if (strpos($key, REDIS_PREFIX) === 0) {
				$key = substr($key, $plen + 1);
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
			$filters[] = a('/@object/?i='.$i, 'Clear filter', 'fa fa-close', '', 'btn-primary', '');
		}
		foreach ((array)$groups as $name => $count) {
			if ($count > 1) {
				$filters[] = a('/@object/?i='.$i.'&g='.urlencode($name), '', 'fa fa-filter', $name.'&nbsp;('.$count.')', '', '');
			} else {
				unset($groups[$name]);
			}
			if ($count > $this->auto_skip_count) {
				$skip[] = $name.':*';
			}
		}
		$avail_types = [];
		foreach((array)$keys as $key) {
			if (strpos($key, REDIS_PREFIX) === 0) {
				$key = substr($key, $plen + 1);
			}
			if ($g) {
				if (strpos($key, $g.':') !== 0) {
					continue;
				}
			} elseif ($skip && wildcard_compare($skip, $key)) {
				continue;
			}
			$type = $this->types[$r->type($key)];
			if ($t && strtoupper($type) != strtoupper($t)) {
				continue;
			}
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
		foreach($avail_types as $type => $count) {
			$filters[] = a('/@object/?i='.$i.'&t='.strtolower($type), '', 'fa fa-cog', $type.'&nbsp;('.$count.')', 'btn-info', '');
		}

		$table = table($data, ['condensed' => true, 'hide_empty' => true, 'pager_records_on_page' => 10000])
			->form(url('/@object/edit/?i='.$i))
			->check_box('id')
			->text('id', ['desc' => 'key', 'link' => url('/@object/edit/?id=%id&i='.$i)])
			->text('type')
			->text('len')
			->text('ttl')
			->btn_delete(['btn_no_text' => 1, 'no_ajax' => 1, 'class_add' => 'btn-danger', 'link' => url('/@object/delete/?&i='.$i.'&num=%id')])
			->footer_submit('mass_delete', ['class' => 'btn btn-xs btn-danger', 'icon' => 'fa fa-trash'])
		;

		$info = $r->info();
		ksort($info);

		$config = $r->config('get', '*');
		ksort($config);

		return ($filters ? '<div class="col-md-12">'.implode(' ', $filters).'</div>' : '')
			. '<div class="col-md-6"><h2>'.$i.' ('.count($keys).')</h2>'.$table.'</div>'
			. '<div class="col-md-3"><h2>Config</h2>'.html()->simple_table($config, ['val' => ['extra' => ['width' => '40%']]]).'</div>'
			. '<div class="col-md-3"><h2>Info</h2>'.html()->simple_table($info, ['val' => ['extra' => ['width' => '40%']]]).'</div>'
		;
	}

	/**
	*/
	function edit() {
		if (is_post() && isset($_POST['mass_delete']) && $_POST['id']) {
			$_GET['action'] = 'delete';
			return $this->delete();
		}
		$i = preg_replace('~[^a-z0-9_]+~ims', '', trim($_GET['i']));
		if (!$i || !isset($this->instances[$i])) {
			return js_redirect('/@object');
		}
		$r = &$this->instances[$i];
		$id = trim($_GET['id']);
		if (strpos($id, REDIS_PREFIX) === 0) {
			$id = substr($id, strlen(REDIS_PREFIX) + 1);
		}
		if (!$r->exists($id)) {
			return _e('No such key');
		}
		$type = $this->types[$r->type($id)];
		$len = '?';
		$data = '';
		if ($type == 'STRING') {
			$len = $r->strlen($id);
			$data = $r->get($id);
			$data = '<pre style="background:black; color:white; font-weight:bold;">'._prepare_html($data).'</pre>'
				. (in_array(substr($data, 0, 1), ['{','[']) ? 'JSON decoded:<pre style="background:black; color:white; font-weight:bold;">'.var_export(json_decode($data, true), true).'</pre>' : '');
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
# TODO: save
		if (is_array($data)) {
			$tmp = [];
			foreach ($data as $k => $v) {
				$auto_date = (is_numeric($v) && substr($v, 0, 2) == '14' && in_array(strlen($v), [10,12,13,14]) ? '&nbsp;<i class="text-info">('.date('Y-m-d H:i:s', substr($v, 0, 10)).')</i>' : '');
				$tmp[] = [
					'id' => $k,
					'val' => _prepare_html($v). $auto_date,
				];
			}
			$data = $tmp;
			unset($tmp);
			$data = table($data, ['condensed' => true, 'hide_empty' => true, 'pager_records_on_page' => 10000])
				->form(url('/@object/edit/?i='.$i.'&id='.$id))
				->check_box('id')
				->text('id', ['desc' => 'key', 'link' => url('/@object/edit/?&i='.$i.'&id='.$_GET['id'].'&num=%id')])
				->text('val')
				->btn_delete(['btn_no_text' => 1, 'no_ajax' => 1, 'class_add' => 'btn-danger', 'link' => url('/@object/delete/?&i='.$i.'&id='.$_GET['id'].'&num=%id')])
				->footer_submit('mass_delete', ['class' => 'btn btn-xs btn-danger', 'icon' => 'fa fa-trash'])
			;
		}
		$ttl = $r->ttl($id);
		return html()->simple_table([
			'key'	=> $id,
			'type'	=> $type,
			'len'	=> $len,
			'ttl'	=> $ttl,
		]). '<br>'. $data;
	}

	/**
	*/
	function delete() {
		$i = preg_replace('~[^a-z0-9_]+~ims', '', trim($_GET['i']));
		if (!$i || !isset($this->instances[$i])) {
			return js_redirect('/@object');
		}
		$r = &$this->instances[$i];
		$id = trim($_GET['id']);
		if (strpos($id, REDIS_PREFIX) === 0) {
			$id = substr($id, strlen(REDIS_PREFIX) + 1);
		}
		$keys_to_del = [];
		if ($id) {
			// Submit mass_delete from edit
			if (is_post()) {
				foreach ((array)$_POST['id'] as $k => $tmp) {
					$k = trim($k);
					if (strpos($k, REDIS_PREFIX) === 0) {
						$k = substr($k, strlen(REDIS_PREFIX) + 1);
					}
					if (strlen($k)) {
						$keys_to_del[$k] = $k;
					}
				}
			// Click on delete link from edit
			} elseif (isset($_GET['num'])) {
				$k = trim($_GET['num']);
				if (strpos($k, REDIS_PREFIX) === 0) {
					$k = substr($k, strlen(REDIS_PREFIX) + 1);
				}
				if (strlen($k)) {
					$keys_to_del[$k] = $k;
				}
			}
			$this->_do_delete($r, $id, $keys_to_del);
			return js_redirect('/@object/edit/?i='.$i.'&id='.$id);
		} else {
			// Submit mass_delete from keys listing
			if (is_post()) {
				foreach ((array)$_POST['id'] as $k => $tmp) {
					$k = trim($k);
					if (strpos($k, REDIS_PREFIX) === 0) {
						$k = substr($k, strlen(REDIS_PREFIX) + 1);
					}
					if (strlen($k)) {
						$keys_to_del[$k] = $k;
					}
				}
				$this->_do_delete($r, $id, $keys_to_del);
			// Click on delete link from keys listing
			} elseif (isset($_GET['num'])) {
				$k = trim($_GET['num']);
				if (strpos($k, REDIS_PREFIX) === 0) {
					$k = substr($k, strlen(REDIS_PREFIX) + 1);
				}
				if (strlen($k)) {
					$r->del($k);
				}
			}
			return js_redirect('/@object/?i='.$i);
		}
	}

	/**
	*/
	function _do_delete($r, $id, $keys_to_del = []) {
		if (!$keys_to_del) {
			return false;
		}
		if ($id) {
			$type = $this->types[$r->type($id)];
			if ($type == 'STRING') {
				$r->del($keys_to_del);
			} elseif ($type == 'HASH') {
				foreach ((array)$keys_to_del as $k) {
					$r->hdel($id, $k);
				}
			} elseif ($type == 'SET') {
				foreach ((array)$keys_to_del as $k) {
					$r->srem($id, $k);
				}
			} elseif ($type == 'LIST') {
				// https://groups.google.com/forum/#!topic/redis-db/c-IpJ0YWa9I
				foreach ((array)$keys_to_del as $k) {
					$r->lset($id, $k, '__deleted__');
				}
				$r->lrem($id, '__deleted__');
			}
		} else {
			$r->del($keys_to_del);
		}
		return true;
	}

	/**
	*/
	function ttl() {
// TODO
	}
}