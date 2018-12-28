<?php

class yf_news
{
    const table = 'news';
    /** @var array @conf_skip Params for the comments */
    public $_comments_params = [
        'action_name' => 'full',
        'object_name' => 'news',
        'return_action' => 'full_news',
        'action_for_add' => 'add_comment',
        'action_for_mark_spam' => 'comments_mark_spam',
        'tpl_name' => 'add_comment_for_news',
    ];

    /**
     * Catch missing method call.
     * @param mixed $name
     * @param mixed $args
     */
    public function __call($name, $args)
    {
        return main()->extend_call($this, $name, $args);
    }

    /**
     * @param mixed $method
     * @param mixed $dirty_args
     */
    public function _module_action_handler($method, $dirty_args = [])
    {
        // For links like this:
        // /news/uvazhaemie-polzovateli-i-posetiteli
        // /news/уникальая-новость
        $id = trim($dirty_args['action'] ?: $_GET['action']);
        if ( ! $_GET['id'] && strlen($id) && $id != 'show') {
            $_GET['id'] = $id;
        }
        if ( ! $method || substr($method, 0, 1) === '_' || ! method_exists($this, $method)) {
            $_GET['action'] = $method;
            $method = 'show';
        }
        return $this->$method();
    }


    public function _init()
    {
        asset('animate-css');
        jquery('
			$(\'.news_item_title\').hover(function(){
				$(this).parent().find(\'.news_btn\').addClass(\'active_news_btn\');
			},function(){
				$(this).parent().find(\'.news_btn\').removeClass(\'active_news_btn\');
			});
			$(\'.news_btn\').hover(function(){
				$(this).parent().parent().find(\'.news_item_title\').addClass(\'active_news\');
			},function(){
				$(this).parent().parent().find(\'.news_item_title\').removeClass(\'active_news\');
			});
		');
        css('
			.news .news_item { font-size: 13px; font-weight:normal; margin-bottom: 20px; margin-top: 20px; }
			.news h1 { font-size: 18px; margin-bottom: 20px; }
			.news h3 { font-size: 18px; margin-top: 5px; }
			.news .news_item_content { }
			.news .news_item_content h1, .news .news_item_content h2, .news .news_item_content h3,
			.news .news_item_content h4, .news .news_item_content h5, .news .news_item_content h6 { font-size: 13px; font-weight:normal; }
			.news .news_item_footer { color: #aaa; }
			.news .news_item_date { color: #777; }
			.news .news_item_social { margin-top: 20px; }
			.news .news_item_comments { margin-top: 20px; }
		');
    }


    public function show()
    {
        if ($_GET['id']) {
            return $this->_view();
        }
        $num_comments = db()->select('object_id', 'COUNT(*)')->from('comments')->where('object_name', 'news')->group_by('object_id')->get_2d();

        $sql = db()->from(self::table)->where('active', 1)->where('locale', conf('language'))->order_by('add_date', 'desc')->sql();
        list($add_sql, $pages, $total) = common()->divide_pages($sql);
        foreach ((array) db()->get_all($sql . $add_sql) as $a) {
            $items[$a['id']] = [
                'title' => $a['title'],
                'head_text' => $a['head_text'],
                'full_text' => $a['full_text'],
                'add_date' => _format_date($a['add_date'], 'long'),
                'full_link' => url('/@object/show/' . ($a['url'] ?: $a['id'])),
                'num_comments' => (int) $num_comments[$a['id']],
            ];
        }
        return tpl()->parse('news/main', [
            'items' => $items,
            'pages' => $pages,
            'total' => (int) $total,
        ]);
    }

    /**
     * @param null|mixed $id
     */
    public function _get_info($id = null)
    {
        $id = $id ?: $_GET['id'];
        $sql = db()->from(self::table)->where('locale', conf('language'));
        if (is_numeric($id)) {
            $sql->whereid($id);
        } else {
            $sql->where('url', $id);
        }
        return $sql->get();
    }


    public function _view()
    {
        $a = $this->_get_info();
        if ( ! $a || ! $a['active']) {
            return _404('Not found');
        }
        $this->_current = $a;

        $url = url('/@object/@action/' . ($a['url'] ?: $a['id']));
        $comments = module('comments')->_show_comments((array) $this->_comments_params + [
            'add_form_action' => url('/@object/add_comment/' . $a['id']),
            'return_path' => $url,
            'object_id' => $a['id'],
        ]);
        $comments_form = main()->USER_ID ? module('comments')->add((array) $this->_comments_params + [
            'add_form_action' => url('/@object/add_comment/' . $a['id']),
            'return_path' => $url,
            'object_id' => $a['id'],
        ]) : '';
        return tpl()->parse('news/full_news', [
            'title' => $a['title'],
            'head_text' => $a['head_text'],
            'full_text' => $a['full_text'],
            'add_date' => _format_date($a['add_date'], 'long'),
            'full_link' => $url,
            'comments_url' => url('/@object/add_comment/' . $a['id']),
            'comments_form' => $comments_form,
            'comments_block' => $comments['comments'],
            'num_comments' => (int) ($comments['num_comments']),
            'social' => html()->social_simple_share(['horizontal' => true, 'url' => $url, 'title' => $a['title'] . ' | ' . t('Новости') . (defined('SITE_ADVERT_NAME') ? ' | ' . SITE_ADVERT_NAME : '')]),
        ]);
    }

    /**
     * @param mixed $params
     */
    public function add_comment($params = [])
    {
        main()->NO_GRAPHICS = 1;
        //		$_GET['ajax_mode'] = 1;

        $id = $_GET['id'];
        $a = $this->_get_info();
        if ( ! $a || ! $a['active']) {
            return _404('Not found');
        }
        $url = url('/@object/' . ($a['url'] ?: $a['id']));
        return module('comments')->add((array) $this->_comments_params + [
            'add_form_action' => url('/@object/add_comment/' . $a['id']),
            'return_path' => $url,
            'object_id' => $a['id'],
        ]);
    }

    public function comments_mark_spam()
    {
        main()->NO_GRAPHICS = 1;
        $_GET['ajax_mode'] = 1;
        $params = [
            'object_name' => 'news',
            'action_name' => 'full_news',
        ];
        return module('comments')->mark_spam($params);
    }
    /**
     * @param mixed $params
     */
    public function _comment_is_allowed($params = [])
    {
        $is_allowed = $_GET['action'] == 'view' ? true : false;
        return $is_allowed;
    }

    /**
     * Hook for navigation bar.
     * @param mixed $params
     */
    public function _hook_nav($params = [])
    {
        if (in_array($_GET['action'], ['show', 'view']) && ! $_GET['id'] || ! $this->_current) {
            // Default nav bar will be shown
            return false;
        }
        $nav = &$params['nav_bar_obj'];
        return [
            $nav->_nav_item('News', url('/@object')),
            $nav->_nav_item($this->_current['title']),
        ];
    }

    /**
     * Meta tags injection.
     * @param mixed $meta
     */
    public function _hook_meta($meta = [])
    {
        $a = $this->_current;
        if ($a) {
            $desc = _truncate($a['meta_desc'], 250);
            $url = url('/@object/show/' . ($a['url'] ?: $a['id']));
            $meta = [
                'keywords' => $a['meta_keywords'],
                'news_keywords' => $a['meta_keywords'],
                'description' => $desc,
                'og:description' => $desc,
                'og:title' => $a['title'],
                'og:type' => 'article',
                'og:url' => $url,
                'og:site_name' => SITE_ADVERT_NAME,
                'canonical' => $url,
                'article:published_time' => date('Y-m-d', $a['add_date']),
            ] + (array) $meta;
        }
        return $meta;
    }

    /**
     * Meta page title injection.
     * @param mixed $title
     */
    public function _hook_title($title)
    {
        $a = $this->_current;
        if ($a) {
            return $a['title'];
        }
        return $title;
    }

    /**
     * Hook for the site_map.
     * @param mixed $sitemap
     */
    public function _hook_sitemap($sitemap = false)
    {
        if ( ! is_object($sitemap)) {
            return false;
        }
        foreach ((array) db()->select('id, url')->from(self::table)->where('active', '1')->where('locale', conf('language'))->order_by('add_date', 'desc')->get_all() as $a) {
            $sitemap->_add('/news/show/' . ($a['url'] ?: $a['id']) . ($a['locale'] ? '?lang=' . $a['locale'] : ''));
        }
        return true;
    }
}
