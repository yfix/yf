<?php

/**
 * Abstraction layer over HTML5/CSS frameworks.
 * Planned support for these plugins:
 *	Bootstrap v2 https://getbootstrap.com/2.3.2/
 *	Bootstrap v3 https://getbootstrap.com/docs/3.4/
 *	Bootstrap v4 https://getbootstrap.com/docs/4.6/
 *	Bootstrap v5 https://getbootstrap.com/docs/5.1/.
 */
class yf_html5fw
{
    /** @var */
    public $DEFAULT_CSS_FRAMEWORK = 'bs3';

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
     * We cleanup object properties when cloning.
     */
    public function __clone()
    {
        foreach ((array) get_object_vars($this) as $k => $v) {
            $this->$k = null;
        }
    }

    /**
     * Need to avoid calling render() without params.
     */
    public function __toString()
    {
        try {
            return (string) $this->render();
        } catch (Exception $e) {
            return '';
        }
    }

    /**
     * @param mixed $content
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $obj
     */
    public function form_row($content, $extra = [], $replace = [], $obj)
    {
        $css_framework = $extra['css_framework'] ?: conf('css_framework');
        if ( ! $css_framework) {
            $css_framework = $this->DEFAULT_CSS_FRAMEWORK;
        }
        return _class('html5fw_' . $css_framework, 'classes/html5fw/')->form_row($content, $extra, $replace, $obj);
    }

    /**
     * @param mixed $content
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $obj
     */
    public function form_dd_row($content, $extra = [], $replace = [], $obj)
    {
        $css_framework = $extra['css_framework'] ?: conf('css_framework');
        if ( ! $css_framework) {
            $css_framework = $this->DEFAULT_CSS_FRAMEWORK;
        }
        return _class('html5fw_' . $css_framework, 'classes/html5fw/')->form_dd_row($content, $extra, $replace, $obj);
    }

    /**
     * @param mixed $content
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $obj
     */
    public function form_render_out($content, $extra = [], $replace = [], $obj)
    {
        $css_framework = $extra['css_framework'] ?: conf('css_framework');
        if ( ! $css_framework) {
            $css_framework = $this->DEFAULT_CSS_FRAMEWORK;
        }
        return _class('html5fw_' . $css_framework, 'classes/html5fw/')->form_render_out($content, $extra, $replace, $obj);
    }
}
