<?php

$Q = db()->query("SELECT * FROM ".db("admin_groups")." WHERE active='1'");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;