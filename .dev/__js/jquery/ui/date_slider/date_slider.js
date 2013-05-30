$(function(){

	if($("#date_slider").size() !== 0){

	  /* Slider customized for date interval picking */
	  // Date slider default values
	  var limit_interval = 30 //days
	  var slider_start = -30;
	  if(stats_period_view !== undefined && stats_period_view !== ""){
			slider_start = (-1)*stats_period_view;
			limit_interval = stats_period_view;
	  }
	  var slider_end	 =	0;
	}


	/* 1 day = 2px */
//	var	now		= new Date("2009-02-15");
	var	now		= new Date();             
	Date.format = 'yyyy-mm-dd';
	if (months_abbr) {
		Date.abbrMonthNames = months_abbr;
	}
	var day 	= now.getDate();
	var month 	= now.getMonth() + 1;
	var year 	= now.getFullYear();
	//alert(now + "#" + day + "#" +  month + "#" + year);
	for(i = 0; i <= 12; i++){
		month_str = month - i;
		css_class = "slider_month";
	//alert(i + "-" +month +"--" + month_str);
		if (month_str <= 0){
			month_str = month_str + 12;
			css_class = "slider_month2";
		}
		month_str = now.getMonthName(true);
		now.addMonths(-1);

		if (i == 0){
			$("#date_slider").append("<div class='"+ css_class +"' style='width:" + (day*2) + "px;'>&nbsp;" + month_str + "</div>");
		} else {
			if (i == 12) {
				$("#date_slider").append("<div class='"+ css_class +"' style='width:" + ((30 - day)*2) + "px;border:0;'>&nbsp;</div>");
			} else { 
				$("#date_slider").append("<div class='"+ css_class +"'>&nbsp;" + month_str + "</div>");
			}
		}
	}

	year_label_pos = day * 2 + 30 * 2 * (month - 1) - 20;
	if (year_label_pos < 0 ) {
		year_label_pos = 0;
	}
	show_year_label2 = true;
	if (year_label_pos > 650 ) {
		show_year_label2 = false;
	}
	year_label = "<div style='position:absolute; bottom:0;right:" + year_label_pos + "px;'>" + year + "</div>";
	$("#date_slider").append(year_label);
	if (show_year_label2) {
		year_label2 = "<div style='position:absolute; bottom:0;left:0px;'>" + (year - 1) + "</div>";
		$("#date_slider").append(year_label2);

	}

	now = new Date();
	cur_day_num = now.getDayOfYear();
	// dates to objects
	if (typeof(date_from) != "undefined") {
		start_date = Date.fromString(date_from);
		if (start_date.getFullYear() == year)	{
			start_val = start_date.getDayOfYear() - cur_day_num;
		} else {
			start_val = -1 * (365 - start_date.getDayOfYear() + cur_day_num);
		}
		if (start_val <=  (limit_interval * -1) && start_val > -365) {
			slider_start = start_val;
		}
	}
	if (typeof(date_to) != "undefined") {
		end_date = Date.fromString(date_to);
		if (end_date.getFullYear() == year)	{
			end_val = end_date.getDayOfYear() - cur_day_num;
		} else {
			end_val = -1 * (365 - end_date.getDayOfYear() + cur_day_num);
		}

		if (Math.abs(slider_start - end_val) < limit_interval){
			end_val = end_val + limit_interval;
		}
		if (end_val <  0 && end_val > (-365 + limit_interval)) {
			slider_end = end_val;
		}
	}
	var start_limit = -365;
	if (typeof(first_date) != "undefined") {
		start_limit = Date.fromString(first_date);
		if (start_limit.getFullYear() == year)	{
			start_limit = start_limit.getDayOfYear() - cur_day_num;	
		} else if((year - start_limit.getFullYear()) == 1) {
			start_limit = -1 * (365 - start_limit.getDayOfYear() + cur_day_num);	
		}
	}

	if(!$("#date_interval_text").text()) {
		// Fill default date interval
		_date_from = _localized_date(slider_start);
		_date_to = _localized_date(slider_end);
		$("#date_interval_text").append(_date_from + " - " + _date_to);
	}
	$("#date_slider").slider({
		range: true,
		min: -365,
		max: 0,
		step: 1,
		values: [slider_start, slider_end],
		orientation: 'horizontal',
		start: function(event, ui) {
			handle1 = ui.values[0];
			handle2 = ui.values[1];
		},
		slide: function(event, ui) {

			if (Math.abs(parseInt(ui.values[1]) - parseInt(ui.values[0])) < limit_interval) {
				if (ui.values[0] == handle1) {
					ui.values[1] = ui.values[0] + limit_interval;
				} else {
					ui.values[0] = ui.values[1] - limit_interval;
				}
				return false;
			}

			if (ui.values[0] < start_limit) {
				ui.values[0] = start_limit;
				return false;
			}

			var now1 = new Date();
			d1 = now1.addDays(parseInt(ui.values[0]));
			date_from = now1.asString(d1);

			var now2 = new Date();
			d2 = now2.addDays(parseInt(ui.values[1]));
			date_to = now2.asString(d2);

			_date_from = _localized_date(ui.values[0]);
			_date_to = _localized_date(ui.values[1]);
			$("#date_interval_text").text(_date_from + " - " + _date_to);

		},
		stop: function(event, ui) {
			if (handle1 == ui.values[0] && handle2 == ui.values[1]) {
				return false;
			}
			new_url = date_url(date_from, date_to);
		}
	});

	$("#apply_date_interval").click(function(){
		new_url = date_url(date_from, date_to);
		window.location.href = new_url;
		return false;
	});

});

function date_url(date_from, date_to){
		var url = window.location.href;
		url = url.replace(/\&date_from=[^\&]+/ig, "");
		url = url.replace(/\&date_to=.+$/ig, "");
		new_url = url + "&date_from=" + date_from + "&date_to=" + date_to;
		return new_url;
}

function count( mixed_var, mode ) {    // Count elements in an array, or properties in an object
    // 
    // +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +      input by: _argos
 
    var key, cnt = 0;
 
    if( mode == 'COUNT_RECURSIVE' ) mode = 1;
    if( mode != 1 ) mode = 0;
 
    for (key in mixed_var){
        cnt++;
        if( mode==1 && mixed_var[key] && (mixed_var[key].constructor === Array || mixed_var[key].constructor === Object) ){
            cnt += count(mixed_var[key], 1);
        }
    }
 
    return cnt;
}

//Localize date from slider value format "-365" to format "15 May 2009" for example
function _localized_date (_days) {
	cur_date = new Date();
	Date.format = 'yyyy-mm-dd';
	var _localized = cur_date.addDays(parseInt(_days));
	_month = _localized.getMonthName(true);
	_localized = _localized.asString();
	_localized = _localized.replace(/-\d{2}-/, " " + _month + " ");
	return  _localized;
}

function explode( delimiter, string ) {
    var emptyArray = { 0: '' };
 
    if ( arguments.length != 2
        || typeof arguments[0] == 'undefined'
        || typeof arguments[1] == 'undefined' )
    {
        return null;
    }
 
    if ( delimiter === ''
        || delimiter === false
        || delimiter === null )
    {
        return false;
    }
 
    if ( typeof delimiter == 'function'
        || typeof delimiter == 'object'
        || typeof string == 'function'
        || typeof string == 'object' )
    {
        return emptyArray;
    }
 
    if ( delimiter === true ) {
        delimiter = '1';
    }
 
    return string.toString().split ( delimiter.toString() );
}

