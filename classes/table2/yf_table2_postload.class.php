<?php

/**
* Table2 plugin
*/
class yf_table2_postload {

	/**
	*/
	function postload($params = array(), $_this) {
		$attr_postload_url = $params['attr_postload_url'] ?: 'postload-url';
		$tr_id_key = $params['tr_id_key'] ?: 'name';

		$jquery = '
			var attr_postload_url = "'.$attr_postload_url.'"
				, tr_id_key = "'.$tr_id_key.'"
			;
			var process_ajax_data_func = function(data, table) {
				var th_id_prefix = "th_"
					, th_id_cut_len = th_id_prefix.length
					, tr_id_prefix = "tr_"
					, tr_id_cut_len = tr_id_prefix.length
					, th_positions = { }
				;
				table.find("th").each(function(i) {
					var th_id = $(this).attr("id");
					if (th_id && th_id.substring(0, th_id_cut_len) == th_id_prefix) {
						th_positions[th_id.substring(th_id_cut_len)] = i;
					}
				})
				table.find("tr").each(function(i) {
					var tr = $(this)
						, tr_id = tr.attr("id")
					;
					if (tr_id && tr_id.length && tr_id.substring(0, tr_id_cut_len) == tr_id_prefix) {
						tr_id = tr_id.substring(tr_id_cut_len)
					}
					if (!tr_id) {
						return;
					}
					for (key1 in data) {
						var td = tr.find("td").eq(th_positions[key1])
							, ajax_arr = data[key1][tr_id]
							, td_a = td.find("a")
						;
						if (td_a) {
							td_a.text(ajax_arr)
						} else {
							td.text(ajax_arr)
						}
					}
				})
			};
			$("table[data-" + attr_postload_url + "]").each(function(){
				var table = $(this)
					, url = table.data(attr_postload_url)
					, spinner = table.before("<i class=\"icon icon-spinner icon-2x icon-spin fa fa-spinner fa-2x fa-spin\" id=\"table_ajax_spinner\" style=\"position:fixed;\"></i>").prev()
				;
				$.post(url, function(data) {
					process_ajax_data_func(data, table);
					spinner.remove();
				})
			});
		';
		_class('core_events')->listen('show_js.append', function() use ($jquery) {
			return '<script type="text/javascript">'.PHP_EOL.'$(function(){'.PHP_EOL.$jquery.PHP_EOL.'})'.PHP_EOL.'</script>'.PHP_EOL;
		});
		return $_this;
	}
}
