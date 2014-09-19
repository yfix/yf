<?php

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

#$sql = 'CREATE TABLE `film` (`id` int unsigned auto_increment, `t` timestamp, primary key(`id`))';

$parser = new \PHPSQLParser\PHPSQLParser($sql);
$result = $parser->parsed;

$table_name = $result['TABLE']['no_quotes']['parts'][0];
$tmp_create_def = $result['TABLE']['create-def']['sub_tree'];
$tmp_options = $result['TABLE']['options'];

#var_export($result['TABLE']);
#var_export(array_keys($result['CREATE']));
#var_export($tmp_options);

$struct = array(
	'name'	=> $table_name,
	'fields' => array(),
	'indexes' => array(),
	'foreign_keys' => array(),
	'options' => array(),
);
foreach ($tmp_create_def as $v) {
#	echo $v['expr_type']. PHP_EOL;
#	print_r($v);

	if ($v['expr_type'] == 'column-def') {
		$col_name = '';
		$col_type = '';
		$nullable = false;
		foreach ($v['sub_tree'] as $v2) {
			if ($v2['expr_type'] == 'colref') {
				$col_name = $v2['no_quotes']['parts'][0];
			} elseif ($v2['expr_type'] == 'column-type') {
				foreach ($v2['sub_tree'] as $v3) {
					if ($v3['expr_type'] == 'data-type') {
						$col_type = $v3['base_expr'];
					}
				}
			}
			print_r($v2);
		}
		$struct['fields'][$col_name] = array(
			'name'		=> $col_name,
			'type'		=> $col_type,
			'nullable'	=> $nullable,
		);
	} elseif ($v['expr_type'] == 'primary-key') {
	} elseif ($v['expr_type'] == 'index') {
	} elseif ($v['expr_type'] == 'foreign-key') {
	}
}
foreach ($tmp_options as $v) {
	$name = array();
	$val = '';
	foreach ($v['sub_tree'] as $v2) {
		if ($v2['expr_type'] == 'reserved') {
			$name[] = $v2['base_expr'];
		} elseif ($v2['expr_type'] == 'const') {
			$val = $v2['base_expr'];
		}
	}
	$name = strtolower(implode(' ', $name));
	if (in_array($name, array('default charset', 'default character set', 'charset', 'character set'))) {
		$name = 'charset';
	} elseif (in_array($name, array('engine'))) {
		$name = 'engine';
	}
	$struct['options'][$name] = $val;
}

print_r($struct);
