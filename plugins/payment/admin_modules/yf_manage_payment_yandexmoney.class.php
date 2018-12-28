<?php

class yf_manage_payment_yandexmoney
{
    public $payment_api = null;
    public $manage_payment_lib = null;

    public $provider_name = 'yandexmoney';
    public $provider_class = null;

    protected $object = null;
    protected $action = null;
    protected $id = null;
    protected $filter_name = null;
    protected $filter = null;
    protected $url = null;

    public function _init()
    {
        $payment_api = &$this->payment_api;
        $manage_lib = &$this->manage_payment_lib;
        $provider_name = &$this->provider_name;
        $provider_class = &$this->provider_class;
        // class
        $payment_api = _class('payment_api');
        $manage_payment_lib = module('manage_payment_lib');
        // provider
        $provider_class = $payment_api->provider_class(['provider_name' => $provider_name]);
        // property
        $object = &$this->object;
        $action = &$this->action;
        $id = &$this->id;
        $filter_name = &$this->filter_name;
        $filter = &$this->filter;
        $url = &$this->url;
        // setup property
        $object = $_GET['object'];
        $action = $_GET['action'];
        $id = $_GET['id'];
        $filter_name = $object . '__' . $action;
        $filter = $_SESSION[$filter_name];
        // url
        $url = [
            'list' => url_admin([
                'is_full_url' => true,
                'object' => $object,
                'action' => 'show',
            ]),
            'authorize' => url_admin([
                'is_full_url' => true,
                'object' => $object,
                'action' => 'authorize',
            ]),
            'request_interkassa' => url_admin([
                'object' => $object,
                'action' => 'request_interkassa',
                'operation_id' => '%operation_id',
            ]),
        ];
    }

    public function _url($name, $replace = null)
    {
        $url = &$this->url;
        $result = null;
        if (empty($url[$name])) {
            return  $result;
        }
        if ( ! is_array($replace)) {
            return  $url[$name];
        }
        $result = str_replace(array_keys($replace), array_values($replace), $url[$name]);
        return  $result;
    }

    public function show()
    {
        $url = $this->_url('authorize');
        $result = js_redirect($url, false);
        return  $result;
    }

    public function _authorize()
    {
        // class
        $provider_class = &$this->provider_class;
        // request
        $url = $this->_url('authorize');
        $result = $provider_class->authorize_request([
            'redirect_uri' => $url,
        ]);
        return  $result;
    }

    public function _revoke_authorize()
    {
        // class
        $provider_class = &$this->provider_class;
        // request
        $result = $provider_class->api_token_revoke();
        return  $result;
    }

    public function authorize()
    {
        $url = &$this->url;
        $url_authorize = $this->_url('authorize');
        // class
        $provider_class = &$this->provider_class;
        // is authorize
        $is_authorize = $provider_class->access_token;
        $authorize_icon = 'fa fa-chain';
        $authorize_class = 'btn btn-xs text-success';
        if ( ! $is_authorize) {
            $authorize_icon .= '-broken';
            $authorize_class = 'btn btn-xs text-danger';
        }
        // web
        $replace = [
            'is_confirm' => false,
            'is_authorize' => $is_authorize ? 'выполнена' : 'не выполнена',
        ];
        $result = form($replace)
            ->on_post(function ($data, $extra, $rules) {
                $is_confirm = ! empty($_POST['is_confirm']);
                if ($is_confirm) {
                    $is_action = false;
                    $action = @$_POST['operation'];
                    switch ($action) {
                        case 'authorize':
                        case 'revoke_authorize':
                            $is_action = true;
                            break;
                    }
                    if ( ! $is_action) {
                        common()->message_error('Неизвестное действие');
                        return  null;
                    }
                    $action = '_' . $action;
                    $result = $this->$action();
                    if (empty($result['status'])) {
                        $level = 'error';
                        $message = 'Ошибка: ';
                    } else {
                        $level = 'success';
                        $message = 'Выполнено: ';
                    }
                    $message .= $result['status_message'];
                    common()->add_message($message, $level);
                    return  js_redirect($url_authorize);
                }
                common()->message_info('Требуется подтверждение, для выполнения операции');
            })
            ->info('is_authorize', ['desc' => 'Авторизация YandexMoney', 'icon' => $authorize_icon, 'class' => $authorize_class])
            ->check_box('is_confirm', ['desc' => 'Подтверждение', 'no_label' => true])
            ->row_start()
                ->submit('operation', 'authorize', ['desc' => 'Авторизация', 'icon' => 'fa fa-chain', 'class' => 'btn btn-xs btn-success'])
                ->submit('operation', 'revoke_authorize', ['desc' => 'Отозвать авторизацию', 'icon' => 'fa fa-chain-broken', 'class' => 'btn btn-xs btn-danger', 'disabled' => ! $is_authorize])
                ->link('Назад', $url['list'], ['class' => 'btn btn-xs btn-default', 'icon' => 'fa fa-chevron-left'])
            ->row_end();
        return  $result;
    }

    public function status($options = null)
    {
        $result = $this->_status($options);
        return  $this->_user_message($result);
    }

    protected function _user_message($options = null)
    {
        $url = &$this->url;
        // import operation
        is_array($options) && extract($options, EXTR_PREFIX_ALL | EXTR_REFS, '');
        if (empty($_status_message)) {
            return  null;
        }
        switch (true) {
            case @$_status === 'in_progress':
                $_css_panel_status = 'warning';
                empty($_status_header) && $_status_header = 'В процессе';
                break;
            case @$_status === 'processing':
                $_css_panel_status = 'warning';
                empty($_status_header) && $_status_header = 'Обработка';
                break;
            case @$_status === 'success' || @$_status === true:
                $_css_panel_status = 'success';
                empty($_status_header) && $_status_header = 'Выполнено';
                break;
            case @$_status === 'refused':
            default:
                $_css_panel_status = 'danger';
                empty($_status_header) && $_status_header = 'Ошибка';
                break;
        }
        // body
        $content = empty($_is_html_message) ? $_status_message : htmlentities($_status_message, ENT_HTML5, 'UTF-8', $double_encode = false);
        $panel_body = '<div class="panel-body">' . $content . '</div>';
        // header
        $content = 'Вывод средств';
        if ( ! empty($_status_header)) {
            $content .= ': ' . $_status_header;
        }
        $content = htmlentities($content, ENT_HTML5, 'UTF-8', $double_encode = false);
        $panel_header = '<div class="panel-heading">' . $content . '</div>';
        // footer
        if ( ! empty($_status_footer)) {
            $content = $_status_footer;
        } else {
            $content = '';
            $operation_id = empty($_operation_id) ? (int) $_GET['operation_id'] : $_operation_id;
            if ($operation_id > 0) {
                $url_view = $this->_url('view', ['%operation_id' => $operation_id]);
                $content .= '<a href="' . $url_view . '" class="btn btn-xs btn-info">Назад к операции</a>';
            }
            $url_list = $this->_url('list');
            $content .= '<a href="' . $url_list . '" class="btn btn-xs btn-primary">Список операции</a>';
        }
        isset($content) && $panel_footer = '<div class="panel-footer">' . $content . '</div>';
        // panel
        $result = <<<"EOS"
<div class="panel panel-{$_css_panel_status}">
	$panel_header
	$panel_body
	$panel_footer
</div>
EOS;
        return  $result;
    }
}
