<?php

$Q = db()->query("SELECT * FROM `".db("sites")."`");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;