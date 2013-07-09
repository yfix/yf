<?php

$Q = db()->query("SELECT * FROM ".db("cache")."");
while ($A = db()->fetch_assoc($Q)) $data[$A["key"]] = $A;