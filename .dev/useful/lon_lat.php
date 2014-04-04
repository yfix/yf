<?php
/*
	$geo =& new Geo($this->dbcon, null, 'San Francisco', 'CA');
	$this->assertEqual($geo->lat, 37.784827);
	$this->assertEqual($geo->long, -122.727802);
*/

//$lon = -122.727802;
//$lat = 37.784827;

// Wrong direction!!!
$lat = -122.727802;
$lon = 37.784827;

$radius = 50;

$zip_query = "
	SELECT 
		latitude
		,longitude
		, (ACOS(
			(SIN(" . $lat . "/57.2958) * SIN(latitude/57.2958)) 
			+ (COS(" . $lat . "/57.2958) * COS(latitude/57.2958) * COS(longitude/57.2958 - " . $lon. "/57.2958)))
		) * 3963 AS distance 
	FROM t_geo_city_location 
	WHERE (latitude >= " . $lat . " - (" . $radius . "/111)) 
		AND (latitude <= " . $lat . " + (" . $radius . "/111)) 
		AND (longitude >= " . $lon . "- (" . $radius . "/111)) 
		AND (longitude <= " . $lon. "+ (" . $radius . "/111)) 
	ORDER BY distance ASC;";

$zip_query .= "
	SELECT 
		lat
		,lon
		, (ACOS(
			(SIN(" . $lat . "/57.2958) * SIN(lat/57.2958)) 
			+ (COS(" . $lat . "/57.2958) * COS(lat/57.2958) * COS(lon/57.2958 - " . $lon. "/57.2958)))
		) * 3963 AS distance 
	FROM t_ads 
	WHERE (lat >= " . $lat . " - (" . $radius . "/111)) 
		AND (lat <= " . $lat . " + (" . $radius . "/111)) 
		AND (lon >= " . $lon . "- (" . $radius . "/111)) 
		AND (lon <= " . $lon. "+ (" . $radius . "/111)) 
	ORDER BY distance ASC;";

echo $zip_query;
?>