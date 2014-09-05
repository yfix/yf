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
        $R = db()->get_all("SELECT `notification_id` FROM `".db('notifications_receivers')."` WHERE `receiver_id`='".$online_class->online_user_id."' AND `receiver_type`='".$online_class->online_user_type."' AND `is_read`=0");
        $notifications = array();
        foreach ((array)$R as $A) {
            $notifications[] = $A['notification_id'];
        }
        if (count($notifications) != 0) {
            $out = db()->get_all("SELECT `id`,`title`,`content`,`add_date` FROM `".db('notifications')."` WHERE `id` IN (".implode(",",$notifications).")");
        }
		echo json_encode($out);
		exit;
	}
	
	function read() {
		$online_class = _class('online_users','classes/');
		$online_class->process();
		
		main()->NO_GRAPHICS = true;
		db()->query("UPDATE `".db('notifications_receivers')."` SET `is_read`=1 WHERE `receiver_id`='".$online_class->online_user_id."' AND `receiver_type`='".$online_class->online_user_type."' AND `notification_id`=".intval($_POST['id']));
		echo "OK";
		exit;
	}
    
	function _prepare () {
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
    
    function _add_receiver_user($name, $user_id) {
        $notification_id = db()->get_one("SELECT `id` FROM `".db('notifications')."` WHERE `id`='"._es($name)."' 
                                        OR (`is_common_template`=1 AND `template_alias` = '"._es($name)."')");
        if (intval($notification_id) == 0) return false;
        db()->query("REPLACE INTO `".db('notifications_receivers')."` (
                `notification_id`,
                `receiver_type`,
                `receiver_id`,
                `is_read`
            ) VALUES (
                '".intval($notification_id)."',
                'user_id',
                '".intval($user_id)."',
                0
            );");
        return true;
    }
	
}

	
