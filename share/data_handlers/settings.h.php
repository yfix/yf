<?php

$Q = db()->query("SELECT * FROM `".db("settings")."`");
while ($A = db()->fetch_assoc($Q)) $data[$A["item"]] = $A["value"];