<?php

$Q = db()->query("SELECT * FROM ".db("dynamic_fields_info")." WHERE active='1' ORDER BY order");
while ($A = db()->fetch_assoc($Q)) $data[$A["category_id"]][$A["id"]] = $A;