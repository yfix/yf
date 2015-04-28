<?php

class yf_form2_ace_editor {

	/**
	*/
	function _ace_editor_html($extra = array(), $replace = array(), $form) {
		$extra['id'] = $extra['id'] ?: 'editor_html';
		asset('ace-editor');
		$opts = array(
			'fontSize' => ($extra['ace_editor']['font-size'] ?: '16px'),
#			'theme' => 'ace/theme/'.($extra['ace_editor']['theme'] ?: 'tomorrow_night'),
#			'mode' => 'ace/mode/'.($extra['ace_editor']['mode'] ?: 'html'),
		);
		$jq = '
			try {
				var ace_editor = ace.edit("'.addslashes($extra['id']).'");
				ace_editor.setTheme("ace/theme/'.($extra['ace_editor']['theme'] ?: 'tomorrow_night').'");
				ace_editor.getSession().setMode("ace/mode/'.($extra['ace_editor']['mode'] ?: 'html').'");
				ace_editor.setPrintMarginColumn(false);
				ace_editor.setOptions('.str_replace('\/', '/', json_encode($opts)).');
				$("#'.addslashes($extra['id']).'").data("ace_editor", ace_editor);
			} catch (e) {
				console.log(e)
			}
		';
		jquery($jq);
		return $body;
	}
}