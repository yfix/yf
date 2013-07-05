<?php

$Q = db()->query("SELECT * FROM ".db("blocks")."");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;