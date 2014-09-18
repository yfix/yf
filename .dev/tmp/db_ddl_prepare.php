<?php

namespace PHPSQLParser;


$libs_root = dirname(dirname(__DIR__)).'/libs';
require_once $libs_root.'/symfony_class_loader/UniversalClassLoader.php';
$loader = new \Symfony\Component\ClassLoader\UniversalClassLoader();
$loader->registerNamespaces(array(
	'PHPSQLParser' => $libs_root.'/php_sql_parser/src',
));
$loader->register();


$sql = <<<'EOD'
CREATE TABLE `film` (
  `film_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL,
  `description` text,
  `release_year` year(4) DEFAULT NULL,
  `language_id` tinyint(3) unsigned NOT NULL,
  `original_language_id` tinyint(3) unsigned DEFAULT NULL,
  `rental_duration` tinyint(3) unsigned NOT NULL DEFAULT '3',
  `rental_rate` decimal(4,2) NOT NULL DEFAULT '4.99',
  `length` smallint(5) unsigned DEFAULT NULL,
  `replacement_cost` decimal(5,2) NOT NULL DEFAULT '19.99',
  `rating` enum('G','PG','PG-13','R','NC-17') DEFAULT 'G',
  `special_features` set('Trailers','Commentaries','Deleted Scenes','Behind the Scenes') DEFAULT NULL,
  `last_update` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`film_id`),
  KEY `idx_title` (`title`),
  KEY `idx_fk_language_id` (`language_id`),
  KEY `idx_fk_original_language_id` (`original_language_id`),
  CONSTRAINT `fk_film_language` FOREIGN KEY (`language_id`) REFERENCES `language` (`language_id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_film_language_original` FOREIGN KEY (`original_language_id`) REFERENCES `language` (`language_id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
EOD;

$sql = 'CREATE TABLE `film` (`id` int unsigned auto_increment, `t` timestamp, primary key(`id`))';

$parser = new PHPSQLParser($sql);
$result = $parser->parsed;

$table_name = $result['TABLE']['no_quotes']['parts'][0];
$tmp_create_def = $result['TABLE']['create-def']['sub_tree'];
$tmp_options = $result['TABLE']['options']['sub_tree'];

#var_export($tmp_create_def);
$struct = array(
	'name'	=> $table_name,
	'fields' => array(),
	'indexes' => array(),
	'foreign_keys' => array(),
	'options' => array(),
);
foreach ($tmp_create_def as $k => $v) {
#	if () {
#	} elseif () {
#	}
#	print_r($v);
}
#$struct = array(
#);
print_r($struct);
