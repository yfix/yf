<?php

return ['versions' => ['master' => [
	// Ideas from here: https://gist.github.com/yfix/fefe5204bf42e29884724bb8d40ffb3b
	// Usage: $form.find("input[type="checkbox"]").shiftSelectable();
	// replace input[type="checkbox"] with the selector to match your list of checkboxes
	'js' => '
		$.fn.yf_shift_selectable = function() {
			var last_checked,
				$boxes = this;

			$boxes.click(function(evt) {
				if(!last_checked) {
					last_checked = this;
					return;
				}
				if(evt.shiftKey) {
					var start = $boxes.index(this),
						end = $boxes.index(last_checked);
					$boxes.slice(Math.min(start, end), Math.max(start, end) + 1)
						.prop("checked", last_checked.checked)
						.trigger("change");
				}
				last_checked = this;
			});
		};
		',
		'jquery' => '
			$("form").find("input[type=checkbox]").yf_shift_selectable();
		',
	]],
	'require' => [
		'asset' => 'jquery',
	],
];
