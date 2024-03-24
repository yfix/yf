<?php

class yf_htmx {

  public $is_hx   = null;

  public $req = [
    'request'      => false,
    'boosted'      => false,
    'target'       => '',
    'trigger'      => '',
    'trigger_name' => '',
    'current_url'  => '',
  ];

  public $current_url  = null;

  public $target       = null;
  public $trigger      = null;
  public $trigger_name = null;
  public $boosted      = null;

  public $_api;

  function _init() {
    $this->_api = _class( 'api' );
    $this->_api->JSON_VULNERABILITY_PROTECTION = false;
    $this->_is_hx();
    $this->target  = @$_SERVER[ 'HTTP_HX_TARGET'  ];
    $this->boosted = @$_SERVER[ 'HTTP_HX_BOOSTED' ];
    $this->_get_req();
  }

  function _is_hx() {
    $r = (bool) @$_SERVER[ 'HTTP_HX_REQUEST' ];
    $this->is_hx = $r;
    return( $r );
  }

  function _get_req() {
    foreach( $this->req as $k => &$v ) {
      $key = 'HTTP_HX_'. strtoupper( $k );
      if( isset( $_SERVER[ $key ] ) ) {
        $v = $_SERVER[ $key ];
      }
    }
  }

  function _firewall() {
    if( !$this->is_hx ) { $this->_reject(); }
    return( $this->is_hx );
  }

  function _reject() {
    no_graphics( true );
    $this->_api->_reject();
  }

  function _forbidden() {
    no_graphics( true );
    $this->_api->_forbidden();
  }

    public function _error()
    {
        no_graphics(true);
        $this->_api->_error();
    }

    public function _location($options)
    {
        no_graphics(true);
        header('HX-Location: ' . json_encode($options,true));
        exit;
    }

  function _redirect( $url ) {
    no_graphics( true );
    header( 'HX-Redirect: '. $url );
    exit;
  }

  function _redirect_login() {
    $this->_redirect( '/login/' );
  }

  function _refresh() {
    no_graphics( true );
    header( 'HX-Refresh: true' );
    exit;
  }

  function _robot_none() {
    return( $this->_api->_robot_none() );
  }

  function _cache( $options = null ) {
    return( $this->_api->_cache( $options ) );
  }

  function _cache_none() {
    return( $this->_api->_cache([ 'no-store' => true ]) );
  }

  function _result( $r ) {
    no_graphics( true );
    $this->_robot_none();
    echo $r;
    exit;
  }

}
