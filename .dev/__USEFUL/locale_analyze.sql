/* Get duplicate translates */
SELECT CONVERT( `value` 
USING utf8 ) AS `text` , COUNT( * ) AS `num` 
FROM `test_sys_locale_translate` 
GROUP BY `text` 
ORDER BY `num` DESC;

/* Get duplicate vars */
SELECT CONVERT( `value` 
USING utf8 ) AS `text` , COUNT( * ) AS `num` 
FROM `test_sys_locale_vars` 
GROUP BY `text` 
ORDER BY `num` DESC;

/* Get not translated vars for given locale */
SELECT CONVERT( `value` 
USING utf8 ) AS `text` 
FROM `new_sys_locale_vars` 
WHERE `id` NOT 
IN (
	SELECT `var_id` 
	FROM `new_sys_locale_translate` 
	WHERE `locale` = 'ru'
)
LIMIT 650;