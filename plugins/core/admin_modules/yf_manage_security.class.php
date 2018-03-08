<?php

class yf_manage_security {
	function show() {
		if (!main()->ADMIN_ID) {
			return _403();
		}
		$body = ''
			. '<div class="col-md-4">'
				. '<h3>'.t('Настройки безопасности').'</h3>'
				. $this->_security_settings()
			. '</div>'
/*
			. '<div class="col-md-8">'
				. '<h3>'.t('История входов в систему').'</h3>'
				. $this->_get_latest_auths()
			. '</div>'
*/
		;
		return '<div class="container-block row" style="margin-top:20px;">'.$body.'</div>';
	}

	/**
	*/
	function _security_settings() {
		asset('bfh-select');

		$user_id = main()->USER_ID;

		$country_names = db()->select('code','name')->from('geo_countries')->order_by('name ASC')->get_2d();
		$countries = [];
		foreach ((array)$country_names as $code => $name) {
			$countries[$code] = [
				'code' => $code,
				'name' => $name,
			];
		}
		$cur_ip = common()->get_ip();
		$cur_country = html()->_get_ip_country($cur_ip);
#		$cur_country = 'UA';

		$fields_ip = ['ip_whitelist', 'ip_blacklist'];
		$fields_country = ['country_whitelist', 'country_blacklist'];

		$a = db()->from('settings')->get_2d('item, value');
		if (is_post()) {
			foreach ($fields_ip as $k) {
				$tmp = [];
				foreach (explode(PHP_EOL, str_replace([' ',',',';'], PHP_EOL, trim($_POST[$k]))) as $v) {
					$v = trim($v);
					if (preg_match('~^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$~', $v)) {
						$tmp[$v] = $v;
					}
				}
				$_POST[$k] = implode(';', $tmp);
				if ($_POST[$k] && $a[$k] != $_POST[$k]) {
					$to_update[$k] = $_POST[$k];
				}
			}
			foreach ($fields_country as $k) {
				$tmp = [];
				foreach ((array)$_POST[$k] as $v) {
					$v = strtoupper(trim($v));
					if (isset($country_names[$v])) {
						$tmp[$v] = $v;
					}
				}
				$_POST[$k] = implode(';', $tmp);
			}
			$sql = [];
			$date = date('Y-m-d H:i:s');
			foreach (array_merge($fields_ip, $fields_country) as $k) {
				$v = $_POST[$k];
				if (!$v || $v === $a[$k]) {
					continue;
				}
				$sql[$k] = [
					'user_id'	=> $user_id,
					'key'		=> $k,
					'value'		=> $v,
					'date'		=> $date,
				];
				db()->insert_safe('user_settings_changelog', [
					'date'		=> $date,
					'user_id'	=> $user_id,
					'key'		=> $k,
					'val_old'	=> $a[$k],
					'val_new'	=> $v,
					'ip'		=> $cur_ip,
					'ua'		=> $_SERVER['HTTP_USER_AGENT'],
				]);
			}
			if ($sql) {
				db()->replace_safe('user_settings', $sql);
			}
			$a = (array)$_POST + (array)$a;
		}
		foreach ($fields_ip as $k) {
			$a[$k] = implode(PHP_EOL, explode(';', $a[$k]));
		}
		foreach ($fields_country as $k) {
			$tmp = [];
			foreach (explode(';', $a[$k]) as $v) {
				$tmp[$v] = $v;
			}
			$a[$k] = $tmp;
		}
		return form($a, ['autocomplete' => 'off', 'class' => 'form-vertical'])
			->container('Текущий IP адрес: <b>'.$cur_ip.'</b>')
			->container('Текущая страна: <b>'.($cur_country ? html()->icon('bfh-flag-'.$cur_country). $country_names[$cur_country] : 'Не определено').'</b>')
			->container('<br />')
			->textarea('ip_whitelist', 'Белый список IP адресов', ['label_tip' => 'security.ip_whitelist'])
			->textarea('ip_blacklist', 'Черный список IP адресов', ['label_tip' => 'security.ip_blacklist'])
			->chosen_box('country_whitelist', $country_names, ['multiple' => true, 'desc' => 'Белый список стран', 'label_tip' => 'security.country_whitelist'])
			->chosen_box('country_blacklist', $country_names, ['multiple' => true, 'desc' => 'Черный список стран', 'label_tip' => 'security.country_blacklist'])
			->container('<br />')
#			->yes_no_box('2factor_auth')
			->save();
	}

	/**
	*/
	function _get_latest_auths() {
		asset('bfh-select');
		$user_id = main()->USER_ID;
		return table(db()->from('log_auth')->whereid($user_id, 'user_id')->order_by('date DESC'), [
				'condensed' => true,
				'no_header' => true,
				'pager_records_on_page' => 50,
				'pager_num_records' => 50,
				'no_pages' => 1,
			])
			->func('date', function($date) { return '<small>'._format_date($date, 'long').'</small>'; }, ['nowrap' => true])
			->func('ip', function($ip) { return html()->ip($ip); })
			->func('user_agent', function($ua) { return '<small>'.$ua.'</small>'; })
		;
	}

	/**
	*/
	function _get_ip_country ($ip) {
		if (!isset($this->_ip_to_country[$ip])) {
			$func = 'geoip_country_code_by_name';
			$this->_ip_to_country[$ip] = is_callable($func) ? $func($ip) : '';
		}
		return $this->_ip_to_country[$ip];
	}

	/**
	*/
	function _get_country_name ($code) {
		if (!isset($this->_country_names)) {
			$this->_country_names = db()->select('code','name')->from('geo_countries')->get_2d();
		}
		return $this->_country_names[$code];
	}
}