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
	public $skip = [
#		'cache:*',
		'channel-subscribe:*',
		'channel-subscribe-ts:*',
		'channels_by_socket:*',
		'socket_by_user:*',
	];

	/**
	*/
	function _init() {
		$this->instances = [
			'redis_default' => redis(),
			'redis_cache' => strpos(strtolower(cache()->DRIVER), 'redis') !== false ? cache()->_driver->_connection : null,
		];
	}

	/**
	*/
	function show() {
		$i = preg_replace('~[^a-z0-9_]+~ims', '', trim($_GET['i']));
		if (!$i || !isset($this->instances[$i])) {
			return implode(PHP_EOL, array_map(function($in){ return a('/@object/?i='.$in, $in, 'fa fa-cog', $in, '', ''); }, array_keys($this->instances)));
		}
		$r = &$this->instances[$i];

		$data = [];

		$plen = strlen(REDIS_PREFIX);
		foreach((array)$r->keys('*') as $key) {
			if (strpos($key, REDIS_PREFIX) === 0) {
				$key = substr($key, $plen + 1);
			}
			if (wildcard_compare($this->skip, $key)) {
				continue;
			}
			$data[$key]['id'] = $key;
			$data[$key]['type'] = $this->types[$r->type($key)];
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

		$table = table($data, ['condensed' => true, 'pager_records_on_page' => 10000])
			->check_box('id')
			->text('id', ['desc' => 'key'/*, 'transform' => function($in){ return '<b>'.$in.'</b>'; }*/, 'link' => url('/@object/edit/?id=%id&i='.$i)])
			->text('type')
			->text('len')
			->text('ttl')
#			->btn_edit(['btn_no_text' => 1, 'no_ajax' => 1, 'link' => url('/@object/edit/?id=%id&i='.$i)])
#			->btn_delete(['btn_no_text' => 1, 'no_ajax' => 1])
		;

		$info = $r->info();
		ksort($info);

		$config = $r->config('get', '*');
		ksort($config);

		return '<div class="col-md-8"><h2>'.$i.'</h2>'.$table.'</div>'
			. '<div class="col-md-4"><h2>Info</h2>'._class('html')->simple_table($info).'</div>'
			. '<div class="col-md-4"><h2>Config</h2>'._class('html')->simple_table($config, ['key' => ['extra' => ['width' => '40%']]]).'</div>'
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