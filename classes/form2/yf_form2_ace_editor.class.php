<?php

class yf_form2_ace_editor {

	/**
	*/
	function _ace_editor_html($extra = array(), $replace = array(), $__this) {
		$extra['id'] = $extra['id'] ?: 'editor_html';
		return '<script src="//cdnjs.cloudflare.com/ajax/libs/ace/1.1.01/ace.js" type="text/javascript"></script>
			<script type="text/javascript">
			(function(){
			try {
				var ace_editor = ace.edit("'.$extra['id'].'");
				ace_editor.setTheme("ace/theme/'.($extra['ace_editor']['theme'] ?: 'tomorrow_night').'");
				ace_editor.getSession().setMode("ace/mode/'.($extra['ace_editor']['mode'] ?: 'html').'");
				ace_editor.setFontSize("'.($extra['ace_editor']['font-size'] ?: '16px').'");
				ace_editor.setPrintMarginColumn(false);
				$("#'.$extra['id'].'").data("ace_editor", ace_editor);
			} catch (e) {
				console.log(e)
			}
			})()
			</script>
		';
	}
}