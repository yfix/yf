<?php

class yf_payment__user
{
    public $payment_module = null;

    public function _init()
    {
        $this->payment_module = module('payment');
    }

    public function balance($options)
    {
        if (empty(main()->USER_ID)) {
            js_redirect('/login_form', false, 'User id empty');
        }
        $payment_api = _class('payment_api');
        list($account_id, $account) = $payment_api->get_account();
        list($currency_id, $currency) = $payment_api->get_currency__by_id($account);
        list($operation, $count) = $payment_api->operation($account);
        $page_per = $payment_api->OPERATION_LIMIT;
        $pages = ceil($count / $page_per);
        // limit
        $balance_limit_lower = $payment_api->BALANCE_LIMIT_LOWER;
        $payout_limit_min = @$payment_api->PAYOUT_LIMIT_MIN ?: 1;
        // provider
        $providers = $payment_api->provider([
            'all' => true,
        ]);
        $payment_api->provider_options($providers, [
            'IS_DEPOSITION', 'IS_PAYMENT',
            'method_allow', 'fee', 'currency_allow', 'description',
        ]);
        $provider_user = $payment_api->provider();
        $provider = [];
        foreach ($provider_user as &$item) {
            $provider_id = (int) $item['provider_id'];
            $_provider = &$providers[$provider_id];
            $_provider['_IS_DEPOSITION'] && $provider['payin'][] = $provider_id;
            $_provider['_IS_PAYMENT'] && $provider['payout'][] = $provider_id;
        }
        // user
        $user = user(main()->USER_ID);
        // misc
        $status = $payment_api->status();
        $currencies = $payment_api->currencies;
        $currency_rate = $payment_api->currency_rates__buy();
        $payout_currency_allow = $payment_api->payout_currency_allow;
        // transition
        $payment_module = $this->payment_module;
        $payment_module->t($currency, 'currency');
        $payment_module->t($currencies, 'currency');
        $payment_module->t($operation);
        $payment_module->t($providers);
        $payment_module->t($status);
        // tpl
        $replace = [
            'user' => $user,
            'payment' => json_encode([
                'balance_limit_lower' => $balance_limit_lower,
                'payout_limit_min' => $payout_limit_min,
                'user' => $user,
                'account' => $account,
                'currency' => $currency,
                'currencies' => $currencies,
                'currency_rate' => $currency_rate,
                'payout_currency_allow' => $payout_currency_allow,
                'operation' => $operation,
                'provider' => $provider,
                'providers' => $providers,
                'status' => $status,
                'operation_pagination' => [
                    'count' => $count,
                    'page_per' => $page_per,
                    'pages' => $pages,
                    'page' => 1,
                ],
            ]),
        ];
        // tpl
        $result = '';
        $result .= tpl()->parse('payment/user/balance_ctrl', $replace);
        $result .= tpl()->parse('payment/user/balance_form', $replace);
        return  $result;
    }
}
