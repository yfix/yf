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
			require_css(array(
				'//cdn.rawgit.com/blueimp/jQuery-File-Upload/9.5.7/css/jquery.fileupload.css',
				'//cdn.rawgit.com/blueimp/jQuery-File-Upload/9.5.7/css/jquery.fileupload-ui.css',
			));
			require_js( array(
				'//blueimp.github.io/JavaScript-Load-Image/js/load-image.min.js',
				'//blueimp.github.io/JavaScript-Canvas-to-Blob/js/canvas-to-blob.min.js',
				'//cdn.rawgit.com/blueimp/jQuery-File-Upload/9.5.7/js/jquery.iframe-transport.js',
				'//cdn.rawgit.com/blueimp/jQuery-File-Upload/9.5.7/js/jquery.fileupload.js',
				'//cdn.rawgit.com/blueimp/jQuery-File-Upload/9.5.7/js/jquery.fileupload-ui.js',
				'//cdn.rawgit.com/blueimp/jQuery-File-Upload/9.5.7/js/jquery.fileupload-process.js',
				'//cdn.rawgit.com/blueimp/jQuery-File-Upload/9.5.7/js/jquery.fileupload-image.js',
				'//cdn.rawgit.com/blueimp/jQuery-File-Upload/9.5.7/js/jquery.fileupload-audio.js',
				'//cdn.rawgit.com/blueimp/jQuery-File-Upload/9.5.7/js/jquery.fileupload-video.js',
				'//cdn.rawgit.com/blueimp/jQuery-File-Upload/9.5.7/js/jquery.fileupload-validate.js',
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
