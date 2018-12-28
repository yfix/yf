<?php

return ['versions' => ['master' => ['jquery' => <<<END
	$("form").each(function() {
		var form = this;

		// Suppress the default bubbles
		form.addEventListener("invalid", function(event) {
			event.preventDefault();
		}, true);

		// Support Safari, iOS Safari, and the Android browser—each of which do not prevent form submissions by default
		$(form).on("submit", function(event) {
			if (!this.checkValidity()) {
				event.preventDefault();
			}
		});

		$("input, select, textarea", form)
			// Destroy the tooltip on blur if the field contains valid data
			.on("change keyup", function() {
//			.on("change keyup invalid valid", function() {
				yf_on_validate_change($(this));
			})
			.on("blur", function() {
				var field = $(this);
				if (this.validity.valid) {
					field.popover("destroy");
				} else {
					field.popover("hide");
				}
			})
			.on("focus", function() {
				$(this).popover("show");
			})
		;

		$("button:not([type=button]), input[type=submit]", form).on("click", function(event) {
			// Destroy any tooltips from previous runs
			$("input, select, textarea", form).each(function() {
				$(this).popover("destroy");
			});
			// Add a tooltip to each invalid field
			var invalidFields = $(":invalid", form).each(function() {
				var field = $(this).popover({
					trigger: "hover",
					placement: "bottom",
					html: true,
					content: function() {
						return "<i class=\"" + yf_css_icon_error + "\"></i>&nbsp;" + field[ 0 ].validationMessage;
					}
				});
			});
			// If there are errors, give focus to the first invalid field
			invalidFields.first().trigger("focus").eq(0).focus();
		});
	});
END
]]];
