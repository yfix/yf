DROP TABLE IF EXISTS `geo_view_adm_by_ru`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `geo_view_adm_by_ru` AS select `a`.`name` AS `name_local`,`a`.`id` AS `aid`,`g`.`id` AS `id`,`g`.`name` AS `name`,`g`.`name_ascii` AS `name_ascii`,`g`.`alternate_names` AS `alternate_names`,`g`.`latitude` AS `latitude`,`g`.`longitude` AS `longitude`,`g`.`feature_class` AS `feature_class`,`g`.`feature_code` AS `feature_code`,`g`.`country` AS `country`,`g`.`cc2` AS `cc2`,`g`.`admin1` AS `admin1`,`g`.`admin2` AS `admin2`,`g`.`admin3` AS `admin3`,`g`.`admin4` AS `admin4`,`g`.`population` AS `population`,`g`.`elevation` AS `elevation`,`g`.`gtopo30` AS `gtopo30`,`g`.`timezone` AS `timezone`,`g`.`mod_date` AS `mod_date` from ((`geo_geoname` `g` left join `geo_hierarchy` `h` on((`g`.`id` = `h`.`child_id`))) left join `geo_alternate_name` `a` on((`g`.`id` = `a`.`geoname_id`))) where ((`h`.`parent_id` = (select `geo_geoname`.`id` from `geo_geoname` where ((`geo_geoname`.`feature_code` = 'PCLI') and (`geo_geoname`.`country` = 'BY')))) and (`h`.`feature_code` = 'ADM') and (`a`.`language_code` = 'RU')) group by `g`.`id` order by (`a`.`name` collate utf8_unicode_ci);

DROP TABLE IF EXISTS `geo_view_adm_pl_ru`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `geo_view_adm_pl_ru` AS select `g`.`id` AS `id`,`g`.`name` AS `name`,`a`.`name` AS `name_local`,`g`.`latitude` AS `latitude`,`g`.`longitude` AS `longitude`,`g`.`country` AS `country`,`g`.`admin1` AS `admin1`,`g`.`admin2` AS `admin2`,`g`.`admin3` AS `admin3`,`g`.`admin4` AS `admin4`,`g`.`population` AS `population`,`g`.`timezone` AS `timezone` from (`geo_geoname` `g` left join `geo_alternate_name` `a` on((`g`.`id` = `a`.`geoname_id`))) where ((`g`.`feature_class` = 'P') and (`g`.`country` = 'PL') and (`g`.`population` > 1000) and (`g`.`feature_code` in ('PPL','PPLA','PPLC')) and (`a`.`language_code` = 'RU')) group by `g`.`id` order by `g`.`population` desc,(`a`.`name` collate utf8_unicode_ci);

DROP TABLE IF EXISTS `geo_view_adm_ru_ru`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `geo_view_adm_ru_ru` AS select `a`.`name` AS `name_local`,`a`.`id` AS `aid`,`g`.`id` AS `id`,`g`.`name` AS `name`,`g`.`name_ascii` AS `name_ascii`,`g`.`alternate_names` AS `alternate_names`,`g`.`latitude` AS `latitude`,`g`.`longitude` AS `longitude`,`g`.`feature_class` AS `feature_class`,`g`.`feature_code` AS `feature_code`,`g`.`country` AS `country`,`g`.`cc2` AS `cc2`,`g`.`admin1` AS `admin1`,`g`.`admin2` AS `admin2`,`g`.`admin3` AS `admin3`,`g`.`admin4` AS `admin4`,`g`.`population` AS `population`,`g`.`elevation` AS `elevation`,`g`.`gtopo30` AS `gtopo30`,`g`.`timezone` AS `timezone`,`g`.`mod_date` AS `mod_date` from ((`geo_geoname` `g` left join `geo_hierarchy` `h` on((`g`.`id` = `h`.`child_id`))) left join `geo_alternate_name` `a` on((`g`.`id` = `a`.`geoname_id`))) where ((`h`.`parent_id` = (select `geo_geoname`.`id` from `geo_geoname` where ((`geo_geoname`.`feature_code` = 'PCLI') and (`geo_geoname`.`country` = 'RU')))) and (`h`.`feature_code` = 'ADM') and (`a`.`language_code` = 'RU')) group by `g`.`id` order by (`a`.`name` collate utf8_unicode_ci);

DROP TABLE IF EXISTS `geo_view_adm_ua_ru`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `geo_view_adm_ua_ru` AS select `a`.`name` AS `name_local`,`a`.`id` AS `aid`,`g`.`id` AS `id`,`g`.`name` AS `name`,`g`.`name_ascii` AS `name_ascii`,`g`.`alternate_names` AS `alternate_names`,`g`.`latitude` AS `latitude`,`g`.`longitude` AS `longitude`,`g`.`feature_class` AS `feature_class`,`g`.`feature_code` AS `feature_code`,`g`.`country` AS `country`,`g`.`cc2` AS `cc2`,`g`.`admin1` AS `admin1`,`g`.`admin2` AS `admin2`,`g`.`admin3` AS `admin3`,`g`.`admin4` AS `admin4`,`g`.`population` AS `population`,`g`.`elevation` AS `elevation`,`g`.`gtopo30` AS `gtopo30`,`g`.`timezone` AS `timezone`,`g`.`mod_date` AS `mod_date` from ((`geo_geoname` `g` left join `geo_hierarchy` `h` on((`g`.`id` = `h`.`child_id`))) left join `geo_alternate_name` `a` on((`g`.`id` = `a`.`geoname_id`))) where ((`h`.`parent_id` = (select `geo_geoname`.`id` from `geo_geoname` where ((`geo_geoname`.`feature_code` = 'PCLI') and (`geo_geoname`.`country` = 'UA')))) and (`h`.`feature_code` = 'ADM') and (`a`.`language_code` = 'RU')) group by `g`.`id` order by (`a`.`name` collate utf8_unicode_ci);

DROP TABLE IF EXISTS `geo_view_adm_ua_uk`;
CREATE ALGORITHM=UNDEFINED SQL SECURITY DEFINER VIEW `geo_view_adm_ua_uk` AS select `a`.`name` AS `name_local`,`a`.`id` AS `aid`,`g`.`id` AS `id`,`g`.`name` AS `name`,`g`.`name_ascii` AS `name_ascii`,`g`.`alternate_names` AS `alternate_names`,`g`.`latitude` AS `latitude`,`g`.`longitude` AS `longitude`,`g`.`feature_class` AS `feature_class`,`g`.`feature_code` AS `feature_code`,`g`.`country` AS `country`,`g`.`cc2` AS `cc2`,`g`.`admin1` AS `admin1`,`g`.`admin2` AS `admin2`,`g`.`admin3` AS `admin3`,`g`.`admin4` AS `admin4`,`g`.`population` AS `population`,`g`.`elevation` AS `elevation`,`g`.`gtopo30` AS `gtopo30`,`g`.`timezone` AS `timezone`,`g`.`mod_date` AS `mod_date` from ((`geo_geoname` `g` left join `geo_hierarchy` `h` on((`g`.`id` = `h`.`child_id`))) left join `geo_alternate_name` `a` on((`g`.`id` = `a`.`geoname_id`))) where ((`h`.`parent_id` = (select `geo_geoname`.`id` from `geo_geoname` where ((`geo_geoname`.`feature_code` = 'PCLI') and (`geo_geoname`.`country` = 'UA')))) and (`h`.`feature_code` = 'ADM') and (`a`.`language_code` = 'UK')) group by `g`.`id` order by (`a`.`name` collate utf8_unicode_ci);

-----------------------------------

DROP TABLE IF EXISTS `geo_view_city`;
CREATE VIEW `geo_view_city` AS 
SELECT
	g.id, g.name, a.name AS name_local, g.latitude, g.longitude, g.country,
	g.admin1, g.admin2, g.admin3, g.admin4, g.population,  g.timezone,
	a.language_code, a.is_preferred
FROM geo_geoname AS g
LEFT JOIN geo_alternate_name AS a ON g.id = a.geoname_id
WHERE
	g.feature_class =  'P'
	AND g.population > 1000
	AND g.feature_code IN('PPL','PPLA','PPLC')

---------------------------------

DROP TABLE IF EXISTS `geo_view_city_ua_ru`;
CREATE VIEW `geo_view_city` AS 
SELECT
	g.id, g.name, a.name AS name_local, g.latitude, g.longitude, g.country,
	g.admin1, g.admin2, g.admin3, g.admin4, g.population,  g.timezone,
	a.language_code, a.is_preferred
FROM geo_geoname AS g
LEFT JOIN geo_alternate_name AS a ON g.id = a.geoname_id
WHERE
	g.feature_class =  'P'
	AND g.population > 1000
	AND g.feature_code IN('PPL','PPLA','PPLC')
	AND g.country IN('UA')
	AND a.language_code = 'RU'
GROUP BY g.id
ORDER BY a.name COLLATE utf8_unicode_ci ASC

---------------------------------

DROP TABLE IF EXISTS `geo_view_city_ua_uk`;
CREATE VIEW `geo_view_city` AS 
SELECT
	g.id, g.name, a.name AS name_local, g.latitude, g.longitude, g.country,
	g.admin1, g.admin2, g.admin3, g.admin4, g.population,  g.timezone,
	a.language_code, a.is_preferred
FROM geo_geoname AS g
LEFT JOIN geo_alternate_name AS a ON g.id = a.geoname_id
WHERE
	g.feature_class =  'P'
	AND g.population > 1000
	AND g.feature_code IN('PPL','PPLA','PPLC')
	AND g.country IN('UA')
	AND a.language_code = 'UK'
GROUP BY g.id
ORDER BY a.name COLLATE utf8_unicode_ci ASC

---------------------------------

DROP TABLE IF EXISTS `geo_view_city_ru_ru`;
CREATE VIEW `geo_view_city` AS 
SELECT
	g.id, g.name, a.name AS name_local, g.latitude, g.longitude, g.country,
	g.admin1, g.admin2, g.admin3, g.admin4, g.population,  g.timezone,
	a.language_code, a.is_preferred
FROM geo_geoname AS g
LEFT JOIN geo_alternate_name AS a ON g.id = a.geoname_id
WHERE
	g.feature_class =  'P'
	AND g.population > 1000
	AND g.feature_code IN('PPL','PPLA','PPLC')
	AND g.country IN('RU')
	AND a.language_code = 'RU'
GROUP BY g.id
ORDER BY a.name COLLATE utf8_unicode_ci ASC

---------------------------------

DROP TABLE IF EXISTS `geo_view_city_ru_uk`;
CREATE VIEW `geo_view_city` AS 
SELECT
	g.id, g.name, a.name AS name_local, g.latitude, g.longitude, g.country,
	g.admin1, g.admin2, g.admin3, g.admin4, g.population,  g.timezone,
	a.language_code, a.is_preferred
FROM geo_geoname AS g
LEFT JOIN geo_alternate_name AS a ON g.id = a.geoname_id
WHERE
	g.feature_class =  'P'
	AND g.population > 1000
	AND g.feature_code IN('PPL','PPLA','PPLC')
	AND g.country IN('RU')
	AND a.language_code = 'UK'
GROUP BY g.id
ORDER BY a.name COLLATE utf8_unicode_ci ASC
