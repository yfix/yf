<?php

$Q = db()->query("SELECT * FROM `".db("font_type")."`");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A["value"];