<?php

return function() {
	$lang = conf('language');
	$lang_files = array(
		'en' => 'en_US',
		'ru' => 'ru_RU',
		'ua' => 'ua_UA',
	);
	return array(
		'versions' => array('master' => array('jquery' => 
			'$("form[data-fv-framework]").formValidation({
				framework: "bootstrap"
				, icon: {
					valid: "fa fa-2x fa-check-circle text-success",
					invalid: "fa fa-2x fa-times-circle text-error text-danger",
					validating: "fa fa-2x fa-refresh"
				}
				, locale: "'.$lang_files[$lang].'"
//				, err: { container: "popover" }
			})
/*
// This event will be triggered when the field passes given validator
    .on("success.validator.fv", function(e, data) {
        // data.field     --> The field name
        // data.element   --> The field element
        // data.result    --> The result returned by the validator
        // data.validator --> The validator name

        if (data.field === "userName"
            && data.validator === "remote"
            && (data.result.available === false || data.result.available === "false"))
        {
            // The userName field passes the remote validator
            data.element                    // Get the field element
                .closest(".form-group")     // Get the field parent

                // Add has-warning class
                .removeClass("has-success")
                .addClass("has-warning")

                // Show message
                .find("small[data-fv-validator=remote][data-fv-for=userName]")
                .show();
        }
    })
*/
			;')
		),
		'require' => array(
			'asset' => array(
				'bootstrap-theme',
				'jquery-formvalidation',
			),
		),
	);
};