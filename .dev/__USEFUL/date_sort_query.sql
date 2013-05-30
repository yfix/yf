SELECT * 
FROM `smsi_smslist` 
ORDER BY FROM_UNIXTIME( `time` , '%Y %m %d' ) ASC , `status` DESC 
LIMIT 0 , 30