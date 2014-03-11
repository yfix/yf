<?php
$data = '
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT \'\',
  `url` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT \'\',
  `image` tinyint(1) NOT NULL DEFAULT \'0\',
  `description` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `features` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `meta_keywords` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `meta_desc` text CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `external_url` varchar(255) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL DEFAULT \'\',
  `cat_id` int(11) NOT NULL,
  `model` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `sku` varchar(64) CHARACTER SET utf8 COLLATE utf8_unicode_ci NOT NULL,
  `quantity` int(10) NOT NULL DEFAULT \'100\',
  `stock_status_id` int(10) NOT NULL DEFAULT \'0\',
  `manufacturer_id` int(10) NOT NULL DEFAULT \'0\',
  `supplier_id` int(10) NOT NULL DEFAULT \'0\',
  `price` decimal(8,2) NOT NULL DEFAULT \'0.00\',
  `price_promo` decimal(8,2) NOT NULL DEFAULT \'0.00\',
  `price_partner` decimal(8,2) NOT NULL DEFAULT \'0.00\',
  `price_raw` decimal(8,2) NOT NULL DEFAULT \'0.00\',
  `old_price` decimal(8,2) NOT NULL DEFAULT \'0.00\',
  `currency` tinyint(3) unsigned NOT NULL DEFAULT \'0\',
  `add_date` int(10) unsigned NOT NULL DEFAULT \'0\',
  `update_date` int(10) unsigned NOT NULL DEFAULT \'0\',
  `last_viewed_date` int(10) NOT NULL DEFAULT \'0\',
  `featured` tinyint(1) NOT NULL DEFAULT \'0\',
  `active` tinyint(1) NOT NULL DEFAULT \'0\',
  `viewed` int(10) NOT NULL DEFAULT \'0\',
  `sold` int(10) NOT NULL DEFAULT \'0\',
  `status` int(10) NOT NULL DEFAULT \'0\',
  `articul` varchar(32) NOT NULL DEFAULT \'\',
  `origin_url` varchar(255) NOT NULL,
  `source` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `cat_id` (`cat_id`),
  KEY `active` (`active`),
  KEY `viewed` (`viewed`),
  KEY `sold` (`sold`),
  KEY `active_cat_id` (`active`,`cat_id`),
  KEY `add_date` (`add_date`),
  KEY `update_date` (`update_date`)
';