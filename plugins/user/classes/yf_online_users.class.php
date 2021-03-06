<?php

class yf_online_users
{
    public $_CACHE_UPDATE_TTL = 30;
    public $_CACHE_CLEANUP_TTL = 90;
    public $_ONLINE_TTL = 60;
    public $_COOKIE_TTL = 3600;

    public $_type = [
        'user_id_tmp',
        'user_id',
        'admin_id',
    ];

    public function _init()
    {
        $this->_type = array_combine($this->_type, $this->_type);
    }

    public function process()
    {
        list($this->online_user_id, $this->online_user_type) = $this->_set_user_id();
        if (main()->TRACK_ONLINE_STATUS) {
            $this->_update();
            $this->_cleanup();
        }
    }

    public function _set_user_id()
    {
        if ((int) ($_SESSION['admin_id']) != 0) {
            return [$_SESSION['admin_id'], 'admin_id'];
        }
        if ((int) (main()->USER_ID) != 0) {
            return [main()->USER_ID, 'user_id'];
        }
        if ((int) ($_COOKIE['user_id_tmp']) != 0) {
            return [$_COOKIE['user_id_tmp'], 'user_id_tmp'];
        }

        // todo: more 'smart' algorythm for user id generation
        setcookie('user_id_tmp', rand(), $_SERVER['REQUEST_TIME'] + $this->_COOKIE_TTL);

        return [$_COOKIE['user_id_tmp'], 'user_id_tmp'];
    }

    public function _is_online($user_ids, $user_type = null)
    {
        if (is_array($user_ids)) {
        } else {
            $user_ids = (int) $user_ids;
            if ($user_ids < 1) {
                return  null;
            }
            $user_ids = (array) $user_ids;
        }
        if (empty($this->_type[$user_type])) {
            $user_type = 'user_id';
        }
        $time = db()->table('users_online')->select('user_id', 'time')
            ->where('user_type', _es($user_type))
            ->where_in($user_id, 'user_ids')
            ->get_deep_array(1);
        $result = [];
        foreach ($user_ids as $user_id) {
            $user_id = (int) $user_id;
            $result[$user_id] = false;
            if ( ! empty($time[$user_id])) {
                $result[$user_id] = (time() - $this->_ONLINE_TTL) < $time[$user_id];
            }
        }
        if (count($user_ids) == 1) {
            $result = reset($result);
        }
        return  $result;
    }

    public function _ip($options = null)
    {
        if ( ! empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = reset($ips);
        } else {
            $ip =
                   $_SERVER['HTTP_CLIENT_IP']
                ?: $_SERVER['HTTP_X_REAL_IP']
                ?: $_SERVER['REMOTE_ADDR'];
        }
        $result = trim($ip);
        return  $result;
    }

    public function _update()
    {
        $cache_name = __CLASS__ . '|' . __FUNCTION__ . '|' . $this->online_user_id . '|' . $this->online_user_type;
        if (cache()->get($cache_name) != 'OK' && (int) ($this->online_user_id) != 0) {
            db()->replace_safe('users_online', [
                'user_id' => $this->online_user_id,
                'user_type' => $this->online_user_type,
                'time' => $_SERVER['REQUEST_TIME'],
            ]);
            cache()->set($cache_name, 'OK', $this->_CACHE_UPDATE_TTL);
        }
        // details not cached for current url to be shown
        if (main()->TRACK_ONLINE_DETAILS && ! (strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest' || ! empty($_GET['ajax_mode'])) && (int) ($this->online_user_id) != 0) {
            $ip = $this->_ip();
            db()->replace_safe('users_online_details', [
                'user_id' => $this->online_user_id,
                'user_type' => $this->online_user_type,
                'time' => $_SERVER['REQUEST_TIME'],
                'session_id' => session_id(),
                'url' => 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'],
                'user_agent' => $_SERVER['HTTP_USER_AGENT'],
                'ip' => $ip,
            ]);
        }
    }

    public function _cleanup()
    {
        // todo: queued
        $cache_name = __CLASS__ . '|' . __FUNCTION__;
        if (cache()->get($cache_name) != 'OK') {
            $time = $_SERVER['REQUEST_TIME'] - $this->_ONLINE_TTL;
            db()->query('DELETE FROM ' . db('users_online') . ' WHERE `time`<' . $time);
            if (main()->TRACK_ONLINE_DETAILS) {
                db()->query('DELETE FROM ' . db('users_online_details') . ' WHERE `time`<' . $time);
            }
            cache()->set($cache_name, 'OK', $this->_CACHE_CLEANUP_TTL);
        }
    }
}
