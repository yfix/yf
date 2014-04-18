SELECT g.id, g.name, a.name AS native, g.feature_code, g.population
FROM geo_geoname AS g
LEFT JOIN geo_alternate_name AS a ON a.geoname_id = g.id
WHERE 
	g.feature_class = 'p'
	AND g.population > 1000
	AND g.country = 'ua' 
	AND a.language_code = 'uk'
GROUP BY g.id
ORDER BY g.population DESC
LIMIT 500;


SELECT * FROM (
	SELECT g.id, g.name, a.name AS native, g.feature_code, g.population
	FROM geo_geoname AS g
	INNER JOIN geo_alternate_name AS a ON a.geoname_id = g.id
	WHERE g.feature_class = 'a'
		AND g.country = 'ua'
	    AND a.language_code = 'uk'
	ORDER BY g.id, a.is_preferred DESC
) AS tmp
GROUP BY id
ORDER BY population DESC
LIMIT 500;


SELECT * FROM (
	SELECT c.code, a.name
	FROM `geo_country` AS c
	INNER JOIN geo_alternate_name AS a ON a.geoname_id = c.geoname_id
	WHERE a.language_code = 'uk'
	ORDER BY is_preferred DESC
) AS tmp
GROUP BY code
ORDER BY name collate utf8_unicode_ci ASC

-- Select ucfirst name, where it is uppercase

SELECT id, name_local, CONCAT(SUBSTRING(name_local FROM 1 FOR 1), LOWER(SUBSTRING(name_local FROM 2))) AS n, population
FROM geo_view_city_ua_ru
WHERE UPPER(name_local collate utf8_bin) = name_local collate utf8_bin

-- Fix ucfirst name, where it is uppercase

UPDATE geo_alternate_name
SET name = CONCAT(SUBSTRING(name FROM 1 FOR 1), LOWER(SUBSTRING(name FROM 2)))
WHERE 
	language_code IN('ru','uk','be')
	AND UPPER(name collate utf8_bin) = name collate utf8_bin

-- Remove not needed alternate names data

DELETE FROM `geo_alternate_name`
WHERE is_short = 1 OR is_colloquial = 1 OR is_historic = 1