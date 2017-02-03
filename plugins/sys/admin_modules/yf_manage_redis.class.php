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
		$i = preg_replace('~[^a-z0-9_-]+~ims', '', trim($_GET['i']));
		$g = preg_replace('~[^a-z0-9_-]+~ims', '', trim($_GET['g']));
		$t = preg_replace('~[^a-z0-9_-]+~ims', '', trim($_GET['t']));
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

		$table = table($data, ['condensed' => true, 'pager_records_on_page' => 10000])
			->check_box('id')
			->text('id', ['desc' => 'key', 'link' => url('/@object/edit/?id=%id&i='.$i)])
			->text('type')
			->text('len')
			->text('ttl')
			->btn_delete(['btn_no_text' => 1, 'no_ajax' => 1, 'class_add' => 'btn-danger'])
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
		$i = preg_replace('~[^a-z0-9_]+~ims', '', trim($_GET['i']));
		if (!$i || !isset($this->instances[$i])) {
			return js_redirect('/@object');
		}
		$r = &$this->instances[$i];
		$key = trim($_GET['id']);
		if (strpos($key, REDIS_PREFIX) === 0) {
			$key = substr($key, strlen(REDIS_PREFIX) + 1);
		}
		if (!$r->exists($key)) {
			return _e('No such key');
		}
		$type = $this->types[$r->type($key)];
		$len = '?';
		$data = '';
		if ($type == 'STRING') {
			$len = $r->strlen($key);
			$data = $r->get($key);
		} elseif ($type == 'HASH') {
			$len = $r->hlen($key);
			$data = $r->hgetall($key);
		} elseif ($type == 'SET') {
			$len = $r->scard($key);
			$data = $r->smembers($key);
		}
		if (is_array($data)) {
			$data = html()->simple_table($data);
		}
		$ttl = $r->ttl($key);
		return html()->simple_table([
			'key'	=> $key,
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
		$key = trim($_GET['id']);
		if (strpos($key, REDIS_PREFIX) === 0) {
			$key = substr($key, strlen(REDIS_PREFIX) + 1);
		}
		if (!$r->exists($key)) {
			return _e('No such key');
		}
		$type = $this->types[$r->type($key)];
// TODO
	}

	/**
	*/
	function ttl() {
// TODO
	}
}