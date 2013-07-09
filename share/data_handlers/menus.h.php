<?php

$Q = db()->query("SELECT * FROM ".db("menus")."");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;