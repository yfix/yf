/* Example: CALL CREATE_INDEX_IF_NOT_EXISTS('tablename','indexname','thisfield,thatfield'); */
DELIMITER $$
DROP PROCEDURE IF EXISTS `create_index_if_not_exists`$$
CREATE DEFINER=`user`@`%` PROCEDURE `create_index_if_not_exists` (table_name_vc varchar(50), index_name_vc varchar(50), field_list_vc varchar(200))
SQL SECURITY INVOKER
BEGIN
	SET @Index_cnt = (SELECT COUNT(1) cnt FROM INFORMATION_SCHEMA.STATISTICS WHERE table_name = table_name_vc AND index_name = index_name_vc);
	IF IFNULL(@Index_cnt,0) = 0 THEN 
		SET @index_sql = CONCAT('ALTER TABLE ',table_name_vc,' ADD INDEX ',index_name_vc,'(',field_list_vc,');');
		PREPARE stmt FROM @index_sql;
		EXECUTE stmt;
		DEALLOCATE PREPARE stmt;
	END IF;
END$$
DELIMITER ;

CALL CREATE_INDEX_IF_NOT_EXISTS('geo_geoname', 'feature_class', 'feature_class');
CALL CREATE_INDEX_IF_NOT_EXISTS('geo_geoname', 'feature_code', 'feature_code');
CALL CREATE_INDEX_IF_NOT_EXISTS('geo_geoname', 'country', 'country');
CALL CREATE_INDEX_IF_NOT_EXISTS('geo_geoname', 'admin1', 'admin1');
CALL CREATE_INDEX_IF_NOT_EXISTS('geo_geoname', 'admin2', 'admin2');
CALL CREATE_INDEX_IF_NOT_EXISTS('geo_geoname', 'population', 'population');

CALL CREATE_INDEX_IF_NOT_EXISTS('geo_alternate_name', 'geoname_id', 'geoname_id');
CALL CREATE_INDEX_IF_NOT_EXISTS('geo_alternate_name', 'language_code', 'language_code');
CALL CREATE_INDEX_IF_NOT_EXISTS('geo_alternate_name', 'is_preferred', 'is_preferred');
CALL CREATE_INDEX_IF_NOT_EXISTS('geo_alternate_name', 'is_historic', 'is_historic');
CALL CREATE_INDEX_IF_NOT_EXISTS('geo_alternate_name', 'is_short', 'is_short');
CALL CREATE_INDEX_IF_NOT_EXISTS('geo_alternate_name', 'is_colloquial', 'is_colloquial');

CALL CREATE_INDEX_IF_NOT_EXISTS('geo_admin1', 'geoname_id', 'geoname_id');

CALL CREATE_INDEX_IF_NOT_EXISTS('geo_admin2', 'geoname_id', 'geoname_id');

CALL CREATE_INDEX_IF_NOT_EXISTS('geo_country', 'geoname_id', 'geoname_id');

CALL CREATE_INDEX_IF_NOT_EXISTS('geo_hierarchy', 'feature_code', 'feature_code');
CALL CREATE_INDEX_IF_NOT_EXISTS('geo_hierarchy', 'parent_id', 'parent_id');
CALL CREATE_INDEX_IF_NOT_EXISTS('geo_hierarchy', 'child_id', 'child_id');

CALL CREATE_INDEX_IF_NOT_EXISTS('geo_language', 'code', 'code');

CALL CREATE_INDEX_IF_NOT_EXISTS('geo_postal_code', 'country_code', 'country_code');

CALL CREATE_INDEX_IF_NOT_EXISTS('geo_timezone', 'country_code', 'country_code');
