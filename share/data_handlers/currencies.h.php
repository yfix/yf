<?php

$Q = db()->query("SELECT name, sign FROM ".db("currencies")." ORDER BY name");
while ($A = db()->fetch_assoc($Q)) $data[$A["name"]] = $A["sign"];