<?php

$Q = db()->query("SELECT name, value FROM ".db("conf")." WHERE active='1'");
while ($A = db()->fetch_assoc($Q)) $data[$A["name"]] = $A["value"];
