<?php

/**
* USA zip codes locator
* 
* @package		Profy Framework
* @author		Yuri Vysotskiy <profy.net@gmail.com>
* @version		1.0
* @revision	$Revision$
*/
class profy_zip_codes {

	/** @var int Use miles or kilometers as default measure (1 = miles, 0 = kilometers) */
	var $USE_MILES_AS_MEASURE = 1;

	/**
	* This method returns the distance in Miles between two zip codes, 
	* if either of the zip code is not found and error is retruned
	* 
	* @access	public
	* @param	$zip_1	string	The first zip code
	* @param	$zip_2	string	The second zip code
	* @return	string
	*/
	function get_distance($zip_1, $zip_2) {
		// Need two zip codes to process
		if (empty($zip_1) || empty($zip_2)) {
			return false;
		}
		// Get info about these two zip codes
		$Q = db()->query("SELECT * FROM `".db('zip_data')."` WHERE `id` IN('"._es($zip_1)."', '"._es($zip_2)."')");
		while ($A = db()->fetch_assoc($Q)) {
			// Get values in radians (Convert all the degrees to radians)
			$lat[] = $this->_degrees_into_radians($A["lat"]);
			$lon[] = $this->_degrees_into_radians($A["lon"]);
		}
		// Check how many records found
		if (count($lat) < 2) {
			return false;
		}
		// Find the deltas
		$delta_lat = $lat[1] - $lat[0];
		$delta_lon = $lon[1] - $lon[0];
		// Set Earth radius
		$EARTH_RADIUS = 3956;
		if (!$this->USE_MILES_AS_MEASURE) {
			$EARTH_RADIUS = $this->_miles_into_kilometers($EARTH_RADIUS);
		}
		// Find the Great Circle distance
		$temp = pow(sin($delta_lat / 2.0), 2) + cos($lat[0]) * cos($lat[1]) * pow(sin($delta_lon / 2.0), 2);
		return ($EARTH_RADIUS * 2 * atan2(sqrt($temp), sqrt(1 - $temp)));
	}

	/**
	* This method returns an array of zipcodes found with the radius supplied in miles, 
	* if the zip code is invalid an error string is returned
	* 
	* @access	public
	* @param	$zip_code	string	The zip code
	* @param	$radius		float	The radius in miles
	* @return	array				Array of zip codes inside given radius
	*/
	function get_in_radius($zip_code, $radius) {
		$zip_codes_array = array();
		// Prepare SQL code
		$sql = $this->_generate_sql($zip_code, $radius);
		// Try to get zip codes array
		if (!empty($sql)) {
			$Q = db()->query($sql);
			while ($A = db()->fetch_assoc($Q)) {
				$zip_codes_array[$A["id"]] = $A["id"];
			}
		}
		return $zip_codes_array;
	}

	/**
	* Generate SQL code for "get_in_radius" method
	* 
	* @access	public
	* @param	$zip_code	string	The zip code
	* @param	$radius		float	The radius in miles
	* @return	string				SQL code for database query
	*/
	function _generate_sql($zip_code, $radius) {
		// Try ot get current zip code info in database
		$CUR_ZIP_CODE = db()->query_fetch("SELECT * FROM `".db('zip_data')."` WHERE `id`='"._es($zip_code)."'");
		// Check if zip code found
		if (empty($CUR_ZIP_CODE["id"])) {
			return false;
		}
		// Prepare radius
		$radius = floatval($radius);
		if (!$this->USE_MILES_AS_MEASURE) {
			$radius = $this->_kilometers_into_miles($radius);
		}
		// Try to get array of zip codes inside given radius
		return "SELECT `id` FROM `".db('zip_data')."` WHERE (POW((69.1 * (`lon` - '"._es($CUR_ZIP_CODE["lon"])."') * cos("._es($CUR_ZIP_CODE["lat"])." / 57.3)), '2') + POW((69.1 * (`lat` - '"._es($CUR_ZIP_CODE["lat"])."')), '2')) < (".floatval($radius)." * ".floatval($radius).")";
	}

	/**
	* Converts degrees into radians
	* 
	* @access	private
	* @param	$degrees	float	degrees to convert
	* @return	float				radians
	*/
	function _degrees_into_radians($degrees = 0.0) {
		return ($degrees * M_PI / 180.0);
	}

	/**
	* Converts miles into kilometers
	* 
	* @access	private
	* @param	$miles	float	miles to convert
	* @return	float			kilometers
	*/
	function _miles_into_kilometers($miles = 0.0) {
		return ($miles * 1.609);
	}

	/**
	* Converts kilometers into miles
	* 
	* @access	private
	* @param	$kilometers	float	kilometers to convert
	* @return	float				miles
	*/
	function _kilometers_into_miles($kilometers = 0.0) {
		return ($kilometers / 1.609);
	}
}
