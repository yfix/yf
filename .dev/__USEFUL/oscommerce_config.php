<?php
	// Get database connection string
	require(realpath(dirname(__FILE__))."/db_setup.php");
	// PROFY project base web path
	define("BASE_WS_PATH",				"/example_site/");
	define('DIR_FS_CATALOG',			str_replace("\\", "/", realpath("./"))."/");
	// Define the webserver and path parameters
	// * DIR_FS_* = Filesystem directories (local/physical)
	// * DIR_WS_* = Webserver directories (virtual/URL)
	define('HTTP_SERVER',				"http://".getenv("HTTP_HOST")); // eg, http://localhost - should not be empty for productive servers
	define('HTTPS_SERVER',				''); // eg, https://localhost - should not be empty for productive servers
	define('ENABLE_SSL',				false); // secure webserver for checkout procedure?
	define('HTTP_COOKIE_DOMAIN',		getenv("HTTP_HOST"));
	define('HTTPS_COOKIE_DOMAIN',		'');
	define('HTTP_COOKIE_PATH',			BASE_WS_PATH.'oscommerce/');
	define('HTTPS_COOKIE_PATH',			'');
	define('DIR_WS_HTTP_CATALOG',		BASE_WS_PATH.'oscommerce/');
	define('DIR_WS_HTTPS_CATALOG',		'');
	define('DIR_WS_IMAGES',				'images/');
	define('DIR_WS_ICONS',				DIR_WS_IMAGES . 'icons/');
	define('DIR_WS_INCLUDES',			'includes/');
	define('DIR_WS_BOXES',				DIR_WS_INCLUDES . 'boxes/');
	define('DIR_WS_FUNCTIONS',			DIR_WS_INCLUDES . 'functions/');
	define('DIR_WS_CLASSES',			DIR_WS_INCLUDES . 'classes/');
	define('DIR_WS_MODULES',			DIR_WS_INCLUDES . 'modules/');
	define('DIR_WS_LANGUAGES',			DIR_WS_INCLUDES . 'languages/');
	define('DIR_WS_DOWNLOAD_PUBLIC',	'pub/');
	define('DIR_FS_DOWNLOAD',			DIR_FS_CATALOG . 'download/');
	define('DIR_FS_DOWNLOAD_PUBLIC',	DIR_FS_CATALOG . 'pub/');
	// define our database connection
	define('DB_SERVER',					DB_HOST); // eg, localhost - should not be empty for productive servers
	define('DB_SERVER_USERNAME',		DB_USER);
	define('DB_SERVER_PASSWORD',		DB_PSWD);
	define('DB_DATABASE',				DB_NAME);
	// Other options
	define('USE_PCONNECT',				'false'); // use persistent connections?
	define('STORE_SESSIONS',			'mysql'); // leave empty '' for default handler or set to 'mysql'
	define('SESSION_WRITE_DIRECTORY',	DIR_FS_CATALOG."session_data/");
	define('USE_DEFAULT_LANGUAGE_CURRENCY',	'true');
	// Admin specific options
	define('HTTP_CATALOG_SERVER',		HTTP_SERVER);
	define('HTTPS_CATALOG_SERVER',		'');
	define('ENABLE_SSL_CATALOG',			'false'); // secure webserver for catalog module
	define('DIR_FS_DOCUMENT_ROOT',		DIR_FS_CATALOG); // where the pages are located on the server
	define('DIR_WS_ADMIN',				BASE_WS_PATH.'oscommerce/admin/'); // absolute path required
	define('DIR_FS_ADMIN',				DIR_WS_HTTP_CATALOG.'admin/'); // absolute pate required
	define('DIR_WS_CATALOG',				BASE_WS_PATH.'oscommerce/'); // absolute path required
	define('DIR_WS_CATALOG_IMAGES',		DIR_WS_CATALOG . 'images/');
	define('DIR_WS_CATALOG_LANGUAGES',	DIR_WS_CATALOG . 'includes/languages/');
	define('DIR_FS_CATALOG_LANGUAGES',	DIR_FS_CATALOG . 'includes/languages/');
	define('DIR_FS_CATALOG_IMAGES',		DIR_FS_CATALOG . 'images/');
	define('DIR_FS_CATALOG_MODULES',		DIR_FS_CATALOG . 'includes/modules/');
	define('DIR_FS_BACKUP',				DIR_FS_ADMIN . 'backups/');
	//---------------
	// Database tables
	//---------------
	// Define Tables prefix
	if (!defined('DB_PREFIX')) {
		define('DB_PREFIX', "");
	}
	if (!defined('TABLES_PREFIX')) {
		define('TABLES_PREFIX', DB_PREFIX."osc_");
	}
	// define the database table names used in the project
	define('TABLE_ADDRESS_BOOK',				TABLES_PREFIX.'address_book');
	define('TABLE_ADDRESS_FORMAT',				TABLES_PREFIX.'address_format');
	define('TABLE_BANNERS',						TABLES_PREFIX.'banners');
	define('TABLE_BANNERS_HISTORY',				TABLES_PREFIX.'banners_history');
	define('TABLE_CATEGORIES',					TABLES_PREFIX.'categories');
	define('TABLE_CATEGORIES_DESCRIPTION',		TABLES_PREFIX.'categories_description');
	define('TABLE_CONFIGURATION',				TABLES_PREFIX.'configuration');
	define('TABLE_CONFIGURATION_GROUP',			TABLES_PREFIX.'configuration_group');
	define('TABLE_COUNTER',						TABLES_PREFIX.'counter');
	define('TABLE_COUNTER_HISTORY',				TABLES_PREFIX.'counter_history');
	define('TABLE_COUNTRIES',					TABLES_PREFIX.'countries');
	define('TABLE_CURRENCIES',					TABLES_PREFIX.'currencies');
	define('TABLE_CUSTOMERS',					TABLES_PREFIX.'customers');
	define('TABLE_CUSTOMERS_BASKET',			TABLES_PREFIX.'customers_basket');
	define('TABLE_CUSTOMERS_BASKET_ATTRIBUTES', TABLES_PREFIX.'customers_basket_attributes');
	define('TABLE_CUSTOMERS_INFO',				TABLES_PREFIX.'customers_info');
	define('TABLE_LANGUAGES',					TABLES_PREFIX.'languages');
	define('TABLE_MANUFACTURERS',				TABLES_PREFIX.'manufacturers');
	define('TABLE_MANUFACTURERS_INFO',			TABLES_PREFIX.'manufacturers_info');
	define('TABLE_ORDERS',						TABLES_PREFIX.'orders');
	define('TABLE_ORDERS_PRODUCTS',				TABLES_PREFIX.'orders_products');
	define('TABLE_ORDERS_PRODUCTS_ATTRIBUTES',	TABLES_PREFIX.'orders_products_attributes');
	define('TABLE_ORDERS_PRODUCTS_DOWNLOAD',	TABLES_PREFIX.'orders_products_download');
	define('TABLE_ORDERS_STATUS',				TABLES_PREFIX.'orders_status');
	define('TABLE_ORDERS_STATUS_HISTORY',		TABLES_PREFIX.'orders_status_history');
	define('TABLE_ORDERS_TOTAL',				TABLES_PREFIX.'orders_total');
	define('TABLE_PRODUCTS',					TABLES_PREFIX.'products');
	define('TABLE_PRODUCTS_ATTRIBUTES',			TABLES_PREFIX.'products_attributes');
	define('TABLE_PRODUCTS_ATTRIBUTES_DOWNLOAD',TABLES_PREFIX.'products_attributes_download');
	define('TABLE_PRODUCTS_DESCRIPTION',		TABLES_PREFIX.'products_description');
	define('TABLE_PRODUCTS_NOTIFICATIONS',		TABLES_PREFIX.'products_notifications');
	define('TABLE_PRODUCTS_OPTIONS',			TABLES_PREFIX.'products_options');
	define('TABLE_PRODUCTS_OPTIONS_VALUES',		TABLES_PREFIX.'products_options_values');
	define('TABLE_PRODUCTS_OPTIONS_VALUES_TO_PRODUCTS_OPTIONS', TABLES_PREFIX.'products_options_values_to_products_options');
	define('TABLE_PRODUCTS_TO_CATEGORIES',		TABLES_PREFIX.'products_to_categories');
	define('TABLE_REVIEWS',						TABLES_PREFIX.'reviews');
	define('TABLE_REVIEWS_DESCRIPTION',			TABLES_PREFIX.'reviews_description');
	define('TABLE_SESSIONS',					TABLES_PREFIX.'sessions');
	define('TABLE_SPECIALS',					TABLES_PREFIX.'specials');
	define('TABLE_TAX_CLASS',					TABLES_PREFIX.'tax_class');
	define('TABLE_TAX_RATES',					TABLES_PREFIX.'tax_rates');
	define('TABLE_GEO_ZONES',					TABLES_PREFIX.'geo_zones');
	define('TABLE_ZONES_TO_GEO_ZONES',			TABLES_PREFIX.'zones_to_geo_zones');
	define('TABLE_WHOS_ONLINE',					TABLES_PREFIX.'whos_online');
	define('TABLE_ZONES',						TABLES_PREFIX.'zones');
	// Admin tables
	define('TABLE_ADMIN',						TABLES_PREFIX.'admin');
	define('TABLE_ADMIN_FILES',					TABLES_PREFIX.'admin_files');
	define('TABLE_ADMIN_GROUPS',				TABLES_PREFIX.'admin_groups');
?>