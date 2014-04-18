CREATE TABLE IF NOT EXISTS geoip_city_blocks (
    id              INT,
    name            VARCHAR(200)    COMMENT 'name of geographical point (utf8)',
    name_ascii      VARCHAR(200)    COMMENT 'name of geographical point in plain ascii characters',
    alternate_names VARCHAR(4000)   COMMENT 'alternate names, comma separated',
    latitude        DECIMAL(10, 7)  COMMENT 'latitude in decimal degrees (wgs84)',
    longitude       DECIMAL(10, 7)  COMMENT 'longitude in decimal degrees (wgs84)',
    feature_class   CHAR(1)         COMMENT 'see http://www.geonames.org/export/codes.html',
    feature_code    VARCHAR(10)     COMMENT 'see http://www.geonames.org/export/codes.html',
    country         VARCHAR(2)      COMMENT 'ISO-3166 2-letter country code',
    cc2             VARCHAR(60)     COMMENT 'alternate country codes, comma separated, ISO-3166 2-letter country code',
    admin1          VARCHAR(20)     COMMENT 'fipscode (subject to change to iso code) see geo_admin1 table. ISO codes are used for US, CH, BE and ME. UK and Greece are using an additional level between country and fips code.',
    admin2          VARCHAR(80)     COMMENT 'code for the second administrative division, a county in the US, see geo_admin2 table',
    admin3          VARCHAR(20)     COMMENT 'code for third level administrative division',
    admin4          VARCHAR(20)     COMMENT 'code for fourth level administrative division',
    population      INT,
    elevation       INT             COMMENT 'in meters',
    gtopo30         INT             COMMENT 'digital elevation model, srtm3 or gtopo30, average elevation of 3''x3'' (ca 90mx90m) or 30''x30'' (ca 900mx900m) area in meters',
    timezone        VARCHAR(100)    COMMENT 'the timezone id, see geo_timezone table',
    mod_date        DATE            COMMENT 'date of last modification in yyyy-MM-dd format',
    PRIMARY KEY (id)
) CHARACTER SET utf8;

	start_ip int(8) unsigned NOT NULL,
	end_ip int(8) unsigned NOT NULL,
	loc_id int(6) unsigned NOT NULL,
	PRIMARY KEY	(`end_ip`)


	loc_id int(10) unsigned NOT NULL,
	country char(2) NOT NULL,
	region char(3) NOT NULL,
	city varchar(32) NOT NULL,
	postal_code char(5) NOT NULL,
	latitude float NOT NULL,
	longitude float NOT NULL,
	dma_code int(8) unsigned NOT NULL,
	area_code int(8) unsigned NOT NULL,
	PRIMARY KEY	(loc_id),
	KEY country (country)

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

==> GeoLite2-Country-Blocks.csv <==
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

==> GeoLite2-Country-Locations.csv <==
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
