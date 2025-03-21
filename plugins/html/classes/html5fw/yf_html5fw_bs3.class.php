<?php

/**
 * Twitter bootstrap v3 html5 abstraction driver.
 */
load('html5fw_empty', '', 'classes/html5fw/');
class yf_html5fw_bs3 extends yf_html5fw_empty
{
    public $CLASS_FORM_GROUP = 'control-group form-group';
    public $CLASS_INPUT_GROUP = 'input-group'; // col-md-2
    public $CLASS_ADDON = 'add-on input-group-addon';
    public $CLASS_INPUT_PREPEND = 'input-prepend';
    public $CLASS_INPUT_APPEND = 'input-append';
    public $CLASS_LABEL = 'control-label col-md-3';
    public $CLASS_NO_LABEL = ' col-md-offset-3';
    public $CLASS_NO_LABEL_BUTTONS = ' col-md-offset-3';
    public $CLASS_CONTROLS = 'controls col-md-offset-3';
    public $CLASS_CONTROLS_BUTTONS = 'controls col-md-offset-3';
    public $CLASS_DESC = '';
    public $CLASS_EDIT_LINK = 'btn btn-default btn-mini btn-xs';
    public $CLASS_EDIT_ICON = 'icon-edit fa fa-edit';
    public $CLASS_LINK_URL = 'btn btn-default';
    public $CLASS_HELP = 'help-block pull-left';
    public $CLASS_ERROR = 'error has-error';
    public $CLASS_SUCCESS = 'success has-success';
    public $CLASS_WARNING = 'warning has-warning';
    public $CLASS_INFO = 'info has-info';
    public $CLASS_FEEDBACK = 'form-control-feedback';
    public $CLASS_STACKED_ITEM = 'stacked-item'; // col-md-2

    /**
     * @param mixed $content
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $form
     */
    public function form_row($content, $extra = [], $replace = [], $form = [])
    {
        $name = $extra['name'] ?? '';
        $is_html_array = (false !== strpos($name, '['));
        if ($is_html_array) {
            $name_dotted = str_replace(['[', ']'], ['.', ''], trim($name, ']['));
        }
        $no_label = false;
        if (isset($form->_params['no_label'])) {
            $no_label = $form->_params['no_label'];
        }
        if (isset($extra['no_label'])) {
            $no_label = $extra['no_label'];
        }
        $_css_group_map = [
            'errors' => $this->CLASS_ERROR,
            'success' => $this->CLASS_SUCCESS,
            'warnings' => $this->CLASS_WARNING,
            'infos' => $this->CLASS_INFO,
        ];
        foreach ($_css_group_map as $_a => $_css_class) {
            if (isset($extra[$_a][$name]) || ($is_html_array && isset($extra[$_a][$name_dotted]))) {
                if ($extra['stacked']) {
                    $extra['class_add_stacked'] .= ' ' . $_css_class;
                } else {
                    $extra['class_add_form_group'] .= ' ' . $_css_class;
                }
                break;
            }
        }
        $class_form_group = $extra['class_form_group'] ?: $this->CLASS_FORM_GROUP . ($extra['class_add_form_group'] ? ' ' . $extra['class_add_form_group'] : '');
        if ($extra['class_add_wrapper']) {
            $class_form_group .= ' ' . $extra['class_add_wrapper'];
        }
        $class_label = $extra['class_label'] ?: $this->CLASS_LABEL
            . ($extra['class_add_label'] ? ' ' . $extra['class_add_label'] : '');

        $def_class_controls = $extra['buttons_controls'] ? $this->CLASS_CONTROLS_BUTTONS : $this->CLASS_CONTROLS;
        $def_class_no_label = $extra['buttons_controls'] ? $this->CLASS_NO_LABEL_BUTTONS : $this->CLASS_NO_LABEL;
        $class_controls = $extra['class_controls'] ?: $def_class_controls
            . ($extra['desc'] && ! $no_label ? ' ' . $this->CLASS_DESC : $def_class_no_label)
            . ($extra['class_add_controls'] ? ' ' . $extra['class_add_controls'] : '');

        $form_group_extra = $extra['form_group'];
        $form_group_extra['class'] = $class_form_group;

        if ($form->_params['form_group_auto_id'] && ! $form_group_extra['id'] && $extra['id']) {
            $form_group_extra['class'] .= ' form-group-id-' . $extra['id'];
        }

        $controls_extra = $extra['controls'];
        $controls_extra['class'] = $class_controls;

        $label_extra = $extra['control_label'];
        $label_extra['class'] = $class_label;
        $label_extra['for'] = $extra['id'];

        $label_tip_html = $extra['label_tip'] ? trim(' ' . tip($extra['label_tip'], $replace)) : '';

        $label = ($extra['desc'] && ! $no_label ? '<label' . _attrs($label_extra, ['id', 'class', 'style', 'for']) . '>' . t($extra['desc']) . $label_tip_html . '</label>' . PHP_EOL : '');

        $row_start = '<div' . _attrs($form_group_extra, ['id', 'class', 'style']) . '>' . PHP_EOL
            . $label
            . ( ! $extra['wide'] ? '<div' . _attrs($controls_extra, ['id', 'class', 'style']) . '>' . PHP_EOL : '');

        $row_end =
                ( ! $extra['wide'] ? '</div>' . PHP_EOL : '')
            . '</div>';

        $input_group_extra = $extra['input_group'];
        if (!isset($input_group_extra['class'])) {
            $input_group_extra['class'] =  $this->CLASS_INPUT_GROUP . ' ' . ($extra['prepend'] ? $this->CLASS_INPUT_PREPEND : '') . ($extra['append'] ? ' ' . $this->CLASS_INPUT_APPEND : '');
        }
        $input_group_extra['class'] .= ' ' . $input_group_extra['class_add'];

        $show_input_group = ($extra['append'] || $extra['prepend']);

        $before_content_html = $show_input_group ? '<div' . _attrs($input_group_extra, ['id', 'class', 'style']) . '>' . PHP_EOL : '';
        $before_content_html .= $extra['prepend'] ? '<span class="' . $this->CLASS_ADDON . ($extra['class_prepend'] ? ' ' . $extra['class_prepend'] : '') . '">' . $extra['prepend'] . '</span>' . PHP_EOL : '';

        $after_content_html = $extra['append'] ? '<span class="' . $this->CLASS_ADDON . ($extra['class_append'] ? ' ' . $extra['class_append'] : '') . '">' . $extra['append'] . '</span>' . PHP_EOL : '';
        $after_content_html .= $show_input_group ? '</div>' . PHP_EOL : '';

        //		$after_content_html .= $extra['feedback_icon'] ? '<span class="'.$extra['feedback_icon'].' '.$this->CLASS_FEEDBACK.'" aria-hidden="true"></span>'.PHP_EOL : '';

        if ($extra['edit_link']) {
            if (MAIN_TYPE_ADMIN && main()->ADMIN_GROUP != 1 && ! _class('admin_methods')->_admin_link_is_allowed($extra['edit_link'])) {
                $extra['edit_link'] = '';
            }
        }
        $edit_link_html = $extra['edit_link'] ? ' <a href="' . $extra['edit_link'] . '" class="' . $this->CLASS_EDIT_LINK . '"><i class="' . $this->CLASS_EDIT_ICON . '"></i> ' . t('Edit') . '</a>' . PHP_EOL : '';
        $link_name_html = ($extra['link_url'] && $extra['link_name']) ? ' <a href="' . $extra['link_url'] . '" class="' . $this->CLASS_LINK_URL . $extra['link_class'] . '">' . t($extra['link_name']) . '</a>' . PHP_EOL : '';

        $inline_help_before = $extra['help_before'] ? '<span class="' . $this->CLASS_HELP . '">' . nl2br($extra['help_before']) . '</span>' . PHP_EOL : '';
        $inline_help_after = $extra['inline_help'] ? '<span class="' . $this->CLASS_HELP . '">' . nl2br($extra['inline_help']) . '</span>' . PHP_EOL : '';
        $inline_tip_html = $extra['tip'] ? ' ' . tip($extra['tip'], $replace) : '';

        if ($extra['only_row_start']) {
            return $row_start;
        } elseif ($extra['only_row_end']) {
            return $row_end;
        } elseif ($extra['stacked']) {
            $extra_stacked = is_array($extra['stacked']) ? $extra['stacked'] : [];
            if ($extra['class_stacked'] && ! isset($extra_stacked['class'])) {
                $extra_stacked['class'] = $extra['class_stacked'];
            }
            $extra_stacked['class'] = ($extra_stacked['class'] ?: $this->CLASS_STACKED_ITEM) . ' ' . ($extra_stacked['class_add'] ?: $extra['class_add_stacked']);
            return
                '<span' . _attrs($extra_stacked, ['id', 'class', 'style']) . '>'
                . ($extra['show_label'] ? $label : '')
                . $inline_help_before . $before_content_html . $content . PHP_EOL . $after_content_html
                . $edit_link_html . $link_name_html . $inline_tip_html . $inline_help_after
                . '</span>';
        }
        // Full variant
        return
                $row_start
                . $inline_help_before . $before_content_html . $content . PHP_EOL . $after_content_html
                . $edit_link_html . $link_name_html . $inline_tip_html . $inline_help_after
                . $this->_add_rich_editor($extra, $replace, $form)
                . $row_end;
    }

    /**
     * Generate form row using dl>dt,dd html tags. Useful for user profle and other simple table-like content.
     * @param mixed $content
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $form
     */
    public function form_dd_row($content, $extra = [], $replace = [], $form = [])
    {
        $dd_class = $form->_params['dd_class'] ?: 'span6';

        $class_wrapper = $extra['class_wrapper'] ?: 'dl-horizontal';
        if ($extra['class_add_wrapper']) {
            $class_wrapper .= ' ' . $extra['class_add_wrapper'];
        }
        $class_dd = $extra['class_dd'] ?: '';
        if ($extra['class_add_dd']) {
            $class_dd .= ' ' . $extra['class_add_dd'];
        }
        $row_start = ! $extra['wide'] ? '<dl class="' . $class_wrapper . '">' . PHP_EOL . '<dt>' . t($extra['desc']) . '</dt>' . PHP_EOL : '';
        $before_content_html = '<dd' . ($class_dd ? ' class="' . $class_dd . '"' : '') . '>';
        $after_content_html = '</dd>';
        $row_end = '</dl>' . PHP_EOL;

        if ($extra['edit_link']) {
            if (MAIN_TYPE_ADMIN && main()->ADMIN_GROUP != 1 && ! _class('admin_methods')->_admin_link_is_allowed($extra['edit_link'])) {
                $extra['edit_link'] = '';
            }
        }
        $edit_link_html = $extra['edit_link'] ? ' <a href="' . $extra['edit_link'] . '" class="' . $this->CLASS_EDIT_LINK . '"><i class="' . $this->CLASS_EDIT_ICON . '"></i> ' . t('Edit') . '</a>' . PHP_EOL : '';
        $link_name_html = ($extra['link_url'] && $extra['link_name']) ? ' <a href="' . $extra['link_url'] . '" class="' . $this->CLASS_LINK_URL . '">' . t($extra['link_name']) . '</a>' . PHP_EOL : '';

        $inline_tip_html = ($extra['tip'] ? ' ' . tip($extra['tip'], $replace) : '');
        $inline_help_before = '';
        $inline_help_after = '';

        if ($extra['only_row_start']) {
            return $row_start . $before_content_html;
        } elseif ($extra['only_row_end']) {
            return $after_content_html . $row_end;
        } elseif ($extra['stacked']) {
            return $inline_help_before . $content . PHP_EOL
                . $edit_link_html . $link_name_html . $inline_tip_html . $inline_help_after;
        }
        // Full variant
        return $row_start
                    . $before_content_html . $inline_help_before . $content . PHP_EOL
                    . $edit_link_html . $link_name_html . $inline_tip_html . $inline_help_after . $after_content_html
                    . $this->_add_rich_editor($extra, $replace, $form)
                . $row_end;
    }
}
