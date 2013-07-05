<?php

$Q = db()->query("SELECT * FROM ".db("block_rules")." WHERE active='1' ORDER BY block_id ASC,order ASC");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;