<?php

class yf_notifications {
    var $salt = 'spfiprt34593502riweprir';
//	var $_CACHE_CHECK_TTL = 1;
// todo: cache
	function check() {
		main()->NO_GRAPHICS = true;
		$out = [];
		
		$online_class = _class('online_users','classes/');
		$online_class->process();
		// user part
        $R = db()->get_all("SELECT `id`,`notification_id` FROM `".db('notifications_receivers')."` WHERE `receiver_id`='".$online_class->online_user_id."' AND `receiver_type`='".$online_class->online_user_type."' AND `is_read`=0");
        $notifications_ids = [];
        foreach ((array)$R as $A) {
            $notifications_ids[$A['id']] = $A['notification_id'];
        }
        if (count((array)$notifications_ids) != 0) {
            $notifications = db()->get_all("SELECT `id`,`title`,`content`,`add_date` FROM `".db('notifications')."` WHERE `id` IN (".implode(",",$notifications_ids).")");
            $add_info = db()->get_all("SELECT `id`,`text`,`url` FROM `".db('notifications_receivers_add_info')."` WHERE `id` IN (".implode(",",array_keys($notifications_ids)).")");
            foreach ($notifications_ids as $k=>$v) {
                $out[$k] = [
                    'id'            => $k,
                    'title'         => $notifications[$v]['title'],
                    'content'       => $notifications[$v]['content'],
                    'add_date'      => $notifications[$v]['add_date'],
                    'text'          => $add_info[$k]['text'],
                    'url'           => $add_info[$k]['url'] != '' ? process_url($add_info[$k]['url']."&notification_id=".$k) : '',
                ];
            }
        }
		echo json_encode($out);
		exit;
	}
	
	function read() {
		$online_class = _class('online_users','classes/');
		$online_class->process();
		
		main()->NO_GRAPHICS = true;
		db()->query("UPDATE `".db('notifications_receivers')."` SET `is_read`=1 WHERE `receiver_id`='".$online_class->online_user_id."' AND `receiver_type`='".$online_class->online_user_type."' AND `id`=".intval($_POST['id']));
		echo "OK";
		exit;
	}
    
	function _prepare () {
        if (intval($_GET['notification_id']) != 0) {
            db()->query("UPDATE `".db('notifications_receivers')."` SET `is_read`=1 WHERE `receiver_id`='".intval(main()->USER_ID)."' AND `receiver_type`='user_id' AND `id`=".intval($_GET['notification_id']));
        }
        if (conf('IS_AJAX')) return false;
		$tpl = file_get_contents(pathinfo(__FILE__,PATHINFO_DIRNAME)."/../templates/user/notifications/notifications_js.stpl");
		$func_name = "url_".main()->type;
		$obj_name = main()->type == 'admin' ? 'notifications_admin' : 'notifications';
		$replace = [
			"url_check" => $func_name("/{$obj_name}/check"),
			"url_read" => $func_name("/{$obj_name}/read"),
		];
		js(tpl()->parse_string($tpl, $replace));		
		css("//cdnjs.cloudflare.com/ajax/libs/pnotify/2.0.0/pnotify.all.min.css");
		js("//cdnjs.cloudflare.com/ajax/libs/pnotify/2.0.0/pnotify.all.min.js");
	}
    
    function _add_receiver_user($name, $user_id, $text = '', $url = '') {
        $notification_id = db()->get_one("SELECT `id` FROM `".db('notifications')."` WHERE `id`='"._es($name)."' 
                                        OR (`is_common_template`=1 AND `template_alias` = '"._es($name)."')");
        if (intval($notification_id) == 0) return false;
        $hash = md5($url.$text.intval($notification_id).intval($user_id).$this->salt);        
        $A = db()->get("SELECT * FROM `".db('notifications_receivers')."` WHERE `hash`='{$hash}'");
        if (!empty($A)) {
            if ($A['is_read'] == 1) {
                db()->query("UPDATE `".db('notifications_receivers')."` SET `is_read`=0 WHERE `hash`='{$hash}'");
                return true;
            } else {
                return false;
            }
        }
        
        
        db()->insert(db('notifications_receivers'), [
            'notification_id'   => intval($notification_id),
            'receiver_type'     => 'user_id',
            'receiver_id'       => intval($user_id),
            'is_read'           => 0,
            'hash'              => $hash,
        ]);
        
        if (($text != '') || ($url != '')) {
            $id = db()->insert_id();
            db()->replace(db('notifications_receivers_add_info'), [
                'id'    => intval($id),
                'text'  => _es($text),
                'url'   => _es($url),
            ]);
        }
        return true;
    }
	
}

	
