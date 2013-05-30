<?php

$Q = db()->query("SELECT * FROM `".db("weights")."`");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A["weight"];