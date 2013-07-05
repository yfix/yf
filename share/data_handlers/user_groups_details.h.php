<?php

$Q = db()->query("SELECT * FROM ".db("user_groups")." WHERE active='1'");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;