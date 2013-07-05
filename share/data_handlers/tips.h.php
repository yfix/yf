<?php

$Q = db()->query("SELECT * FROM ".db("tips")." WHERE active='1'".($locale ? " AND locale='".db()->es($locale)."'" : ""));
while ($A = db()->fetch_assoc($Q)) $data[$A["name"]] = $A;