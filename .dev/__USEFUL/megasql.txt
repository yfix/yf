SELECT m.`id`                                                  ,
       IFNULL(`__d_blabla`.`blabla`,'')         AS `blabla`    ,
       IFNULL(`__d_blabla2`.`blabla2`,'')       AS `blabla2`   ,
       IFNULL(`__d_blabla3`.`blabla3`,'')       AS `blabla3`   ,
       IFNULL(`__d_blabla1000`.`blabla1000`,'') AS `blabla1000`,
       IFNULL(`__d_blabla1001`.`blabla1001`,'') AS `blabla1001`,
       IFNULL(`__d_blabla1002`.`blabla1002`,'') AS `blabla1002`,
       IFNULL(`__d_blabla1003`.`blabla1003`,'') AS `blabla1003`,
       IFNULL(`__d_blabla1004`.`blabla1004`,'') AS `blabla1004`,
       IFNULL(`__d_blabla1005`.`blabla1005`,'') AS `blabla1005`,
       IFNULL(`__d_blabla1006`.`blabla1006`,'') AS `blabla1006`,
       IFNULL(`__d_blabla1007`.`blabla1007`,'') AS `blabla1007`,
       IFNULL(`__d_blabla1008`.`blabla1008`,'') AS `blabla1008`,
       IFNULL(`__d_blabla1009`.`blabla1009`,'') AS `blabla1009`,
       IFNULL(`__d_blabla1010`.`blabla1010`,'') AS `blabla1010`,
       IFNULL(`__d_blabla1011`.`blabla1011`,'') AS `blabla1011`,
       IFNULL(`__d_blabla1012`.`blabla1012`,'') AS `blabla1012`,
       IFNULL(`__d_blabla1013`.`blabla1013`,'') AS `blabla1013`,
       IFNULL(`__d_blabla1014`.`blabla1014`,'') AS `blabla1014`,
       IFNULL(`__d_blabla1015`.`blabla1015`,'') AS `blabla1015`,
       IFNULL(`__d_blabla1016`.`blabla1016`,'') AS `blabla1016`,
       IFNULL(`__d_blabla1017`.`blabla1017`,'') AS `blabla1017`,
       IFNULL(`__d_blabla1018`.`blabla1018`,'') AS `blabla1018`,
       IFNULL(`__d_blabla1019`.`blabla1019`,'') AS `blabla1019`,
       IFNULL(`__d_blabla1020`.`blabla1020`,'') AS `blabla1020`,
       IFNULL(`__d_blabla1021`.`blabla1021`,'') AS `blabla1021`,
       IFNULL(`__d_blabla1022`.`blabla1022`,'') AS `blabla1022`,
       IFNULL(`__d_blabla1023`.`blabla1023`,'') AS `blabla1023`,
       IFNULL(`__d_blabla1024`.`blabla1024`,'') AS `blabla1024`,
       IFNULL(`__d_blabla1025`.`blabla1025`,'') AS `blabla1025`,
       IFNULL(`__d_blabla1026`.`blabla1026`,'') AS `blabla1026`,
       IFNULL(`__d_blabla1027`.`blabla1027`,'') AS `blabla1027`,
       IFNULL(`__d_blabla1028`.`blabla1028`,'') AS `blabla1028`,
       IFNULL(`__d_blabla1029`.`blabla1029`,'') AS `blabla1029`,
       IFNULL(`__d_blabla1030`.`blabla1030`,'') AS `blabla1030`,
       IFNULL(`__d_blabla1031`.`blabla1031`,'') AS `blabla1031`,
       IFNULL(`__d_blabla1032`.`blabla1032`,'') AS `blabla1032`,
       IFNULL(`__d_blabla1033`.`blabla1033`,'') AS `blabla1033`,
       IFNULL(`__d_blabla1034`.`blabla1034`,'') AS `blabla1034`,
       IFNULL(`__d_blabla1035`.`blabla1035`,'') AS `blabla1035`,
       IFNULL(`__d_blabla1036`.`blabla1036`,'') AS `blabla1036`,
       IFNULL(`__d_blabla1037`.`blabla1037`,'') AS `blabla1037`,
       IFNULL(`__d_blabla1038`.`blabla1038`,'') AS `blabla1038`,
       IFNULL(`__d_blabla1039`.`blabla1039`,'') AS `blabla1039`,
       IFNULL(`__d_blabla1040`.`blabla1040`,'') AS `blabla1040`,
       IFNULL(`__d_blabla1041`.`blabla1041`,'') AS `blabla1041`,
       IFNULL(`__d_blabla1042`.`blabla1042`,'') AS `blabla1042`,
       IFNULL(`__d_blabla1043`.`blabla1043`,'') AS `blabla1043`,
       IFNULL(`__d_blabla1044`.`blabla1044`,'') AS `blabla1044`,
       IFNULL(`__d_blabla1045`.`blabla1045`,'') AS `blabla1045`,
       IFNULL(`__d_blabla1046`.`blabla1046`,'') AS `blabla1046`,
       IFNULL(`__d_blabla1047`.`blabla1047`,'') AS `blabla1047`,
       IFNULL(`__d_blabla1048`.`blabla1048`,'') AS `blabla1048`,
       IFNULL(`__d_blabla1049`.`blabla1049`,'') AS `blabla1049`,
       IFNULL(`__d_blabla1050`.`blabla1050`,'') AS `blabla1050`
FROM   `test_user_data_main`                    AS m NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla2`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 2
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla2` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla3`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 3
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla3` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1000`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1000
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1000` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1001`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1001
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1001` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1002`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1002
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1002` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1003`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1003
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1003` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1004`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1004
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1004` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1005`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1005
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1005` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1006`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1006
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1006` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1007`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1007
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1007` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1008`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1008
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1008` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1009`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1009
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1009` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1010`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1010
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1010` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1011`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1011
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1011` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1012`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1012
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1012` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1013`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1013
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1013` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1014`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1014
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1014` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1015`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1015
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1015` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1016`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1016
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1016` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1017`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1017
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1017` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1018`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1018
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1018` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1019`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1019
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1019` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1020`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1020
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1020` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1021`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1021
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1021` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1022`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1022
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1022` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1023`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1023
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1023` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1024`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1024
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1024` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1025`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1025
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1025` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1026`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1026
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1026` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1027`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1027
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1027` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1028`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1028
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1028` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1029`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1029
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1029` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1030`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1030
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1030` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1031`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1031
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1031` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1032`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1032
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1032` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1033`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1033
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1033` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1034`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1034
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1034` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1035`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1035
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1035` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1036`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1036
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1036` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1037`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1037
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1037` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1038`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1038
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1038` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1039`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1039
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1039` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1040`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1040
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1040` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1041`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1041
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1041` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1042`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1042
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1042` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1043`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1043
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1043` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1044`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1044
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1044` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1045`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1045
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1045` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1046`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1046
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1046` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1047`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1047
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1047` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1048`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1048
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1048` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1049`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1049
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1049` NATURAL
       LEFT JOIN
              ( SELECT `user_id`          AS `id` ,
                      IFNULL(`value`, '') AS `blabla1050`
              FROM    `test_user_data_info_values`
              WHERE   `field_id` = 1050
                  AND `user_id` IN(2000000000,2000000001,2000000002)
              ) AS `__d_blabla1050`
WHERE  m.`id` IN(2000000000,2000000001,2000000002)