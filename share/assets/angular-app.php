<?php

return MAIN_TYPE_ADMIN ? array(
	'versions' => array(
		'master' => array(
			'js' => '
	var ng_app_admin = angular.module( "api.admin", [
		"ngAnimate"
		, "ngRoute"
		, "ngSanitize"
		, "ngResource"
		, "ngTouch"
		, "ui.select2"
		, "ui.select"
		, "mgcrea.ngStrap"
	]);
	ng_app_admin.config( function( $datepickerProvider ) {
		angular.extend( $datepickerProvider.defaults, {
			dateFormat: "dd.MM.yyyy"
			, autoclose: 1
			, startWeek: 1
			, startView: 2
		});
	});
	ng_app_admin.config( function( uiSelectConfig ) {
		uiSelectConfig.theme = "select2";
	});
		'),
	),
	'require' => array(
		'asset' => array(
			'angular-full',
			'font-awesome4',
		),
	),
) : null;
