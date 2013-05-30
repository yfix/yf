<?

		$box .= "<br />";
		$box .= common()->select_box("piersings", array("Nose","Nipple","Tongue","Genitals","Naval","Other"), array("Nose"=>1,"Other"=>1), 0, 1);
		$box .= "<br />";
		$box .= common()->radio_box("piersings", array("Nose","Nipple","Tongue","Genitals","Naval","Other"), array("Nose"=>1,"Other"=>1), 0, 1);
		$box .= "<br />";
		$box .= common()->select_box("piersings", array("Nose","Nipple","Tongue","Genitals","Naval","Other"), array("Nose"=>1,"Other"=>1), 0, 1, "size=4");
		$box .= "<br />";
		$box .= common()->multi_check_box("piersings", array("Nose","Nipple","Tongue","Genitals","Naval","Other"), array("Nose"=>1,"Other"=>1), 0, 1);
		$box .= "<br />";
		$box .= common()->multi_select("piersings", array("Nose","Nipple","Tongue","Genitals","Naval","Other"), array("Nose","Other"), 0, 1, "size=6");
		$box .= "<br />";

		return $box. $body;

?>