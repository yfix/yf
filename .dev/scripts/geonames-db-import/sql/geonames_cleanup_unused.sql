DELETE FROM geo_geoname WHERE feature_class NOT IN ('A','P');

SELECT COUNT(*) 
FROM geo_alternate_name AS a 
LEFT JOIN geo_geonames AS g ON a.geoname_id = g.id
WHERE g.id IS NULL;