<?php

class yf_html5fw_empty
{
    /**
     * @param mixed $content
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $form
     */
    public function form_render_out($content, $extra = [], $replace = [], $form = null)
    {
        return $content;
    }

    /**
     * @param mixed $content
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $form
     */
    public function form_row($content, $extra = [], $replace = [], $form = null)
    {
        $row_start =
            '<div>' . PHP_EOL
                . ($extra['desc'] && ! $extra['no_label'] ? '<label for="' . $extra['id'] . '">' . t($extra['desc']) . '</label>' . PHP_EOL : '')
                . ( ! $extra['wide'] ? '<div>' . PHP_EOL : '');

        $row_end =
                ( ! $extra['wide'] ? '</div>' . PHP_EOL : '')
            . '</div>' . PHP_EOL;

        $before_content_html = '';
        $after_content_html = '';

        $edit_link_html = ($extra['edit_link'] ? ' <a href="' . $extra['edit_link'] . '"><i></i> ' . t('Edit') . '</a>' . PHP_EOL : '');
        $link_name_html = (($extra['link_url'] && $extra['link_name']) ? ' <a href="' . $extra['link_url'] . '">' . t($extra['link_name']) . '</a>' . PHP_EOL : '');

        $inline_help_html = ($extra['inline_help'] ? '<span>' . $extra['inline_help'] . '</span>' . PHP_EOL : '');
        $inline_tip_html = ($extra['tip'] ? ' ' . $this->_show_tip($extra['tip'], $extra, $replace) : '');

        if ($extra['only_row_start']) {
            return $row_start;
        } elseif ($extra['only_row_end']) {
            return $row_end;
        } elseif ($extra['stacked']) {
            return $before_content_html . $content . PHP_EOL . $after_content_html
                . $edit_link_html . $link_name_html . $inline_help_html . $inline_tip_html;
        }
        // Full variant
        return $row_start
                    . $before_content_html . $content . PHP_EOL . $after_content_html
                    . $edit_link_html . $link_name_html . $inline_help_html . $inline_tip_html
                    . $this->_add_rich_editor($extra, $replace, $form)
                . $row_end;
    }

    /**
     * @param mixed $content
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $form
     */
    public function form_dd_row($content, $extra = [], $replace = [], $form = null)
    {
        // TODO
        return $this->form_row($content, $extra, $replace, $form = null);
    }

    /**
     * @param mixed $extra
     * @param mixed $replace
     * @param mixed $form
     */
    public function _add_rich_editor($extra = [], $replace = [], $form = null)
    {
        $out = [];
        if (isset($extra['ace_editor']) && $extra['ace_editor']) {
            $out[] = $form->_ace_editor_html($extra, $replace);
        }
        if (isset($extra['ckeditor']) && $extra['ckeditor']) {
            $out[] = $form->_ckeditor_html($extra, $replace);
        }
        if (isset($extra['tinymce']) && $extra['tinymce']) {
            $out[] = $form->_tinymce_html($extra, $replace);
        }
        return implode(PHP_EOL, $out);
    }
}
