<?php

/**
* Form api
*
* @package		YF
* @author		YFix Team <yfix.dev@gmail.com>
* @version		1.0
*/
class yf_form_api {

	public $URL = '/api/form_api/';

	function user_select_box(&$name, &$values, &$extra, &$replace) {
		if( !@$extra[ 'ajax' ][ 'url' ] ) {
			$extra[ 'ajax' ][ 'url' ] = url( $this->URL . __FUNCTION__ );
		}
	}

	function _api_user_select_box( $options = null ) {
		if (!main()->ADMIN_ID) {
			return _403();
		}
		$result = array();
		// prepare query
		$db = db()->table( 'user' );
		// limit
		$page_per = 10;
		$page = (int)$_GET[ 'page' ]; $page = $page < 1 ? 1: $page;
		$offset = ( $page - 1 ) * $page_per;
		$db->limit( $page_per, $offset );
		// q
		$q = @$_GET[ 'q' ];
		$q_int = (int)$q;
		$q_is_int = $q_int > 0;
		// order
		if( $q_is_int ) {
			$db->order_by( 'id' );
		} else {
			$db->order_by( 'name' );
		}
		// prepare text fields
		$fields_text = array( 'name', 'login', 'email', 'first_name', 'last_name', 'nick', 'phone' );
		foreach( $fields_text as $item ) {
			$db->where_or( $item, 'like', _es( $q ). '%' );
		}
		// prepare int fields
		$fields_int = array( 'id' );
		if( $q_is_int ) {
			foreach( $fields_int as $item ) {
				$db->where_or( $item, '=', $q_int );
			}
		}
		// prepare select fields
		$fields = array_merge( $fields_int, $fields_text );
		$db->select( implode( ',', $fields ) );
		// DEBUG
		// $data = $db->sql(); var_dump( $data ); exit;
		// get db data
		$data = $db->get_all();
		// more?
		$more = count( $data ) == $page_per;
		// DEBUG
		// var_dump( $data ); exit;
		// empty
		if( !$data ) { return( $result ); }
		// prepare data
		$fields_json = array( 'login', 'name', 'email' );
		foreach( $data as $idx => $item ) {
			$fields = array();
			foreach( $fields_json as $field ) {
				$f = &$item[ $field ];
				if( !empty( $f ) ) {
					$fields[ $f ] = $f;
				}
			}
			$fields = empty( $fields ) ? '': implode( '; ', $fields );
			$result[] = array(
				'id' => $item[ 'id' ],
				'text' => sprintf( '%u: %s', $item[ 'id' ], $fields ),
			);
		}
		$result = array(
			'more'  => $more,
			'items' => $result
		);
		// etc
		$api = _class( 'api' );
		$api->JSON_VULNERABILITY_PROTECTION = false;
		return( $result );
	}

}
