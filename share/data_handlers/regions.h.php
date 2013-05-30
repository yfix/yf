<?php

$Q = db()->query("SELECT * FROM `".db("geo_regions")."` ORDER BY `country` ASC, `code` ASC");
while ($A = db()->fetch_assoc($Q)) $data[$A["country"]][$A["code"]] = $A["name"];