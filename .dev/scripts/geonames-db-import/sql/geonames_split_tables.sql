
DROP TABLE IF EXISTS geo_city;
CREATE TABLE IF NOT EXISTS geo_city (
    geoname_id   int PRIMARY KEY,
    name        varchar(200),
    name_local  varchar(200),
    latitude    decimal(10,7),
    longitude   decimal(10,7),
    country     varchar(2),
    admin1      varchar(20),
    admin2      varchar(80),
    admin3      varchar(20),
    admin4      varchar(20),
    population  int,
    timezone    varchar(40)
) CHARACTER SET utf8;

TRUNCATE TABLE geo_city;

#CREATE OR REPLACE VIEW `geo_city_view_ua_uk` AS
INSERT INTO geo_city
SELECT
    g.id,
    g.name,
    a.name AS name_local,
    g.latitude,
    g.longitude,
    g.country,
    g.admin1,
    g.admin2,
    g.admin3,
    g.admin4,
    g.population,
    g.timezone
FROM
    geo_geoname AS g
LEFT JOIN geo_alternate_name AS a ON g.id = a.geoname_id
WHERE
    g.feature_class =  'P'
	AND g.country IN('UA')
	AND g.population > 1000
    AND g.feature_code IN('PPL','PPLA','PPLC')
	AND a.language_code = 'UK'
GROUP BY g.id
#ORDER BY a.name COLLATE utf8_unicode_ci ASC
;



# Get administration 1 LEVEL FROM Ukraine
# SELECT * FROM `geo_geoname` WHERE `country` = 'UA' AND `feature_class` = 'A' AND `feature_code` IN ('ADM1')
/*
SELECT a.name, g.*
FROM geo_geoname AS g
LEFT JOIN geo_hierarchy AS h ON g.id = h.child_id
LEFT JOIN geo_alternate_name AS a ON g.id = a.geoname_id
WHERE h.parent_id = (SELECT id FROM geo_geoname WHERE feature_code = 'PCLI' AND country = 'UA')
  AND h.feature_code = 'ADM'
  AND a.language_code = 'UK'
GROUP BY g.id
ORDER BY a.name ASC COLLATE utf8_unicode_ci ASC
*/

# Get top cities by population FROM Ukraine
/*
SELECT a.name, g.*
FROM (SELECT * FROM geo_geoname WHERE country = 'UA' AND feature_class = 'P') AS g
LEFT JOIN geo_alternate_name AS a ON g.id = a.geoname_id
WHERE a.language_code = 'UK'
GROUP BY g.id
ORDER BY g.population DESC
LIMIT 100
*/
/*
SELECT a.name, g.*
FROM (SELECT * FROM geo_geoname WHERE country = 'UA' AND feature_class = 'P' AND population > 1000 ORDER BY population DESC LIMIT 100) AS g
LEFT JOIN geo_alternate_name AS a ON g.id = a.geoname_id
WHERE a.language_code = 'UK'
GROUP BY g.id
ORDER BY population DESC
LIMIT 100
*/

# Get administration 1 LEVEL FROM Russia
/*
SELECT a.name, g.*
FROM geo_geoname AS g
LEFT JOIN geo_hierarchy AS h ON g.id = h.child_id
LEFT JOIN geo_alternate_name AS a ON g.id = a.geoname_id
WHERE h.parent_id = (SELECT id FROM geo_geoname WHERE feature_code = 'PCLI' AND country = 'RU')
  AND h.feature_code = 'ADM'
  AND a.language_code = 'RU'
GROUP BY g.id
ORDER BY a.name ASC
*/

# Get administration 1 LEVEL FROM Spain
/*
SELECT a.name, g.*
FROM geo_geoname AS g
LEFT JOIN geo_hierarchy AS h ON g.id = h.child_id
LEFT JOIN geo_alternate_name AS a ON g.id = a.geoname_id
WHERE h.parent_id = 2510769
  AND h.feature_code = 'ADM'
  AND a.language_code = 'es'
  AND a.is_short = 1
GROUP BY g.id
ORDER BY a.name ASC
*/
/*
SELECT a.name, g.*
FROM geo_geoname AS g
LEFT JOIN geo_hierarchy AS h ON g.id = h.child_id
LEFT JOIN geo_alternate_name AS a ON g.id = a.geoname_id
WHERE h.parent_id = (SELECT id FROM geo_geoname WHERE feature_code = 'PCLI' AND country = 'ES')
  AND h.feature_code = 'ADM'
  AND a.language_code = 'ES'
  AND a.is_short = 1
GROUP BY g.id
ORDER BY a.name ASC
*/
