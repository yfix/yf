<?php

class yf_form2_upload {

	function upload( $name = '', $desc = '', $extra = array(), $replace = array(), $form ) {
		if (is_array($desc)) {
			$extra = (array)$extra + $desc;
			$desc = '';
		}
		$extra['name'] = $extra['name'] ?: ($name ?: 'image');
		$extra['desc'] = $extra['desc'] ?: ($desc ?: ucfirst(str_replace('_', ' ', $extra['name'])));
		$func = function( $_extra, $_replace, $form ) {
			asset('blueimp-uploader');
			$r = array(
				'item'   => $_replace,
				'option' => $_extra,
			);
			$result = tpl()->parse( 'form2/upload', $r );
			return( $form->_row_html( $result, $_extra, $_replace ) );
		};
		if ($form->_chained_mode) {
			$form->_body[] = array( 'func' => $func, 'extra' => $extra, 'replace' => $replace, 'name' => __FUNCTION__ );
			return $form;
		}
		return( $func( $extra, $replace, $form ) );
	}

}
