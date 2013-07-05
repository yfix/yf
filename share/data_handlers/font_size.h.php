<?php

$Q = db()->query("SELECT * FROM ".db("font_size")."");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A["value"];