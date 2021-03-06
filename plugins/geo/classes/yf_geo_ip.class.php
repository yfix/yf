<?php

/**
 * GEO to IP handler.
 *
 * @author		YFix Team <yfix.dev@gmail.com>
 * @version		1.0
 */
class yf_geo_ip
{
    // Send email with verification code
    public function _update_user_geo_location($user_id = 0, $FORCE_IP = '')
    {
        if ( ! main()->USE_GEO_IP) {
            return false;
        }
        if (empty($user_id)) {
            return false;
        }
        $user_info = user($user_id);
        if ( ! empty($user_info)) {
            $user_info['lon'] = (float) ($user_info['lon']);
            $user_info['lat'] = (float) ($user_info['lat']);
        }
        // Do not update if user has already found lon and lat
        if ( ! empty($user_info['lon']) && ! empty($user_info['lat'])) {
            return false;
        }
        // Get user's last IP he logged in
        $cur_ip = $FORCE_IP;
        if (empty($cur_ip)) {
            list($cur_ip) = db()->query_fetch(
                'SELECT ip AS `0` 
				FROM ' . db('log_auth') . ' 
				WHERE user_id=' . (int) $user_id . ' 
				ORDER BY date DESC 
				LIMIT 1'
            );
        }
        // Get user's register IP
        if (empty($cur_ip)) {
            $cur_ip = $user_info['ip'];
        }
        // Try to get lon, lat and zip_code by IP
        if ( ! empty($cur_ip)) {
            $geo_data = common()->_get_geo_data_from_db($cur_ip);
            $lon = (float) ($geo_data['latitude']);
            $lat = (float) ($geo_data['longitude']);
            $radius = 3;
            $zip_data = db()->query_fetch('SELECT * FROM ' . db('zip_data') . ' WHERE (POW((69.1 * (lon - ' . (float) $lon . ') * cos(' . (float) $lat . " / 57.3)), '2') + POW((69.1 * (lat - " . (float) $lat . ")), '2')) < (" . (float) $radius . ' * ' . (float) $radius . ') LIMIT 1');
            if ( ! empty($zip_data)) {
                $zip_code = $zip_data['id'];
            }
            // Try to get lon, lat by zip_code
        } elseif ( ! empty($user_info['zip_code'])) {
            $zip_data = db()->query_fetch('SELECT * FROM ' . db('zip_data') . " WHERE id='" . _es($user_info['zip_code']) . "'");
            $lon = (float) ($zip_data['lon']);
            $lat = (float) ($zip_data['lat']);
        }
        // Do update user's info
        if ( ! empty($lon) && ! empty($lat)) {
            db()->UPDATE('user', [
                'lon' => (float) $lon,
                'lat' => (float) $lat,
                'zip_code' => _es(empty($user_info['zip_code']) ? $zip_code : ''),
            ], 'id=' . (int) $user_id);
        }
        // Sync ads lon,lat with users
        db()->query(
            'UPDATE ' . db('ads') . ' AS a
				, ' . db('user') . ' AS u
			SET a.lon = u.lon
				, a.lat = u.lat
			WHERE a.user_id = u.id
				AND u.id = ' . (int) $user_id
        );
    }
}
