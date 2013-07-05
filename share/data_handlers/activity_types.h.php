<?php

$Q = db()->query("SELECT * FROM ".db("activity_types")." WHERE active='1'");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A;