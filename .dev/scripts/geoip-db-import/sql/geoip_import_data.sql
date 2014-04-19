SET autocommit=0;
SET unique_checks=0;
SET foreign_key_checks=0;
/*------------------------*/
CREATE TABLE IF NOT EXISTS geoip_asn (
	start_ip		int unsigned NOT NULL,
	end_ip			int unsigned NOT NULL,
	name			varchar(256) NOT NULL,
	PRIMARY KEY (start_ip, end_ip)
) CHARACTER SET utf8;
/*
==> ./GeoIPASNum2.csv <==
16777216,16777471,"AS15169 Google Inc."
16778240,16779263,"AS56203 Big Red Group"
*/
LOAD DATA LOCAL INFILE './data/GeoIPASNum2.csv'
INTO TABLE geoip_asn
CHARACTER SET 'UTF8'
FIELDS TERMINATED BY ',' ENCLOSED BY '"'
(start_ip, end_ip, name);
/*------------------------*/
CREATE TABLE IF NOT EXISTS geoip_city_location (
	id int(10) unsigned NOT NULL,
	country char(2) NOT NULL,
	region char(3) NOT NULL,
	city varchar(32) NOT NULL,
	postal_code char(5) NOT NULL,
	lat float NOT NULL,
	lon float NOT NULL,
	dma_code int(8) unsigned NOT NULL,
	area_code int(8) unsigned NOT NULL,
	PRIMARY KEY	(id),
	KEY (lon, lat),
	KEY country (country)
) CHARACTER SET utf8;
/*
==> ./GeoLiteCity-Location.csv <==
Copyright (c) 2012 MaxMind LLC.  All Rights Reserved.
locId,country,region,city,postalCode,latitude,longitude,metroCode,areaCode
*/
LOAD DATA LOCAL INFILE './data/GeoLiteCity-Location.csv'
INTO TABLE geoip_city_location
CHARACTER SET 'UTF8'
FIELDS TERMINATED BY ',' ENCLOSED BY '"'
IGNORE 2 LINES
(id, country, region, city, postal_code, lat, lon, dma_code, area_code);
/*-------------------------*/
CREATE TABLE IF NOT EXISTS geoip_countries (
	start_ip		int unsigned NOT NULL,
	end_ip			int unsigned NOT NULL,
	country			char(2) NOT NULL,
	PRIMARY KEY (start_ip, end_ip)
) CHARACTER SET utf8;
/*
==> ./GeoIPCountryWhois.csv <==
"1.0.0.0","1.0.0.255","16777216","16777471","AU","Australia"
"1.0.1.0","1.0.3.255","16777472","16778239","CN","China"
*/
LOAD DATA LOCAL INFILE './data/GeoIPCountryWhois.csv'
INTO TABLE geoip_countries
CHARACTER SET 'UTF8'
FIELDS TERMINATED BY ',' ENCLOSED BY '"'
(@skip, @skip, start_ip, end_ip, country);
/*---------------------------*/
CREATE TABLE IF NOT EXISTS geoip_city_blocks (
	start_ip		int unsigned NOT NULL,
	end_ip			int unsigned NOT NULL,
	loc_id			int unsigned NOT NULL,
	country			char(2) NOT NULL,
	region			char(2) NOT NULL,
	PRIMARY KEY (start_ip, end_ip)
) CHARACTER SET utf8;
/*
==> ./GeoLiteCity-Blocks.csv <==
Copyright (c) 2011 MaxMind Inc.  All Rights Reserved.
startIpNum,endIpNum,locId
*/
LOAD DATA LOCAL INFILE './data/GeoLiteCity-Blocks.csv'
INTO TABLE geoip_city_blocks
CHARACTER SET 'UTF8'
FIELDS TERMINATED BY ',' ENCLOSED BY '"'
IGNORE 2 LINES
(start_ip, end_ip, loc_id);
/*---------------------------*/
CREATE TABLE IF NOT EXISTS geoip_region_names (
	country 		char(2) NOT NULL,
	region_code 	varchar(16) NOT NULL,
	region_name		varchar(64) NOT NULL,
	PRIMARY KEY (country, region_code)
) CHARACTER SET utf8;
/*
==> ./region_codes.csv <==
AD,02,"Canillo"
AD,03,"Encamp"
*/
LOAD DATA LOCAL INFILE './data/region_codes.csv'
INTO TABLE geoip_region_names
CHARACTER SET 'UTF8'
FIELDS TERMINATED BY ',' ENCLOSED BY '"'
(country, region_code, region_name);
/*---------------------------*/
CREATE TABLE IF NOT EXISTS geoip_cities_dma_regions (
	criteria_id		int unsigned NOT NULL,
	dma_code 		int(8) unsigned NOT NULL,
	city_name		varchar(64) NOT NULL,
	dma_region		varchar(64) NOT NULL,
	PRIMARY KEY (criteria_id)
) CHARACTER SET utf8;
/*
==> ./cities-DMAregions.csv <==
City Name,Criteria ID,DMA Region Name,DMA Region Code
Acton,1018752,"Portland-Auburn, ME",500
*/
LOAD DATA LOCAL INFILE './data/cities-DMAregions.csv'
INTO TABLE geoip_cities_dma_regions
CHARACTER SET 'UTF8'
FIELDS TERMINATED BY ',' ENCLOSED BY '"'
IGNORE 1 LINES
(city_name, criteria_id, dma_region, dma_code);
/*---------------------------*/
UPDATE geoip_city_blocks AS b
INNER JOIN geoip_city_location AS l ON b.loc_id = l.id
SET b.country = l.country, b.region = l.region;
/*---------------------------*/
COMMIT;
SET unique_checks=1;
SET foreign_key_checks=1;