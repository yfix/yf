<?php

$Q = db()->query("SELECT * FROM `".db("categories")."`");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;