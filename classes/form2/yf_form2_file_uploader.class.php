<?php

class yf_form2_file_uploader {

	function file_uploader($name = '', $desc = '', $extra = array(), $replace = array(), $__this) {
		if (is_array($desc)) {
			$extra += $desc;
			$desc = '';
		}
		if (!is_array($extra)) {
			$extra = array();
		}
		
		$extra['name'] = $extra['name'] ?: ($name ?: 'date');
		$extra['desc'] = $extra['desc'] ?: ($desc ?: ucfirst(str_replace('_', ' ', $extra['name'])));
		$func = function($extra, $r, $_this) {

			$replace = array(
				'multiple' => $extra['options']['max_number_of_files'] != 1 ? 1 : 0,
				'max_number_of_files' => $extra['options']['max_number_of_files'],
				'url' => './?object='.$_GET['object'].'&action=ajax_file_uploader&id='.$_GET['id'],
			);
			
			$body = tpl()->parse('form2/file_uploader',$replace);
			
// TODO: use this CDN for JS and CSS: http://cdnjs.com/libraries/blueimp-file-upload/
			
			// todo: move sources to repository
			css(array(
#				'http://blueimp.github.io/Gallery/css/blueimp-gallery.min.css',
				'//cdnjs.cloudflare.com/ajax/libs/blueimp-file-upload/9.5.2/css/jquery.fileupload.min.css',
				'//cdnjs.cloudflare.com/ajax/libs/blueimp-file-upload/9.5.2/css/jquery.fileupload-ui.min.css',
			));
			
			js(array(
#				'http://localhost/fileupload_tmp/js/vendor/jquery.ui.widget.js',
#				'http://blueimp.github.io/JavaScript-Load-Image/js/load-image.min.js',
#				'http://blueimp.github.io/JavaScript-Canvas-to-Blob/js/canvas-to-blob.min.js',
#				'http://blueimp.github.io/Gallery/js/jquery.blueimp-gallery.min.js',
				'//cdnjs.cloudflare.com/ajax/libs/blueimp-file-upload/9.5.2/jquery.iframe-transport.min.js',
				'//cdnjs.cloudflare.com/ajax/libs/blueimp-file-upload/9.5.2/jquery.fileupload.min.js',
				'//cdnjs.cloudflare.com/ajax/libs/blueimp-file-upload/9.5.2/jquery.fileupload-ui.min.js',
#				'http://localhost/fileupload_tmp/js/jquery.fileupload-image.js',
#				'http://localhost/fileupload_tmp/js/jquery.fileupload-audio.js',
#				'http://localhost/fileupload_tmp/js/jquery.fileupload-video.js',
#				'http://localhost/fileupload_tmp/js/jquery.fileupload-validate.js',
#				'http://localhost/fileupload_tmp/js/jquery.fileupload-angular.js',				
			));

/*
			$body = '<input id="fileupload" type="file" name="files[]" data-url="server/php/" multiple>';
			js(array(
				'//cdnjs.cloudflare.com/ajax/libs/blueimp-file-upload/9.5.2/jquery.iframe-transport.min.js',
				'//cdnjs.cloudflare.com/ajax/libs/blueimp-file-upload/9.5.2/jquery.fileupload.min.js',
			));
			js(
				'$(function(){
					$("#fileupload").fileupload({
						dataType: "json",
						done: function (e, data) {
							$.each(data.result.files, function (index, file) {
								$("<p/>").text(file.name).appendTo(document.body);
							});
						}
					});
				});'
			);
*/
			return $_this->_row_html($body, $extra, $r);
		};
		if ($__this->_chained_mode) {
			$__this->_body[] = array('func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__);
			return $__this;
		}
		return $func($extra, $replace, $__this);
	}	
}