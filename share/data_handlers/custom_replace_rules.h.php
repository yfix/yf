<?php

$Q = db()->query("SELECT * FROM `".db("custom_replace_rules")."` WHERE `active`='1' ORDER BY `tag_id` ASC, `order` ASC");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;