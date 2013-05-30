<?php

$Q = db()->query("SELECT * FROM `".db("heights")."`");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A["height"];