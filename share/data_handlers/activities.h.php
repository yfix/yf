<?php

$Q = db()->query("SELECT * FROM ".db("prof_interests")." WHERE 1 ".($locale ? " AND locale='".db()->es($locale)."'" : "")." ORDER BY name ASC");
while ($A = db()->fetch_assoc($Q)) $data[$A["id"]] = $A["name"];