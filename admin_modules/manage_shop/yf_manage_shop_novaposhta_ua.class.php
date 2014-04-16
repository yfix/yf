<?php

class yf_manage_shop_novaposhta_ua {

	private $_class_price          = false;

	function _init() {
		$this->_class_price = _class( '_shop_price', 'modules/shop/' );
		$this->is_post = main()->is_post();
		$this->is_init = (int)main()->_get( 'init' );
	}

	function novaposhta_ua__import() {
		// get data
		$url = 'http://novaposhta.ua/public/files/xls/warenhouses_ru.xls';
		$content = common()->get_remote_page( $url );
		// save to temp file
		$file_path = sys_get_temp_dir() ?: '/tmp';
		$file_name = tempnam( $file_path, 'import' );
		$file = fopen( $file_name, 'w+' ); fwrite( $file, $content ); fclose( $file );
		// init Excel reader
		if( file_exists( YF_PATH.'libs/phpexcel/PHPExcel.php' ) ) {
			require_once( YF_PATH.'libs/phpexcel/PHPExcel.php' );
		} else {
			require_once( INCLUDE_PATH.'libs/phpexcel/PHPExcel.php' );
		}
		// parse file
		$reader = PHPExcel_IOFactory::createReader( 'Excel5' );
		$reader->setReadDataOnly( true );
		try {
			$excel = $reader->load( $file_name );
			// $nullValue = null, $calculateFormulas = true, $formatData = true, $returnCellRef = false
			$data = $excel->getActiveSheet()->toArray( null, false, false, false );
		} catch ( Exception $e ) {
			$data = null;
		}
		// free memory
		unset( $excel, $reader );
		unlink( $file_name );
		// prepare data
		if( empty( $data ) ) { return( 'Не найдено данных по адресу: ' . $url ); }
		$count = 0;
		$sql_data = array();
		foreach( $data as $r ) {
			if( ( empty( $r[ 0 ] ) && empty( $r[ 1 ] ) ) || $r[ 0 ] == 'Мiсто' ) { continue; }
			$count++;
			$sql_data[] = array(
				'city'       => $r[ 0 ],
				'address'    => $r[ 1 ],
				'tel'        => $r[ 2 ],
				'time_in_1'  => $r[ 3 ],
				'time_in_2'  => $r[ 4 ],
				'time_out_1' => $r[ 5 ],
				'time_out_2' => $r[ 6 ],
			);
		}
		$table_name = db( 'shop_novaposhta_ua' );
		$count_in_db = (int)db()->get_one( "SELECT COUNT(*) FROM $table_name" );
		db()->insert_on_duplicate_key_update( $table_name, _es( $sql_data ) );
		$result = table( array(
			array( 'title' => 'Обработано: ', 'count' => $count )                ,
			array( 'title' => 'Добавлено: ' , 'count' => $count - $count_in_db ),
		))
		->text( 'title', 'Операция' )
		->text( 'count', 'Количество' )
		;
		return( $result );
	}

}
