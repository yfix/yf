<?php

$Q = db()->query("SELECT * FROM ".db("category_items")." ORDER BY `order` ASC");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;
