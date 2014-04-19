SET autocommit=0;
SET unique_checks=0;
SET foreign_key_checks=0;
/*------------------------*/
CREATE TABLE IF NOT EXISTS geoip2_city_blocks (
	start_ip6			char(39) NOT NULL,
	mask_len6			int unsigned NOT NULL,
	start_ip4			int unsigned NOT NULL,
	geoname_id			int unsigned NOT NULL,
	registered_cgid		int unsigned NOT NULL,
	represented_cgid	int unsigned NOT NULL,
	postal_code 		char(5) NOT NULL,
	lat 				decimal(6,4) NOT NULL,
	lon 				decimal(6,4) NOT NULL,
	is_anonymous_proxy	tinyint(1) unsigned NOT NULL,
	is_satellite_provider	tinyint(1) unsigned NOT NULL,
	PRIMARY KEY (start_ip6, mask_len6)
) CHARACTER SET utf8;
/*
==> GeoLite2-City-Blocks.csv <==
network_start_ip,
network_mask_length,
geoname_id,
registered_country_geoname_id,
represented_country_geoname_id,
postal_code,
latitude,
longitude,
is_anonymous_proxy,
is_satellite_provider
*/
LOAD DATA LOCAL INFILE './data/GeoLite2-City-Blocks.csv'
INTO TABLE geoip2_city_blocks
CHARACTER SET 'UTF8'
FIELDS TERMINATED BY ',' ENCLOSED BY '"'
(start_ip6, mask_len6, geoname_id, registered_cgid, represented_cgid, postal_code, lat, lon, is_anonymous_proxy, is_satellite_provider);
/*---------------------------*/
CREATE TABLE IF NOT EXISTS geoip2_city_locations (
	geoname_id			int unsigned NOT NULL,
	continent 			char(2) NOT NULL,
	country 			char(2) NOT NULL,
	region 				char(6) NOT NULL,
	region_name			varchar(64) NOT NULL,
	city_name 			varchar(64) NOT NULL,
	metro_code			int unsigned NOT NULL,
	time_zone			int unsigned NOT NULL,
	PRIMARY KEY (geoname_id)
) CHARACTER SET utf8;
/*
==> GeoLite2-City-Locations.csv <==
geoname_id,
continent_code,
continent_name,
country_iso_code,
country_name,
subdivision_iso_code,
subdivision_name,
city_name,
metro_code,
time_zone
*/
LOAD DATA LOCAL INFILE './data/GeoLite2-City-Locations.csv'
INTO TABLE geoip2_city_locations
CHARACTER SET 'UTF8'
FIELDS TERMINATED BY ',' ENCLOSED BY '"'
(geoname_id, continent, @skip, country, @skip, region, region_name, city_name, metro_code, time_zone);
/*---------------------------*/
UPDATE `geoip2_city_blocks`
SET start_ip4 = SUBSTRING(start_ip6 FROM 8)
WHERE start_ip4 = '' AND start_ip6 LIKE '::FFFF:%'
/*---------------------------*/
COMMIT;
SET unique_checks=1;
SET foreign_key_checks=1;
