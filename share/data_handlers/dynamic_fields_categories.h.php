<?php

$Q = db()->query("SELECT * FROM ".db("dynamic_fields_categories")."");
while ($A = db()->fetch_assoc($Q)) $data[$A["name"]] = $A["id"];