<?php

class yf_ck_test
{
    public function show()
    {
        return form()
            ->textarea('full_text', ['desc' => '', 'ckeditor' => ['config' => $this->_get_cke_config()], 'no_label' => true]);
    }

    /**
     * Return default config used by CKEditor.
     * @param mixed $params
     */
    public function _get_cke_config($params = [])
    {
        return _class('admin_methods')->_get_cke_config([
            'file_browser' => 'internal',
        ]);
        /*
                asset('ckeditor-plugin-autosave');
                asset('ckeditor-plugin-youtube');
                $config = array(
                    'language' => 'ru',
                    'height'                    => '500px',
                    'filebrowserBrowseUrl' => url_admin("./?object=ck_file_browser&action=show"),
        //            'filebrowserImageBrowseUrl' => url_admin("./?object=ck_file_browser&action=show"),
                    'filebrowserImageUploadUrl' => url_admin("./?object=ck_file_browser&action=upload_image&id=".intval($_GET['id'])."&type=image"),
                    'format_tags' => 'p;h1;h2;h3;h4;h5;h6;pre;address;div',
                    'extraAllowedContent' => 'a[*]{*}(*); img[*]{*}(*); div[*]{*}(*) table tr th td caption',
                    'extraPlugins' => 'autosave,youtube',
                );
        var_dump($config);
                return $config;
        */
    }
}
