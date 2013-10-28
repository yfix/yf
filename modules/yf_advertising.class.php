<?php

class yf_advertising {

    function _show($params = array()) {

        // Set cookie
        if (empty($_COOKIE['advertise']) && $_GET['ads_places'] == 1) {
            $_COOKIE['advertise'] = setcookie("advertise", $_SERVER['HTTP_HOST'], time()+3600, '/');
        // Unset cookie
        }elseif($_GET['ads_places'] == 2 && !empty($_COOKIE['advertise'])){
            setcookie("advertise", "", time()-3600,'/');
            unset($_COOKIE['advertise']);
        }
        //  if isset cookie - return advertising buttons
        if (!empty($_COOKIE['advertise'])) {
			$admin_link = "http://".$_SERVER['HTTP_HOST']."/admin/?object=manage_advertising&action=listing&ad=".$params['ad'];
            return '<a href="'.$admin_link.'" class="btn advertising">ADVERTISING SPACE</a>';
        }
        $CACHE_NAME = 'advertise_cache';
        $ads = cache_get($CACHE_NAME);
        if (empty($ads) && !empty($GLOBALS['_CACHE_'.$CACHE_NAME])) {
            $ads = $GLOBALS['_CACHE_'.$CACHE_NAME];
        }
        if (empty($ads)) {
            $Q = db()->query("SELECT * FROM `".db('advertising')."` WHERE `active`=1 ");
            while ($A = db()->fetch_assoc($Q)) {
                $ads[$A['id']] = $A;
            }
            cache_set($CACHE_NAME, $ads, 300);
            $GLOBALS['_CACHE_'.$CACHE_NAME] = $ads;
        }

        foreach ((array)$ads as $k=>$v) {
            if ($v['ad'] != $params['ad']) {
                unset($ads[$k]);
            }
        }

        $matched_ids = array();
        $skipped_ids = array();
        foreach ((array)$ads as $ad) {
            // check by language
            if (!in_array($ad['id'],$skipped_ids)) {
                if ($ad['langs'] == '') {
                    $matched_ids[$ad['id']] = intval($matched_ids[$ad['id']]);
                } else {
                    if (in_array(DEFAULT_LANG, explode(",",$ad['langs']))) {
                        $matched_ids[$ad['id']] = intval($matched_ids[$ad['id']])+10;
                    } else {
                        $skipped_ids[$ad['id']] = $ad['id'];
                    }
                }
            }
            // check by start date
            if (!in_array($ad['id'],$skipped_ids)) {
                if ($ad['date_start'] == 0) {
                    $matched_ids[$ad['id']] = intval($matched_ids[$ad['id']]);
                } else {
                    if ($_SERVER['REQUEST_TIME']>=$ad['date_start']) {
                        $matched_ids[$ad['id']] = intval($matched_ids[$ad['id']])+10;
                    } else {
                        $skipped_ids[$ad['id']] = $ad['id'];
                    }
                }
            }
            // check by end date
            if (!in_array($ad['id'],$skipped_ids)) {
                if ($ad['date_end'] == 0) {
                    $matched_ids[$ad['id']] = intval($matched_ids[$ad['id']]);
                } else {
                    if ($_SERVER['REQUEST_TIME']<=$ad['date_end']) {
                        $matched_ids[$ad['id']] = intval($matched_ids[$ad['id']])+10;
                    } else {
                        $skipped_ids[$ad['id']] = $ad['id'];
                    }
                }
            }
            // check by current category (for object=>programs / action=>show
            if (!in_array($ad['id'],$skipped_ids)) {
                if ($ad['cat_ids'] == 0) {
                    $matched_ids[$ad['id']] = intval($matched_ids[$ad['id']]);
                } else {
                    if (($_GET['object'] == 'programs') && ($_GET['action']=='show') && ($ad['cat_ids'] == $_GET['id'])) {
                        $matched_ids[$ad['id']] = intval($matched_ids[$ad['id']])+10;
                    } else {
                        $skipped_ids[$ad['id']] = $ad['id'];
                    }
                }
            }
            //check by current country
            if (!in_array($ad['id'],$skipped_ids)) {
                if ($ad['langs'] == '') {
                    $matched_ids[$ad['id']] = intval($matched_ids[$ad['id']]);
                } else {
                    if (in_array(common()->_get_country(), explode(",",$ad['user_countries']))) {
                        $matched_ids[$ad['id']] = intval($matched_ids[$ad['id']])+10;
                    } else {
                        $skipped_ids[$ad['id']] = $ad['id'];
                    }
                }
            }

        }

		arsort($matched_ids);
		foreach (array_keys($matched_ids) as $k) {
			if (!in_array($k,$skipped_ids)) {
				$out[] = $ads[$k]['html'];
			}		
		}
		$out_key = rand(0, count($out)-1);
		return $out[$out_key];
    }
}