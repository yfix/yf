<?php

//-----------------------------------------------------------------------------
// Boxes container
class yf_boxes {

	//-----------------------------------------------------------------------------
	// YF module constructor
	function _init () {
		$this->_get_data();
		// Array of select boxes to process
		$this->_boxes = array(
			"country"			=> 'select_box("country",		$this->_countries,		$selected, false, 2)',
			"state"				=> 'select_box("state",			$this->_states,			$selected, " ")',
			"mode_type"			=> 'select_box("mode_type",		$this->_mode_types,		$selected, false, 2, "", false)',
			"mood"				=> 'select_box("mood",			$this->_moods,			$selected, false, 2, "", false)',
			"privacy"			=> 'select_box("privacy",		$this->_privacy_types,	$selected, false, 2, "", false)',
			"allow_comments"	=> 'select_box("allow_comments",$this->_comments_types,	$selected, false, 2, "", false)',
			"content_level"		=> 'select_box("content_level",	$this->_content_levels,	$selected, false, 2, "", false)',
			"race"				=> 'select_box("race",			$this->_races,			$selected, " ")',
			"hair_color"		=> 'select_box("hair_color",	$this->_hair_colors,	$selected, " ")',
			"eye_color"			=> 'select_box("eye_color",		$this->_eye_colors,		$selected, " ")',
			"gender"			=> 'radio_box("gender",			$this->_gender,			$selected, "")',
			"orientation"		=> 'select_box("orientation",	$this->_orientations,	$selected, " ", 2, "", 0)',
			"race"				=> 'select_box("race",			$this->_races,			$selected, " ", 2, "", 0)',
			"star_sign"			=> 'select_box("star_sign",		$this->_star_signs,		$selected, " ", 2, "", 0)',
			"smoking"			=> 'select_box("smoking",		$this->_smoking,		$selected, " ", 2, "", 0)',
			"english"			=> 'select_box("english",		$this->_english,		$selected, " ", 2, "", 0)',
			"height"			=> 'select_box("height",		$this->_heights,		$selected, " ", 2, "", false)',
			"weight"			=> 'select_box("weight",		$this->_weights,		$selected, " ", 2, "", false)',
		);
	}

	//-----------------------------------------------------------------------------
	// Get data for boxes
	function _get_data () {
		// Fill array of states
		$this->_states = main()->get_data("states");
		$this->_states = my_array_merge(array(" " => "Non US"), $this->_states);
		// Fill array of countries
		$this->_countries = main()->get_data("countries");
		$this->_countries = my_array_merge(array(" " => " "), $this->_countries);
		// Process featured countries if needed
		if (FEATURED_COUNTRY_SELECT == 1) {
			$this->_featured_countries = main()->get_data("featured_countries");
			if ($this->_featured_countries) {
				$this->_featured_countries = my_array_merge(array("  " => "  "), $this->_featured_countries);
				$this->_countries = my_array_merge($this->_featured_countries, $this->_countries);
			}
		}
		// Fill array of heights
		$this->_heights = main()->get_data("heights");
		// Fill array of weights
		$this->_weights = main()->get_data("weights");
		// Fill array of statuses
		$this->_statuses = array("Independent" => translate("Independent"),"Agency"	=> translate("Agency"));
		// Fill array of ages
		for ($i = 18; $i <= 75; $i++) {
			$this->_ages[$i] = $i;
		}
		// Fill array of orientations
		$this->_orientations = array(
			"Heterosexual"	=> translate("Heterosexual"), 
			"Homosexual"	=> translate("Homosexual"),
			"Bisexual"		=> translate("Bisexual"),
		);
		// Fill array of races
		$this->_races = array(
			"Asian"				=> translate("Asian"),
			"Black"				=> translate("Black"),
			"Caucasian"			=> translate("Caucasian"),
			"Caucasian (White)"	=> translate("Caucasian (White)"),
			"East Indian"		=> translate("East Indian"),
			"Hispanic"			=> translate("Hispanic"),
			"Middle Eastern"	=> translate("Middle Eastern"),
			"Native American"	=> translate("Native American"),
			"Other"				=> translate("Other"),
		);
		// Fill array of star_signs
		$this->_star_signs = array(
			"Aries"			=> translate("Aries"),
			"Taurus"		=> translate("Taurus"),
			"Gemini"		=> translate("Gemini"),
			"Cancer"		=> translate("Cancer"),
			"Leo"			=> translate("Leo"),
			"Virgo"			=> translate("Virgo"),
			"Libra"			=> translate("Libra"),
			"Scorpio"		=> translate("Scorpio"),
			"Sagittarius"	=> translate("Sagittarius"),
			"Capricorn"		=> translate("Capricorn"),
			"Aquarius"		=> translate("Aquarius"),
			"Pisces"		=> translate("Pisces"),
		);
		// Fill array of smoking
		$this->_smoking = array(
			"No"		=> translate("No"),
			"Social"	=> translate("Social"),
			"Yes"		=> translate("Yes"),
		);
		// Fill array of english
		$this->_english = array(
			"Native"		=> translate("Native"),
			"Fluent"		=> translate("Fluent"),
			"Basic Skills"	=> translate("Basic Skills"),
			"No"			=> translate("No"),
		);
		// Fill array of hair colors
		$this->_hair_colors = array(
			"Black"			=> translate("Black"),
			"Brown"			=> translate("Brown"),
			"Brunette"		=> translate("Brunette"),
			"Chestnut"		=> translate("Chestnut"),
			"Charcoal"		=> translate("Charcoal"),
			"Mixed"			=> translate("Mixed"),
			"Auburn"		=> translate("Auburn"),
			"DirtyBlonde"	=> translate("DirtyBlonde"),
			"Blonde"		=> translate("Blonde"),
			"Golden"		=> translate("Golden"),
			"Red"			=> translate("Red"),
			"Blue"			=> translate("Blue"),
			"Gray"			=> translate("Gray"),
			"Silver"		=> translate("Silver"),
			"White"			=> translate("White"),
		);
		// Fill array of eye colors
		$this->_eye_colors = array(
			"Black"	=> translate("Black"),
			"Brown"	=> translate("Brown"),
			"Gray"	=> translate("Gray"),
			"Hazel"	=> translate("Hazel"),
			"Blue"	=> translate("Blue"),
			"Green"	=> translate("Green"),
		);
	}

	//-----------------------------------------------------------------------------
	// YF module constructor
	function box ($params = array()) {
		$name		= $params["name"];
		$selected	= $params["selected"];
		if (empty($name) || empty($this->_boxes[$name])) {
			return false;
		} else {
			return eval("return common()->".$this->_boxes[$name].";");
		}
	}
}
