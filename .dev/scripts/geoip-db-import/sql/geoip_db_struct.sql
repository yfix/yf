CREATE TABLE IF NOT EXISTS geoip_countries (
	start_ip		int unsigned NOT NULL,
	end_ip			int unsigned NOT NULL,
	mask			int unsigned NOT NULL,
	country			char(2) NOT NULL,
) CHARACTER SET utf8;

CREATE TABLE IF NOT EXISTS geoip_city_blocks (
	start_ip		int unsigned NOT NULL,
	end_ip			int unsigned NOT NULL,
	loc_id			int unsigned NOT NULL,
	country			char(2) NOT NULL,
	region			char(2) NOT NULL,
) CHARACTER SET utf8;

CREATE TABLE IF NOT EXISTS geoip_city_locations (
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
	KEY country (country)
) CHARACTER SET utf8;
