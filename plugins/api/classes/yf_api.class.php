<?php

/***
 *  module: api
 *
 *    info: external api interface by example: ajax, rpc, etc
 * support: json, jsonp
 *     url:
 *        /api/@object/@action?value=10
 *        /api/@object?action=@action&value=10
 *        ./?object=api&action=@object&id=@action&value=10
 *
 * description: call class or module with method prefix '_api_'
 *
 *     example: handler user message
 *         url: /api/user/message
 *       class:
 *              class user {
 *                  _init() {
 *                  ...
 *                  }
 *                  _api_message() {
 *                      // handler here
 *                      // get user id, store message, etc
 *                  }
 *                  ...
 *              }
 */

class yf_api
{
    public $API_SSL_VERIFY = true;
    public $JSON_VULNERABILITY_PROTECTION = true;
    public $ROBOT_NONE = true;
    public $CACHE_TTL   = 60;
    public $CACHE_TTL_S = 60 / 2;

    public $class = null;
    public $method = null;
    public $is_head = null;
    public $is_get = null;
    public $is_post = null;
    public $is_json = null;
    public $is_jsonp = null;
    public $is_put = null;
    public $is_patch = null;
    public $is_delete = null;
    public $is_request = null;
    public $request_method = null;
    public $request = null;

    public $language_id = null;

    public $class_i18n = null;

    public function _init()
    {
        ini_set('html_errors', 0);
        // ob_start();
        $class = &$this->class;
        $method = &$this->method;
        $is_head = &$this->is_head;
        $is_get = &$this->is_get;
        $is_post = &$this->is_post;
        $is_json = &$this->is_json;
        $is_jsonp = &$this->is_jsonp;
        $is_put = &$this->is_put;
        $is_patch = &$this->is_patch;
        $is_delete = &$this->is_delete;
        $is_request = &$this->is_request;
        $request_method = &$this->request_method;
        // setup
        $object = $_GET['object'];
        if (empty($object) || $object != 'api') {
            return  null;
        }
        $class = $_GET['action'];
        $method = $_GET['id'];
        // language_id
        $language_id = &$_GET['language_id'];
        $this->language_id = $language_id;
        $this->class_i18n = _class('i18n');
        $this->class_i18n->_set_current_lang($language_id);
        // http method
        $request_method = @$_SERVER['REQUEST_METHOD'] ?: 'GET';
        switch ($request_method) {
            case 'GET':    $is_get = true; break;
            case 'POST':   $is_post = true; break;
            case 'PUT':    $is_put = true; break;
            case 'PATCH':  $is_patch = true; break;
            case 'DELETE': $is_delete = true; break;
        }
        if (strpos(@$_SERVER['CONTENT_TYPE'], 'application/json') !== false) {
            $is_json = true;
        }
        if ($is_get && is_string(@$_GET['callback'])) {
            $is_jsonp = true;
        }
        // override
        $class == 'show' && $class = $_REQUEST['object'];
        ! $method && $method = $_REQUEST['action'];
        $this->_call($class, null, $method);
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

    public function _check_ip($options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // filter
        $ip_filter = @$_ip_filter ? $_ip_filter : $this->ip_filter;
        is_string($ip_filter) && $ip_filter = (array) $ip_filter;
        if ( ! is_array($ip_filter)) {
            return  null;
        }
        $ip_filter = $this->_ip_filter_valid($ip_filter);
        if ( ! $ip_filter) {
            return  null;
        }
        // ip
        $ip = isset($_ip) ? $_ip : $this->_ip();
        foreach ($ip_filter as $range => $allow) {
            $result = $this->_ip_in_range($ip, $range);
            if ($result) {
                return  $allow;
            }
        }
        return  false;
    }

    public function _ip_filter_valid($ip_filter)
    {
        if ( ! is_array($ip_filter)) {
            return  false;
        }
        $result = [];
        foreach ($ip_filter as $ip => $allow) {
            if ( ! $ip) {
                return  false;
            }
            $r = strpos($ip, '.*');
            $len = strlen($ip);
            if ($r > 0 && ($len - $r) >= 2) {
                $r = explode('.*', $ip);
                unset($ip_filter[$ip]);
                $count = count($r) - 1;
                $mask = 32 - 8 * $count;
                $ip = $r[0] . str_repeat('.0', $count) . '/' . $mask;
                $ip_filter[$ip] = $allow;
            }
            $result[$ip] = $allow;
        }
        return  $result;
    }

    public function _ip_in_range($ip, $range)
    {
        if (strpos($range, '/') == false) {
            $range .= '/32';
        }
        // $range is in IP/CIDR format eg 127.0.0.1/24
        list($range, $netmask) = explode('/', $range, 2);
        $range_decimal = ip2long($range);
        $ip_decimal = ip2long($ip);
        $wildcard_decimal = pow(2, (32 - $netmask)) - 1;
        $netmask_decimal = ~$wildcard_decimal;
        return  ($ip_decimal & $netmask_decimal) == ($range_decimal & $netmask_decimal);
    }

    public function _parse_request()
    {
        $is_post  = &$this->is_post;
        $is_put   = &$this->is_put;
        $is_patch = &$this->is_patch;
        $is_json  = &$this->is_json;
        $request  = &$this->request;
        if ($is_post || $is_put || $is_patch) {
            $request = file_get_contents('php://input');
            $request = json_decode(file_get_contents('php://input'), true);
            $request && $is_json = true;
        }
        return  $request;
    }

    // 403 Forbidden
    // usage if user_id < 1
    public function _forbidden()
    {
        $this->_reject(403);
    }

    // 500 Internal Server Error
    public function _error()
    {
        $this->_reject(500);
    }

    // 503 Service Unavailable
    public function _reject($code = 503, $is_raw = true)
    {
        list($protocol, $code, $status) = $this->_send_http_status($code);
        $this->_send_http_type();
        $this->_send_http_content($status, $is_raw);
    }

    // 301 Moved Permanently
    // 302 Moved Temporarily
    // 302 Found
    public function _redirect($url, $message = null, $is_raw = true)
    {
        list($protocol, $code, $status) = $this->_send_http_status(302);
        // location
        $url = $url ?: url('/');
        $location = 'Location: ' . $url;
        header($location);
        // message
        $this->_send_http_type();
        $this->_send_http_content($message, $is_raw);
    }

    // 200 OK, etc
    public function _response_raw($message = null, $code = 200, $type = null)
    {
        $is_raw = true;
        // code
        list($protocol, $code, $status) = $this->_send_http_status($code);
        $this->_send_http_type($type);
        // message
        $message = @$message ?: $status;
        $this->_send_http_content($message, $is_raw);
    }

    public function _detect_protocol_scheme($options = null)
    {
        $result = 'http';
        if ( ! empty($_SERVER['HTTPS']) || $_SERVER['SERVER_PORT'] == 443) {
            $result .= 's';
        }
        return  $result;
    }

    public function _detect_protocol($options = null)
    {
        $result = 'HTTP/1.1';
        if (function_exists('php_sapi_name')) { // PHP >= 4.1.0
            $type = php_sapi_name();
            substr($type, 0, 3) == 'cgi' && $result = 'Status:';
        } else {
            isset($_SERVER['SERVER_PROTOCOL']) && $result = $_SERVER['SERVER_PROTOCOL'];
        }
        return  $result;
    }

    public function _request($url, $post = null, $options = null)
    {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        // options
        $options = [
            CURLOPT_USERAGENT => 'YF.API',
            CURLOPT_RETURNTRANSFER => true,
            // CURLOPT_URL            =>  $url,
        ];
        $header = [];
        if ( ! empty($post)) {
            if (@$_is_json) {
                $http_post = json_encode($post);
            } else {
                if (@$_is_request_raw || @$_is_post_raw) {
                    $http_post = $post;
                } else {
                    $http_post = http_build_query($post);
                }
            }
            $options += [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => $http_post,
            ];
        }
        if (@$_is_post) {
            $options += [
                CURLOPT_POST => true,
            ];
        }
        if (@$_user) {
            $userpwd = $_user;
            @$_password && $userpwd .= ':' . $_password;
            $options += [
                CURLOPT_HTTPAUTH => CURLAUTH_ANY,
                CURLOPT_USERPWD => $userpwd,
            ];
        }
        if (@$_bearer || @$_access_token) {
            ( ! @$_bearer && $_access_token) && $_bearer = $_access_token;
            $header[] = 'Authorization: Bearer ' . $_bearer;
        }
        if (@$_is_json) {
            $header[] = 'Content-Type: application/json; charset=utf-8';
        }
        if ($this->API_SSL_VERIFY && strpos($url, 'https') !== false) {
            $options += [
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_CAINFO => @$_CA ?: __DIR__ . '/ca.pem',
            ];
        } else {
            $options += [
                CURLOPT_SSL_VERIFYPEER => false,
            ];
        }
        @$_timeout && $options += [CURLOPT_TIMEOUT => $_timeout];
        @$_SSLCERT && $options += [CURLOPT_SSLCERT => $_SSLCERT];
        @$_SSLCERTPASSWD && $options += [CURLOPT_SSLCERTPASSWD => $_SSLCERTPASSWD];
        @$_SSLKEY && $options += [CURLOPT_SSLKEY => $_SSLKEY];
        @$_SSLKEYPASSWD && $options += [CURLOPT_SSLKEYPASSWD => $_SSLKEYPASSWD];
        // redirect
        $is_redirect = @$_is_redirect || @$_is_followlocation;
        if ($is_redirect) {
            $options += [
                CURLOPT_FOLLOWLOCATION => true,
            ];
        }
        // add header
        if ( ! empty($_header)) {
            $header = array_replace_recursive($header, $_header);
        }
        ! empty($header) && $options += [CURLOPT_HTTPHEADER => $header];
        // debug request header
        $options += [
            CURLINFO_HEADER_OUT => true,
        ];
        // debug response header
        $options += [
            CURLOPT_VERBOSE => true,
            CURLOPT_HEADER => true,
        ];
        // exec
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);
        // status
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $http_header = curl_getinfo($ch, CURLINFO_HEADER_OUT);
        $error_number = curl_errno($ch);
        $error_message = curl_error($ch);
        // DEBUG
        @$_is_debug && var_dump($url, $options, $http_code, $http_header);
        // exit;
        // response
        $status = null;
        if ($response === false) {
            $message = sprintf('[%d] %s', $error_number, $error_message);
            $result = [
                'status' => $status,
                'status_message' => 'Ошибка транспорта: ' . $message,
            ];
            return  $result;
        }
        // get header, body
        $http_header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $http_header_size);
        $body = substr($response, $http_header_size);
        // DEBUG
        @$_is_debug && var_dump($header, $body);
        // exit;
        // http code
        $is_error = false;
        $message = '';
        switch ($http_code) {
            case 200: $status = true;                 break;
            case 301: $message = 'Moved Permanently'; break;
            case 302: $message = 'Moved Temporarily'; break;
            case 400: $message = 'неверный запрос';   break;
            case 401: $message = 'неавторизован';     break;
            case 403: $message = 'доступ ограничен';  break;
            case 404: $message = 'неверный адрес';    break;
            default:
                if ($http_code >= 500) {
                    $message = 'ошибка сервера';
                }
                break;
        }
        if ($http_code != 200) {
            $result = sprintf('Ошибка транспорта: [%d] %s', $http_code, $message);
        }
        $content_type = curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
        // DEBUG
        @$_is_debug && var_dump($content_type);
        // finish
        curl_close($ch);
        // detect content type of response
        if (@$_is_response_raw) {
            $result = $body;
        } else {
            switch (true) {
                case  @$_is_response_json || strpos($content_type, 'application/json') === 0:
                    $result = @json_decode($body, true);
                    // DEBUG
                    @$_is_debug && var_dump('is_json', $result);
                    break;
                case @$_is_response_html || @$_is_response_xml || @$_is_response_form
                    || strpos($content_type, 'application/xml') === 0
                    || strpos($content_type, 'text/xml') === 0
                    || strpos($content_type, 'text/html') === 0:
                    libxml_use_internal_errors(true);
                    $xml_response = @simplexml_load_string($body);
                    $error = libxml_get_errors();
                    // DEBUG
                    @$_is_debug && var_dump('is_xml', $result, $error);
                    if ($error) {
                        libxml_clear_errors();
                        $result = [
                            'status' => null,
                            'status_message' => 'Ошибка ответа: неверная структура данных',
                            'error' => $error,
                            'content' => $body,
                        ];
                        return  $result;
                    }
                    if (@$xml_response->getName() == 'error') {
                        $result = [
                            'status' => null,
                            'status_message' => 'Ошибка ответа: неверные данные - ' . (string) $xml_response,
                            'content' => $body,
                        ];
                        return  $result;
                    }
                    if (@$_is_response_form) {
                        $form = [];
                        foreach ($xml_response->xpath('//input') as $item) {
                            $_item = $item->attributes();
                            $name = (string) $_item->name;
                            $value = (string) $_item->value;
                            $form[$name] = $value;
                        }
                        ! empty($form) && $xml_response = $form;
                        // DEBUG
                        @$_is_debug && var_dump('is_form', $form);
                    }
                    $result = $xml_response;
            }
        }
        if (@$_is_http_raw) {
            $result = [
                'status' => $status,
                'http_code' => $http_code,
                'http_message' => $message,
                'body' => $result,
            ];
            return  $result;
        }
        return  [$status, $result];
    }

    protected function _firewall($class = null, $class_path = null, $method = null, $options = [])
    {
        $is_request = &$this->is_request;
        $_method = '_api_' . $method;
        // try module
        $is_request = true;
        $_class = module_safe($class);
        $_status = method_exists($_class, $_method);
        if ( ! $_status) {
            // try class
            $_class = _class_safe($class, $class_path);
            $_status = method_exists($_class, $_method);
        }
        if ( ! $_status) {
            $this->_reject();
        }
        $request = $this->_parse_request();
        return  $_class->$_method($request, $options);
    }

    protected function _call($class = null, $class_path = null, $method = null, $options = [])
    {
        main()->NO_GRAPHICS = true;
        $result = $this->_firewall($class, $class_path, $method, $options);
        if ($result['is_raw'] ?? false) {
            @list($response, $code, $type) = $result;
            $is_raw = true;
        } else {
            $is_raw = false;
            $json = json_encode($result);
            $response = &$json;
            // check jsonp
            $type = 'json';
            if (isset($_GET['callback'])) {
                $jsonp_callback = $_GET['callback'];
                $response = '/**/ ' . $jsonp_callback . '(' . $json . ');';
                $type = 'javascript';
                $this->is_jsonp = true;
            }
        }
        list($protocol, $code, $status) = $this->_send_http_status($code);
        // message
        $message = @$response ?: $status;
        $this->_send_http_type($type);
        $this->_send_http_content($message, $is_raw);
    }

    protected function _send_http_status($code = null, $status = null)
    {
        $code = (int) $code;
        $protocol = null;
        $status = null;
        if ($code > 0) {
            // send http code
            if (function_exists('http_response_code')) {
                http_response_code($code);
            } // PHP >= 5.4.0
            // protocol detect
            $protocol = $this->_detect_protocol();
            $header = [];
            $header[] = $protocol;
            // status default
            if (empty($status)) {
                switch ($code) {
                    case 200: $status = 'OK';                    break;
                    case 301: $status = 'Moved Permanently';     break;
                    case 302: $status = 'Moved Temporarily';     break;
                    case 403: $status = 'Forbidden';             break;
                    case 500: $status = 'Internal Server Error'; break;
                    case 503: $status = 'Service Unavailable';   break;
                }
            }
            // code
            $header[] = $code;
            // status
            ! empty($status) && $header[] = $status;
            // send http status
            $header = implode(' ', $header);
            header($header);
        }
        return  [$protocol, $code, $status];
    }

    protected function _send_http_type($type = null, $charset = null)
    {
        empty($type) && $type = 'html';
        empty($charset) && $charset = 'utf-8';
        switch ($type) {
            case 'json':
            case 'javascript':
                $content_type = 'application/' . $type;
                break;
            case 'plain':
            case 'html':
                $content_type = 'text/' . $type;
                break;
            default:
                $content_type = 'text/html';
                break;
        }
        $header = 'Content-Type: ' . $content_type . '; charset=' . $charset;
        header($header);
    }

    protected function _send_http_content($response = null, $is_raw = null)
    {
        if (@$this->ROBOT_NONE) {
            $this->_robot_none();
        }
        // $error = ob_get_contents();
        // ob_end_clean();
        if ( ! @$is_raw && ! $this->is_jsonp && @$this->JSON_VULNERABILITY_PROTECTION) {
            echo  ")]}',\n";
        }
        if (isset($response)) {
            echo  $response;
        }
        // if( isset( $error    ) ) { echo( "\n,([{\n $error" ); }
        exit;
    }

    public function _robot_none()
    {
        // none = noindex, nofollow
        header( 'X-Robots-Tag: none' );
    }

    public function _cache( $options = null ) {
        // import options
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        $h = [];
        // none
        if( @$_no_store ) {
            header( 'Cache-Control: no-store' );
            return;
        }
        // public/private
        if( @$_private ) {
            $h[] = 'private';
        } else {
            $h[] = 'public';
        }
        switch( true ) {
            case @$_no_cache : $h[] =   'no-cache'; break;
            case @$_ttl      : $h[] =   'max-age='. $_ttl;
            case @$_ttl_s    : $h[] = 's-max-age='. $_ttl_s; break;
            default:
                $h[] =  'max-age='. $this->CACHE_TTL;
                $h[] = 's-maxage='. $this->CACHE_TTL_S;
        }
        $h = implode( ', ', $h );
        header( 'Cache-Control: '. $h );
    }

}
