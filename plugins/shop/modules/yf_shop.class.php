<?php

/**
 * Shop module.
 */
class yf_shop extends yf_module
{
    /* Test CC numbers:

    370000000000002 American Express Test Card
    6011000000000012 Discover Test Card
    5424000000000015 MasterCard Test Card
    4007000000027 Visa Test Card
    4012888818888 second Visa Test Card
    3088000000000017 JCB
    38000000000006 Diners Club/ baskete Blanche
    */

    /** @var string Folder where product's images store */
    public $PROD_IMG_DIR = 'shop/products/';
    /** @var string fullsize image suffix (underscore at the beginning required) */
    public $FULL_IMG_SUFFIX = '_full';
    /** @var string Thumb image suffix (underscore at the beginning required) */
    public $THUMB_SUFFIX = '_small';
    /** @var string Image prefix */
    public $IMG_PREFIX = 'product_';
    /** @var string Default currency */
    public $CURRENCY = '$';
    /** @var bool SHOW_SIMILAR_PRICE or not */
    public $SHOW_SIMILAR_PRICE = true;
    /** @var bool THIS_ITEM_OFTEN_BUY or not */
    public $THIS_ITEM_OFTEN_BUY = true;
    /** @var array forum settings (default values) */
    public $COMPANY_INFO = [
        'company_name' => 'Shop.com ',
        'company_address' => 'Company Address 1',
        'company_address2' => 'Company Address 2',
        'company_phone' => 'Company Phone',
        'company_website' => 'Company Website',
        'company_email' => 'Company Email',
        'company_title' => 'Shop.com ',
    ];
    /** @var Billing info */
    public $_b_fields = [
        'b_first_name',
        'b_last_name',
        'b_email',
        'b_phone',
        'b_address',
        'b_address2',
        'b_zip_code',
        'b_city',
        'b_state',
        'b_country',
        'b_company',
    ];
    /** @var Shipping info */
    public $_s_fields = [
        's_first_name',
        's_last_name',
        's_email',
        's_phone',
        's_address',
        's_address2',
        's_zip_code',
        's_city',
        's_state',
        's_country',
        's_company',
    ];
    /** @var Required shipping and billing fields */
    public $_required_fields = [
        'name',
        'phone',
    ];
    /** @var @conf_skip */
    public $_statuses = [
        'pending' => 'pending',
        'pending payment' => 'pending payment',
        'proccessed' => 'proccessed',
        'delivery' => 'delivery',
        'shipped' => 'shipped',
    ];
    public $_ship_type = [
        1 => 'Free',
        2 => 'Courier',
        3 => 'FedEX',
        4 => 'Post',
    ];
    /** @var Shipping types */
    public $_ship_types = [
        1 => [
            'name' => 'Free',
            'price' => 0,
        ],
        2 => [
            'name' => 'Courier',
            'price' => 1,
        ],
        3 => [
            'name' => 'FedEX',
            'price' => 5,
        ],
        4 => [
            'name' => 'Post',
            'price' => 1,
        ],
    ];
    /** @var Shipping types names (creating automatically inside '_init') @conf_skip */
    public $_ship_types_name = [];
    /** @var Payment types */
    public $_pay_types = [
        1 => 'Cash On Delivery',
        2 => 'Authorize.Net',
        3 => 'Bank Transfer',
        4 => 'Cheque / Money Order',
    ];
    /** @var Payment methods params */
    public $_pay_method_params = [
        2 => [ // Authorize.Net
            'LOGIN_ID' => '{CLIENT_ID}',
            'TRANSACTION_KEY' => '{CLIENT_SECRET}',
            'TEST_MODE' => 1,
            'IN_PRODUCTION' => 0,
            'DESCRIPTION' => 'Shop Description Here',
        ],
    ];

    /** @var Force payment method (Set to 0 to disable) */
    public $FORCE_PAY_METHOD = 0;
    /** @var Inline registration */
    public $INLINE_REGISTER = true;
    /** @var */
    public $ATTRIBUTES_CAT_ID = 1;
    /** @var Force ship method for user group (user_group => ship_type) */
    public $FORCE_GROUP_SHIP = [
        //3	=> 3,
    ];
    /** @var Force payment method for user group (user_group => pay_type) */
    public $FORCE_GROUP_PAY = [
        //3	=> 1,
    ];
    public $_comments_params = [
        'return_action' => 'product_details',
        'object_name' => 'shop',
        'allow_guests_posts' => '1',
    ];

    public function _init()
    {
        $shop = module('shop');
        $shop->_shop_cats = _class('cats')->_get_items_names('shop_cats');
        $shop->_shop_cats_all = _class('cats')->_get_items_array('shop_cats');
        $shop->_shop_cats_for_select = _class('cats')->_prepare_for_box('shop_cats');

        $sql_man = 'SELECT * FROM ' . db('shop_manufacturers') . ' ORDER BY name ASC';
        $shop->_manufacturer = db()->query_fetch_all($sql_man);

        $shop->_man_for_select['none'] = '--NONE--';
        foreach ((array) $shop->_manufacturer as $k => $v) {
            $shop->_man_for_select[$v['url']] = $v['name'];
        }
        $shop->_man_id = 'none';
        $shop->products_img_dir = INCLUDE_PATH . SITE_UPLOADS_DIR . $shop->PROD_IMG_DIR;
        $shop->products_img_webdir = WEB_PATH . SITE_UPLOADS_DIR . $shop->PROD_IMG_DIR;
        if ( ! file_exists($shop->products_img_dir)) {
            _mkdir_m($shop->products_img_dir);
        }

        $shop->_boxes = [
            'ship_type' => 'select_box("ship_type", $shop->_ship_types_names, $selected, false, 2, "", false)',
            'pay_type' => 'radio_box("pay_type", $shop->_pay_types, $selected, 1, 2, "", false)',
        ];

        $shop->_ship_types_names = [];
        foreach ((array) $shop->_ship_types as $_id => $_info) {
            $_price_text = ' (' . ($_info['price'] < 0 ? '-' : '+') . $shop->_format_price(abs($_info['price'])) . ')';
            $shop->_ship_types_names[$_id] = $_info['name'] . ($_info['price'] ? $_price_text : '');
        }

        // Override pay type for group
        $force_group_pay_type = $shop->FORCE_GROUP_PAY[main()->USER_GROUP];
        if ($force_group_pay_type/* && isset($shop->_pay_types[$force_group_pay_type])*/) {
            $shop->FORCE_PAY_METHOD = $force_group_pay_type;
        }
    }

    public function show()
    {
        return _class('shop_show', 'modules/shop/')->show();
    }

    public function products_show($search = '', $str_search = '')
    {
        return _class('shop_products_show', 'modules/shop/')->products_show($search, $str_search);
    }

    public function product_details()
    {
        return _class('shop_product_details', 'modules/shop/')->product_details();
    }

    public function products_related($id = '')
    {
        return _class('shop_products_related', 'modules/shop/')->products_related($id);
    }

    public function products_similar_by_price($price, $id)
    {
        return _class('shop_products_similar_by_price', 'modules/shop/')->products_similar_by_price($price, $id);
    }

    public function products_similar_by_basket($id)
    {
        return _class('shop_products_similar_by_basket', 'modules/shop/')->products_similar_by_basket($id);
    }

    public function basket($params = [])
    {
        return _class('shop_basket', 'modules/shop/')->basket($params);
    }

    public function basket_main($params = [])
    {
        return _class('shop_basket_main', 'modules/shop/')->basket_main($params);
    }

    public function basket_add()
    {
        return _class('shop_basket_add', 'modules/shop/')->basket_add();
    }

    public function basket_clean()
    {
        return _class('shop_basket_clean', 'modules/shop/')->basket_clean();
    }

    public function _basket_side()
    {
        return _class('shop__basket_side', 'modules/shop/')->_basket_side();
    }

    public function _basket_save()
    {
        return _class('shop__basket_save', 'modules/shop/')->_basket_save();
    }

    public function _basket_api()
    {
        return _class('shop__basket_api', 'modules/shop/')->_basket_api();
    }

    public function order()
    {
        return _class('shop_order', 'modules/shop/')->order();
    }

    public function orders($FORCE_DISPLAY_FORM = false)
    {
        return _class('shop_orders', 'modules/shop/')->orders($FORCE_DISPLAY_FORM);
    }

    public function order_validate_data($FORCE_DISPLAY_FORM = false)
    {
        return _class('shop_order_validate_data', 'modules/shop/')->order_validate_data($FORCE_DISPLAY_FORM);
    }

    public function order_view($FORCE_DISPLAY_FORM = false)
    {
        return _class('shop_order_view', 'modules/shop/')->order_view($FORCE_DISPLAY_FORM);
    }

    public function order_delete($FORCE_DISPLAY_FORM = false)
    {
        return _class('shop_order_delete', 'modules/shop/')->order_delete($FORCE_DISPLAY_FORM);
    }

    public function _order_step_start($FORCE_DISPLAY_FORM = false)
    {
        return _class('shop__order_step_start', 'modules/shop/')->_order_step_start($FORCE_DISPLAY_FORM);
    }

    public function _order_step_delivery($FORCE_DISPLAY_FORM = false)
    {
        return _class('shop__order_step_delivery', 'modules/shop/')->_order_step_delivery($FORCE_DISPLAY_FORM);
    }

    public function _order_validate_delivery()
    {
        return _class('shop__order_validate_delivery', 'modules/shop/')->_order_validate_delivery();
    }

    public function _order_step_select_payment($FORCE_DISPLAY_FORM = false)
    {
        return _class('shop__order_step_select_payment', 'modules/shop/')->_order_step_select_payment($FORCE_DISPLAY_FORM);
    }

    public function _order_validate_select_payment()
    {
        return _class('shop__order_validate_select_payment', 'modules/shop/')->_order_validate_select_payment();
    }

    public function _order_create()
    {
        return _class('shop__order_create', 'modules/shop/')->_order_create();
    }

    public function _order_step_do_payment($FORCE_DISPLAY_FORM = false)
    {
        return _class('shop__order_step_do_payment', 'modules/shop/')->_order_step_do_payment($FORCE_DISPLAY_FORM);
    }

    public function _order_validate_do_payment()
    {
        return _class('shop__order_validate_do_payment', 'modules/shop/')->_order_validate_do_payment();
    }

    public function _order_step_finish($FORCE_DISPLAY_FORM = false)
    {
        return _class('shop__order_step_finish', 'modules/shop/')->_order_step_finish($FORCE_DISPLAY_FORM);
    }

    public function _order_pay_authorize_net($order_info = [], $params = [])
    {
        return _class('shop__order_pay_authorize_net', 'modules/shop/')->_order_pay_authorize_net($order_info, $params);
    }

    public function payment_callback()
    {
        return _class('shop_payment_callback', 'modules/shop/')->payment_callback();
    }

    public function _format_price($price = 0)
    {
        return _class('shop__format_price', 'modules/shop/')->_format_price($price);
    }

    public function _hidden_field($name = '', $value = '')
    {
        return _class('shop__hidden_field', 'modules/shop/')->_hidden_field($name, $value);
    }

    public function _product_id_url($product_info = [])
    {
        return _class('shop__product_id_url', 'modules/shop/')->_product_id_url($product_info);
    }

    public function _product_get_price($product_info = [])
    {
        return _class('shop__product_get_price', 'modules/shop/')->_product_get_price($product_info);
    }

    public function _get_group_prices($product_ids = [])
    {
        return _class('shop__get_group_prices', 'modules/shop/')->_get_group_prices($product_ids);
    }

    public function _search_form_short()
    {
        return _class('shop__search_form_short', 'modules/shop/')->_search_form_short();
    }

    public function search()
    {
        return _class('shop_search', 'modules/shop/')->search();
    }

    public function _categories_show()
    {
        return _class('shop__categories_show', 'modules/shop/')->_categories_show();
    }

    public function _products_bestsellers()
    {
        return _class('shop__products_bestsellers', 'modules/shop/')->_products_bestsellers();
    }

    public function _products_last_viewed()
    {
        return _class('shop__products_last_viewed', 'modules/shop/')->_products_last_viewed();
    }

    public function _products_get_attributes($products_ids = [])
    {
        return _class('shop__products_get_attributes', 'modules/shop/')->_products_get_attributes($products_ids);
    }

    public function _get_select_attributes($atts = [])
    {
        return _class('shop__get_select_attributes', 'modules/shop/')->_get_select_attributes($atts);
    }

    public function _box($name = '', $selected = '')
    {
        return _class('shop__box', 'modules/shop/')->_box($name, $selected);
    }

    public function _site_title($title)
    {
        return _class('shop__site_title', 'modules/shop/')->_site_title($title);
    }

    public function _hook_meta_tags($meta)
    {
        return _class('shop__hook_meta_tags', 'modules/shop/')->_hook_meta_tags($meta);
    }

    public function _site_map_items($SITE_MAP_OBJ = false)
    {
        return _class('shop__site_map_items', 'modules/shop/')->_site_map_items($SITE_MAP_OBJ);
    }

    public function _nav_bar_items($params = [])
    {
        return _class('shop__nav_bar_items', 'modules/shop/')->_nav_bar_items($params);
    }

    public function _get_children_cat($id)
    {
        return _class('shop__get_children_cat', 'modules/shop/')->_get_children_cat($id);
    }

    public function _manufacturer_show()
    {
        // TODO: redo current manufacturers
//		return _class('shop__manufacturer_show', 'modules/shop/')->_manufacturer_show();
    }

    public function manufacturer()
    {
        // TODO: show products by given manufacturer
    }

    public function manufacturers()
    {
        // TODO: show list of manufacturers
    }

    public function supplier()
    {
        // TODO: show products by given supplier
    }

    public function suppliers()
    {
        // TODO: show list of suppliers
    }

    public function product_set()
    {
        // TODO: show details of given product set
    }

    public function product_sets()
    {
        // TODO: show list of product set
    }

    public function category()
    {
        // TODO: show category contents
    }

    /**
     * @param mixed $product_id
     */
    public function _get_images($product_id)
    {
        $A = db()->get_all('SELECT id FROM ' . db('shop_product_images') . ' WHERE product_id=' . (int) $product_id . ' AND active=1 ORDER BY is_default DESC');
        $d = sprintf('%09s', $product_id);
        foreach ((array) $A as $img) {
            $replace = [
                '{subdir2}' => substr($d, -6, 3),
                '{subdir3}' => substr($d, -3, 3),
                '{product_id}' => $product_id,
                '{image_id}' => $img['id'],
            ];
            $images[] = [
                'big' => str_replace(array_keys($replace), array_values($replace), 'uploads/shop/products/{subdir2}/{subdir3}/product_{product_id}_{image_id}_big.jpg'),
                'thumb' => str_replace(array_keys($replace), array_values($replace), 'uploads/shop/products/{subdir2}/{subdir3}/product_{product_id}_{image_id}_thumb.jpg'),
                'id' => $img['id'],
            ];
        }
        return $images;
    }

    /**
     * @param mixed $product_id
     * @param mixed $image_id
     * @param mixed $media
     */
    public function _generate_image_name($product_id, $image_id, $media = false)
    {
        $dirs = sprintf('%06s', $product_id);
        $dir2 = substr($dirs, -3, 3);
        $dir1 = substr($dirs, -6, 3);
        $m_path = $dir1 . '/' . $dir2 . '/';

        $media_host = defined('MEDIA_HOST') ? MEDIA_HOST : false;
        $base_url = WEB_PATH;
        if ( ! empty($media_host) && $media) {
            $base_url = '//' . $media_host . '/';
        }
        $image = [
            'big' => $base_url . 'uploads/shop/products/' . $m_path . 'product_' . $product_id . '_' . $image_id . '_big.jpg',
            'thumb' => $base_url . 'uploads/shop/products/' . $m_path . 'product_' . $product_id . '_' . $image_id . '_thumb.jpg',
            'default' => $base_url . 'uploads/shop/products/' . $m_path . 'product_' . $product_id . '_' . $image_id . '.jpg',
        ];
        return $image;
    }

    /**
     * Hook to provide settigns from shop.
     */
    public function _hook_settings()
    {
        return [
            ['yes_no_box', 'shop__sms_order_send', 'Send SMS to user when new order arrives'],
            ['yes_no_box', 'shop__sms_order_copy', 'Send SMS copy when new order arrives'],
            ['text', 		'shop__sms_order_copy_to', 'Phone numbers to send SMS copy when new order arrives'],
            ['yes_no_box', 'shop__emails_all_send', 'Send emails'],
            ['yes_no_box', 'shop__emails_all_copy', 'Send email copies'],
            ['text', 		'shop__emails_all_copy_to', 'Emails to copy all userland emails'],
            ['text', 		'shop__currency', 'Currency to use in userland'],
        ];
    }
}
