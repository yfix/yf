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
  UNIQUE KEY `idx_title` (`title`),
  FULLTEXT KEY `idx_desc` (`description`),
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

$struct = array(
	'name'	=> $table_name,
	'fields' => array(),
	'indexes' => array(),
	'foreign_keys' => array(),
	'options' => array(),
);

foreach ($tmp_create_def as $v) {
	if ($v['expr_type'] == 'column-def') {
// http://dev.mysql.com/doc/refman/5.6/en/timestamp-initialization.html
// As of MySQL 5.6.5, TIMESTAMP and DATETIME columns can be automatically initializated and updated to the current date and time (that is, the current timestamp). 
// Before 5.6.5, this is true only for TIMESTAMP, and for at most one TIMESTAMP column per table
		$name = null;
		$type = null;
		$length = null;
		$unsigned = null;
		$nullable = false;
		$default = null;
		$auto_inc = null;
		$primary = false;
		$unique = false;
		$decimals = null;
		$values = null; // ENUM and SET
		foreach ($v['sub_tree'] as $v2) {
			if ($v2['expr_type'] == 'colref') {
				$name = $v2['no_quotes']['parts'][0];
			} elseif ($v2['expr_type'] == 'column-type') {
				foreach ($v2['sub_tree'] as $v3) {
					if (isset($v3['unsigned'])) {
						$unsigned = $v3['unsigned'];
					}
					if ($v3['expr_type'] == 'data-type') {
						$type = $v3['base_expr'];
						$length = $v3['length'];
						$decimals = $v3['decimals'];
					} elseif ($v3['expr_type'] == 'default-value') {
						$default = trim($v3['base_expr'], '"\'');
					} elseif ($v3['expr_type'] == 'reserved' && in_array($v3['base_expr'], array('enum', 'set'))) {
						$type = $v3['base_expr'];
						if ($v3['sub_tree']['expr_type'] != 'bracket_expression') {
							continue;
						}
						$values = array();
						foreach ($v3['sub_tree']['sub_tree'] as $v4) {
							if ($v4['expr_type'] == 'const') {
								$_val = trim($v4['base_expr'], '"\'');
								$values[$_val] = $_val;
							}
						}
					}
				}
				$nullable = $v2['nullable'];
				$auto_inc = $v2['auto_inc'];
				$primary = $v2['primary'];
				$unique = $v2['unique'];
			}
		}
		if ($auto_inc) {
			$primary = true;
		}
#		if ($type == 'year' && !$length) {
#			$length = 4;
#		}
		$struct['fields'][$name] = array(
			'name'		=> $name,
			'type'		=> $type,
			'length'	=> $length,
			'decimals'	=> $decimals,
			'unsigned'	=> $unsigned,
			'nullable'	=> $nullable,
			'default'	=> $default,
			'auto_inc'	=> $auto_inc,
			'primary'	=> $primary,
			'unique'	=> $unique,
			'values'	=> !empty($values) ? $values : null,
			'raw'		=> $v['base_expr'],
		);
	} elseif ($v['expr_type'] == 'primary-key') {
		$name = 'PRIMARY';
		$type = 'primary';
		$columns = array();
		foreach ($v['sub_tree'] as $v2) {
			if ($v2['expr_type'] == 'column-list') {
				foreach ($v2['sub_tree'] as $v3) {
					if ($v3['expr_type'] == 'index-column') {
						$index_col_name = $v3['no_quotes']['parts'][0];
						$columns[$index_col_name] = $index_col_name;
					}
				}
			}
		}
		$struct['indexes'][$name] = array(
			'name'		=> $name,
			'type'		=> $type,
			'columns'	=> $columns,
			'raw'		=> $v['base_expr'],
		);
	} elseif ($v['expr_type'] == 'index') {
		$name = null;
		$type = 'index';
		$base = strtoupper(trim($v['base_expr']));
		if (substr($base, 0, strlen('UNIQUE')) == 'UNIQUE') {
			$type = 'unique';
		} elseif (substr($base, 0, strlen('FULLTEXT')) == 'FULLTEXT') {
			$type = 'fulltext';
		} elseif (substr($base, 0, strlen('SPATIAL')) == 'SPATIAL') {
			$type = 'spatial';
		}
		$columns = array();
		foreach ($v['sub_tree'] as $v2) {
			if ($v2['expr_type'] == 'const') {
				$name = trim($v2['base_expr'], '"\'`');
			} elseif ($v2['expr_type'] == 'column-list') {
				foreach ($v2['sub_tree'] as $v3) {
					if ($v3['expr_type'] == 'index-column') {
						$index_col_name = $v3['no_quotes']['parts'][0];
						$columns[$index_col_name] = $index_col_name;
					}
				}
			}
		}
		if (!$name) {
			$name = 'idx_'.(count($struct['indexes']) + 1);
		}
		$struct['indexes'][$name] = array(
			'name'		=> $name,
			'type'		=> $type,
			'columns'	=> $columns,
			'raw'		=> $v['base_expr'],
		);
#print_r($v);
	} elseif ($v['expr_type'] == 'foreign-key') {
		$name = null;
		$columns = array();
		$ref_table = null;
		$ref_columns = array();
		$on_update = null;
		$on_delete = null;
		foreach ($v['sub_tree'] as $v2) {
			if ($v2['expr_type'] == 'constraint') {
				$name = trim($v2['sub_tree']['base_expr'], '"\'`');
			} elseif ($v2['expr_type'] == 'column-list') {
				foreach ($v2['sub_tree'] as $v3) {
					if ($v3['expr_type'] == 'index-column') {
						$index_col_name = $v3['no_quotes']['parts'][0];
						$columns[$index_col_name] = $index_col_name;
					}
				}
			} elseif ($v2['expr_type'] == 'foreign-ref') {
				foreach ($v2['sub_tree'] as $v3) {
					if ($v3['expr_type'] == 'table') {
						$ref_table = $v3['no_quotes']['parts'][0];
					} elseif ($v3['expr_type'] == 'column-list') {
						foreach ($v3['sub_tree'] as $v4) {
							if ($v4['expr_type'] == 'index-column') {
								$ref_col_name = $v4['no_quotes']['parts'][0];
								$ref_columns[$ref_col_name] = $ref_col_name;
							}
						}
					}
				}
				$on_update = $v2['on_update'];
				$on_delete = $v2['on_delete'];
			}
		}
		if (!$name) {
			$name = 'fk_'.(count($struct['foreign_keys']) + 1);
		}
		$struct['foreign_keys'][$name] = array(
			'name'			=> $name,
			'columns'		=> $columns,
			'ref_table'		=> $ref_table,
			'ref_columns'	=> $ref_columns,
			'on_update'		=> $on_update,
			'on_delete'		=> $on_delete,
			'raw'			=> $v['base_expr'],
		);
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
