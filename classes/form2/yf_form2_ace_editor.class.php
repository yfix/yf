<?php

class yf_form2_ace_editor {

	/**
	*/
	function _ace_editor_html($extra = array(), $replace = array(), $form) {
		$extra['id'] = $extra['id'] ?: 'editor_html';
		asset('ace-editor');
		jquery('
			try {
				var ace_editor = ace.edit("'.addslashes($extra['id']).'");
				ace_editor.setTheme("ace/theme/'.($extra['ace_editor']['theme'] ?: 'tomorrow_night').'");
				ace_editor.getSession().setMode("ace/mode/'.($extra['ace_editor']['mode'] ?: 'html').'");
				ace_editor.setFontSize("'.($extra['ace_editor']['font-size'] ?: '16px').'");
				ace_editor.setPrintMarginColumn(false);
				$("#'.addslashes($extra['id']).'").data("ace_editor", ace_editor);
			} catch (e) {
				console.log(e)
			}
		');
		return $body;
	}
}