<?php

$Q = db()->query("SELECT * FROM `".db("forum_categories")."` ORDER BY `order` ASC");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;