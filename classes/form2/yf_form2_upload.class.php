<?php

class yf_form2_upload {

	function upload( $name = '', $desc = '', $extra = array(), $replace = array(), $__this ) {
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		$extra['name'] = $extra['name'] ?: ($name ?: 'image');
		$extra['desc'] = $extra['desc'] ?: ($desc ?: ucfirst(str_replace('_', ' ', $extra['name'])));
		$func = function( $_extra, $_replace, $_this ) {
			css(array(
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/css/jquery.fileupload.css',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/css/jquery.fileupload-ui.css',
			));
			js(array(
				'//cdn.rawgit.com/yfix/JavaScript-Load-Image/master/js/load-image.all.min.js',
				'//cdn.rawgit.com/yfix/JavaScript-Canvas-to-Blob/master/js/canvas-to-blob.min.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.iframe-transport.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-ui.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-process.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-image.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-audio.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-video.js',
				'//cdn.rawgit.com/yfix/jQuery-File-Upload/master/js/jquery.fileupload-validate.js',
			));
			$r = array(
				'item'   => $_replace,
				'option' => $_extra,
			);
			$result = tpl()->parse( 'form2/upload', $r );
			return( $_this->_row_html( $result, $_extra, $_replace ) );
		};
		if ($__this->_chained_mode) {
			$__this->_body[] = array( 'func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__ );
			return $__this;
		}
		return( $func( $extra, $replace, $__this ) );
	}

}
