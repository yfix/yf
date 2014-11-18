<?php

class yf_notifications {
//	var $_CACHE_CHECK_TTL = 1;
// todo: cache
	function check() {
		main()->NO_GRAPHICS = true;
		$out = array();
		
		$online_class = _class('online_users','classes/');
		$online_class->process();
		// user part
        $R = db()->get_all("SELECT `id`,`notification_id` FROM `".db('notifications_receivers')."` WHERE `receiver_id`='".$online_class->online_user_id."' AND `receiver_type`='".$online_class->online_user_type."' AND `is_read`=0");
        $notifications_ids = array();
        foreach ((array)$R as $A) {
            $notifications_ids[$A['id']] = $A['notification_id'];
        }
        if (count($notifications_ids) != 0) {
            $notifications = db()->get_all("SELECT `id`,`title`,`content`,`add_date` FROM `".db('notifications')."` WHERE `id` IN (".implode(",",$notifications_ids).")");
            $add_info = db()->get_all("SELECT `id`,`text`,`url` FROM `".db('notifications_receivers_add_info')."` WHERE `id` IN (".implode(",",array_keys($notifications_ids)).")");
            foreach ($notifications_ids as $k=>$v) {
                $out[$k] = array(
                    'id'            => $k,
                    'title'         => $notifications[$v]['title'],
                    'content'       => $notifications[$v]['content'],
                    'add_date'      => $notifications[$v]['add_date'],
                    'text'          => $add_info[$k]['text'],
                    'url'           => $add_info[$k]['url'] != '' ? process_url($add_info[$k]['url']) : '',
                );
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
        if (conf('IS_AJAX')) return false;
		$tpl = file_get_contents(pathinfo(__FILE__,PATHINFO_DIRNAME)."/../templates/user/notifications/notifications_js.stpl");
		$func_name = "url_".main()->type;
		$obj_name = main()->type == 'admin' ? 'notifications_admin' : 'notifications';
		$replace = array(
			"url_check" => $func_name("/{$obj_name}/check"),
			"url_read" => $func_name("/{$obj_name}/read"),
		);
		require_js(tpl()->parse_string($tpl, $replace));		
		require_css("//cdnjs.cloudflare.com/ajax/libs/pnotify/2.0.0/pnotify.all.min.css");
		require_js("//cdnjs.cloudflare.com/ajax/libs/pnotify/2.0.0/pnotify.all.min.js");
	}
    
    function _add_receiver_user($name, $user_id, $text = '', $url = '') {
        $notification_id = db()->get_one("SELECT `id` FROM `".db('notifications')."` WHERE `id`='"._es($name)."' 
                                        OR (`is_common_template`=1 AND `template_alias` = '"._es($name)."')");
        if (intval($notification_id) == 0) return false;
        db()->insert(db('notifications_receivers'), array(
            'notification_id'   => intval($notification_id),
            'receiver_type'     => 'user_id',
            'receiver_id'       => intval($user_id),
            'is_read'           => 0,
        ));
        
        if (($text != '') || ($url != '')) {
            $id = db()->insert_id();
            db()->insert(db('notifications_receivers_add_info'), array(
                'id'    => intval($id),
                'text'  => _es($text),
                'url'   => _es($url),
            ));
        }
        return true;
    }
	
}

	
