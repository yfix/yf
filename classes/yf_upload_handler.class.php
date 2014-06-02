<?php

require_once( YF_PATH . 'libs/jquery-file-upload/server/php/UploadHandler.php' );

class yf_upload_handler extends UploadHandler {

	protected $options_default = array();
	protected $url;

	public $image_width  = 1920;
	public $image_height = 1080;
	public $image_types  = array(
		'jpg'  => true,
		'jpeg' => true,
		'png'  => true,
	);

    function __construct( $options = null ) {
		parent::__construct( null, false, null );
		$this->options_default = $this->options;
	}

	function _init( $options = null ) {
		$this->reset( $options );
	}

    protected function get_url( $force = false ) {
		if( $this->url && !$force ) { return( $this->url ); }
		$base = $this->get_full_url();
		$object = input()->get( 'object' );
		$action = input()->get( 'action' );
		$uri_object  = $object ? 'object=' . $object : '';
		$uri_action  = $action ? 'action=' . $action : '';
		$uri_and = $uri_object && $uri_action ? '&' : '';
		$uri_q   = $object || $action ? '?' : '';
		$result  = sprintf( '%s/%s%s%s%s', $base, $uri_q, $uri_object, $uri_and, $uri_action );
		$this->url = $result;
		return( $result );
	}

	function reset( $options = null ) {
		$url = $this->get_url( $force = true );
		$path = PROJECT_PATH . 'uploads/';
		// prepare options
		$default = array(
			'script_url'       => $url,
			'upload_dir'       => $path,
			'mkdir_mode'       => 0775,
			'download_via_php' => true,
			'delete_type'      => 'POST',
            'image_versions'   => array(),
		);
		$this->options = (array)$options + $default + $this->options_default;
	}

	function options( $option = null, $value = null ) {
		if( is_string( $option ) ) {
			if( empty( $value ) ) {
				return( $this->options[ $option ] );
			} else {
				$this->options[ $option ] = $value;
			}
		} elseif( is_array( $option ) ) {
			$this->options = $option + $this->options;
		} elseif( empty( $option ) ) {
			return( $this->options );
		}
	}

	protected function create_image_versions( $uploads_result = null, $options = null ) {
		if( !is_array( $uploads_result ) || !is_array( $options[ 'image_versions' ] ) ) { return( false ); }
		$uploads_remove = $options[ 'upload_remove' ];
		$uploads_remove = isset( $uploads_remove ) ? (bool)$uploads_remove : true;
		$image_versions = &$options[ 'image_versions' ];
		$image_types    = &$this->image_types;
		$result = true;
		foreach( $uploads_result as $param_name => $uploads ) {
			if( empty( $image_versions[ $param_name ] ) || !is_array( $uploads ) ) { continue; }
			foreach( $uploads as $i => $upload ) {
				$file_name = @$upload->name;
				$type = strtolower( substr( strrchr( $file_name, '.' ), 1 ) );
				// check images
				if( empty( $file_name ) || empty( $image_types[ $type ] ) ) { continue; }
				$file_upload = $this->get_upload_path( $file_name );
				// create image versions
				foreach( $image_versions[ $param_name ] as $version_name => $version_options ) {
					$file       = $version_options[ 'file'       ];
					$max_width  = $version_options[ 'max_width'  ] ?: $this->image_width;
					$max_height = $version_options[ 'max_height' ] ?: $this->image_height;
					$watermark  = $version_options[ 'watermark'  ] ?: false;
					$result &= common()->make_thumb( $file_upload, $file, $max_width, $max_height, $watermark );
				}
				// remove upload file
				if( $uploads_remove ) { unlink( $file_upload ); }
			}
		}
		return( $result );
	}

	function post_handler( $options = null ) {
		$result   = $this->post( $print_response = false );
		$versions = $this->create_image_versions( $result, $options );
		$result[ 'versions' ] = $versions;
		return( $result );
	}

}
